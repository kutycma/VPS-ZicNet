<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VpsInstance;
use App\Models\Order;
use App\Services\Vps\VpsManager;
use App\Services\Billing\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VpsApiController extends Controller
{
    /**
     * GET /api/vps?tab=active|expiring_soon|expired|deleted|all
     */
    public function index(Request $request)
    {
        $tab   = $request->query('tab', 'active');
        $user  = Auth::user();
        $query = $user->vpsInstances()->with(['plan', 'provider']);

        if ($tab === 'active') {
            $query->where('status', 'active')->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            });
        } elseif ($tab === 'expiring_soon') {
            $query->where('status', 'active')->whereNotNull('expires_at')
                  ->whereBetween('expires_at', [now(), now()->addDays(7)]);
        } elseif ($tab === 'expired') {
            $query->where(function ($q) {
                $q->where('status', 'expired')->orWhere('expires_at', '<', now());
            })->where('status', '!=', 'deleted');
        } elseif ($tab === 'deleted') {
            $query->where('status', 'deleted');
        }

        $instances = $query->latest()->paginate(15);

        $counts = [
            'all' => $user->vpsInstances()->count(),
            'active' => $user->vpsInstances()->where('status', 'active')->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })->count(),
            'expiring_soon' => $user->vpsInstances()->where('status', 'active')
                ->whereNotNull('expires_at')
                ->whereBetween('expires_at', [now(), now()->addDays(7)])->count(),
            'expired' => $user->vpsInstances()->where(function ($q) {
                $q->where('status', 'expired')->orWhere('expires_at', '<', now());
            })->where('status', '!=', 'deleted')->count(),
            'deleted' => $user->vpsInstances()->where('status', 'deleted')->count(),
        ];

        return response()->json([
            'data'       => array_map(function ($i) { return $this->formatInstance($i); }, $instances->items()),
            'counts'     => $counts,
            'pagination' => [
                'current_page' => $instances->currentPage(),
                'last_page'    => $instances->lastPage(),
                'per_page'     => $instances->perPage(),
                'total'        => $instances->total(),
            ],
        ]);
    }

    /**
     * GET /api/vps/{instance}
     */
    public function show(VpsInstance $instance)
    {
        if ($instance->user_id !== Auth::id()) abort(403);

        $instance->load(['plan', 'provider', 'orders']);

        $providerInfo = null;
        try {
            $manager      = new VpsManager();
            $providerInfo = $manager->getVpsInfo($instance);
        } catch (\Exception $e) {
            $providerInfo = ['error' => $e->getMessage()];
        }

        return response()->json([
            'instance'      => $this->formatInstance($instance),
            'provider_info' => $providerInfo,
        ]);
    }

    /**
     * POST /api/vps/{instance}/action
     */
    public function action(Request $request, VpsInstance $instance)
    {
        if ($instance->user_id !== Auth::id()) abort(403);

        $request->validate([
            'action' => 'required|in:on,off,restart,cancel,on-auto-renew,off-auto-renew',
        ]);

        try {
            $manager = new VpsManager();
            $result  = $manager->controlVps($instance, $request->action);

            $statusMap = ['on' => 'active', 'off' => 'stopped'];
            if (isset($statusMap[$request->action])) {
                $instance->update(['status' => $statusMap[$request->action]]);
            }

            return response()->json(['success' => true, 'result' => $result]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * GET /api/vps/{instance}/rebuild
     */
    public function getRebuild(VpsInstance $instance)
    {
        if ($instance->user_id !== Auth::id()) abort(403);

        try {
            $manager = new VpsManager();
            $result  = $manager->controlVps($instance, 'check-os-when-rebuild-vps');
            $osList  = $result['os-vps'] ?? ($result['list-os'] ?? ($result['data'] ?? []));
            return response()->json(['os_list' => $osList]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * POST /api/vps/{instance}/rebuild
     */
    public function confirmRebuild(Request $request, VpsInstance $instance)
    {
        if ($instance->user_id !== Auth::id()) abort(403);
        $request->validate(['os_id' => 'required|integer']);

        try {
            $manager = new VpsManager();
            $manager->controlVps($instance, 'confirm-rebuild-vps', ['os_id' => $request->os_id]);
            return response()->json(['success' => true, 'message' => 'Rebuild initiated.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * GET /api/vps/{instance}/renew
     */
    public function getRenew(VpsInstance $instance)
    {
        if ($instance->user_id !== Auth::id()) abort(403);

        $plan = $instance->plan;
        if (!$plan) {
            return response()->json(['message' => 'No plan attached to this VPS.'], 422);
        }

        return response()->json([
            'instance'       => $this->formatInstance($instance),
            'plan'           => ['id' => $plan->id, 'name' => $plan->name],
            'billing_cycles' => $plan->sorted_pricing,
        ]);
    }

    /**
     * POST /api/vps/{instance}/renew
     */
    public function confirmRenew(Request $request, VpsInstance $instance)
    {
        if ($instance->user_id !== Auth::id()) abort(403);
        $request->validate(['billing_cycle' => 'required|string']);

        $plan        = $instance->plan;
        $pricingData = $plan->sorted_pricing;
        $cycle       = $request->billing_cycle;
        $price       = isset($pricingData[$cycle]['selling_price'])
            ? (float) $pricingData[$cycle]['selling_price']
            : (float) $plan->selling_price;

        $user = Auth::user();
        if ($user->balance < $price) {
            return response()->json([
                'message' => 'Insufficient balance. Need ' . number_format($price, 0, ',', '.') . 'đ',
            ], 422);
        }

        try {
            return DB::transaction(function () use ($instance, $plan, $user, $cycle, $price) {
                (new PaymentService())->purchase($user, $price, "Gia hạn VPS #{$instance->id} - {$plan->name}");

                Order::create([
                    'user_id'         => $user->id,
                    'vps_plan_id'     => $plan->id,
                    'vps_instance_id' => $instance->id,
                    'amount'          => $price,
                    'billing_cycle'   => $cycle,
                    'duration_months' => 1,
                    'type'            => 'renew',
                    'status'          => 'pending',
                    'payload'         => ['billing_cycle' => $cycle],
                ]);

                return response()->json(['success' => true, 'message' => 'Renew request submitted.']);
            });
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/vps/{instance}/upgrade
     */
    public function getUpgrade(VpsInstance $instance)
    {
        if ($instance->user_id !== Auth::id()) abort(403);

        $addons = [];
        try {
            $manager     = new VpsManager();
            $driver      = $manager->resolveProvider($instance->provider);
            $allProducts = $driver->getProducts();

            foreach ($allProducts as $product) {
                $name = $product['name'] ?? '';
                if (stripos($name, 'Mua thêm') !== false || stripos($name, 'addon') !== false) {
                    $monthly      = isset($product['pricing']['monthly']['amount']) ? (float)$product['pricing']['monthly']['amount'] : 0;
                    $sellingPrice = $instance->provider->calculateSellingPrice($monthly);
                    $type         = 'unknown';
                    if (stripos($name, 'cpu') !== false)  $type = 'cpu';
                    if (stripos($name, 'ram') !== false)  $type = 'ram';
                    if (stripos($name, 'disk') !== false) $type = 'disk';

                    if ($type !== 'unknown') {
                        $addons[$type] = [
                            'name'                    => $name,
                            'provider_price'          => $monthly,
                            'selling_price'           => $sellingPrice,
                            'selling_price_formatted' => number_format($sellingPrice, 0, ',', '.') . 'đ',
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['instance' => $this->formatInstance($instance), 'addons' => $addons]);
    }

    /**
     * POST /api/vps/{instance}/upgrade
     */
    public function confirmUpgrade(Request $request, VpsInstance $instance)
    {
        if ($instance->user_id !== Auth::id()) abort(403);

        $request->validate([
            'addon_cpu'  => 'integer|min:0',
            'addon_ram'  => 'integer|min:0',
            'addon_disk' => 'integer|min:0',
        ]);

        $cpu  = (int) $request->addon_cpu;
        $ram  = (int) $request->addon_ram;
        $disk = (int) $request->addon_disk;

        if ($cpu == 0 && $ram == 0 && $disk == 0) {
            return response()->json(['message' => 'Select at least one resource to upgrade.'], 422);
        }

        $addons = [];
        try {
            $manager = new VpsManager();
            $driver  = $manager->resolveProvider($instance->provider);
            foreach ($driver->getProducts() as $product) {
                $name    = $product['name'] ?? '';
                $monthly = isset($product['pricing']['monthly']['amount']) ? (float)$product['pricing']['monthly']['amount'] : 0;
                $price   = $instance->provider->calculateSellingPrice($monthly);
                if (stripos($name, 'cpu') !== false)  $addons['cpu']  = $price;
                if (stripos($name, 'ram') !== false)  $addons['ram']  = $price;
                if (stripos($name, 'disk') !== false) $addons['disk'] = $price;
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to verify upgrade prices.'], 422);
        }

        $total = 0;
        if ($cpu  > 0) $total += ($addons['cpu']  ?? 0) * $cpu;
        if ($ram  > 0) $total += ($addons['ram']  ?? 0) * $ram;
        if ($disk > 0) $total += ($addons['disk'] ?? 0) * ($disk / 10);

        if ($total <= 0) return response()->json(['message' => 'Invalid total price.'], 422);

        $user = Auth::user();
        if ($user->balance < $total) {
            return response()->json(['message' => 'Insufficient balance. Need ' . number_format($total, 0, ',', '.') . 'đ'], 422);
        }

        try {
            return DB::transaction(function () use ($instance, $user, $cpu, $ram, $disk, $total) {
                (new PaymentService())->purchase($user, $total, "Nâng cấp VPS #{$instance->id} (+{$cpu}CPU, +{$ram}RAM, +{$disk}GB)");

                Order::create([
                    'user_id'         => $user->id,
                    'vps_plan_id'     => $instance->plan_id,
                    'vps_instance_id' => $instance->id,
                    'amount'          => $total,
                    'billing_cycle'   => 'upgrade',
                    'duration_months' => 0,
                    'type'            => 'upgrade',
                    'status'          => 'pending',
                    'payload'         => ['addon_cpu' => $cpu, 'addon_ram' => $ram, 'addon_disk' => $disk],
                ]);

                return response()->json(['success' => true, 'message' => 'Upgrade request submitted.']);
            });
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function formatInstance(VpsInstance $instance): array
    {
        $expiresAt          = $instance->expires_at;
        $isExpiringSoon     = $expiresAt && $expiresAt->diffInDays(now()) <= 7 && $expiresAt->isFuture();

        return [
            'id'                    => $instance->id,
            'hostname'              => $instance->hostname,
            'ip_address'            => $instance->ip_address,
            'status'                => $instance->status,
            'os'                    => $instance->os,
            'expires_at'            => $expiresAt ? $expiresAt->toISOString() : null,
            'expires_at_formatted'  => $expiresAt ? $expiresAt->format('d/m/Y') : null,
            'is_expiring_soon'      => $isExpiringSoon,
            'auto_renew'            => (bool) $instance->auto_renew,
            'plan'                  => ($instance->relationLoaded('plan') && $instance->plan) ? [
                'id'   => $instance->plan->id,
                'name' => $instance->plan->name,
                'type' => $instance->plan->type,
            ] : null,
            'created_at'            => $instance->created_at ? $instance->created_at->toISOString() : null,
        ];
    }
}
