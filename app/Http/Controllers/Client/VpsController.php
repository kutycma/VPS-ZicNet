<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\VpsInstance;
use App\Models\Order;
use App\Services\Vps\VpsManager;
use App\Services\Billing\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VpsController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'active');
        $query = Auth::user()->vpsInstances()->with(['plan', 'provider']);

        if ($tab === 'active') {
            $query->where('status', 'active')->where(function($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            });
        } elseif ($tab === 'expiring_soon') {
            $query->where('status', 'active')
                  ->whereNotNull('expires_at')
                  ->whereBetween('expires_at', [now(), now()->addDays(7)]);
        } elseif ($tab === 'expired') {
            $query->where(function($q) {
                $q->where('status', 'expired')
                  ->orWhere('expires_at', '<', now());
            })->where('status', '!=', 'deleted');
        } elseif ($tab === 'deleted') {
            $query->where('status', 'deleted');
        } elseif ($tab === 'all') {
            // Keep all
        }

        $instances = $query->latest()->paginate(15)->appends(['tab' => $tab]);

        $counts = [
            'all' => Auth::user()->vpsInstances()->count(),
            'active' => Auth::user()->vpsInstances()->where('status', 'active')->where(function($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })->count(),
            'expiring_soon' => Auth::user()->vpsInstances()->where('status', 'active')
                ->whereNotNull('expires_at')
                ->whereBetween('expires_at', [now(), now()->addDays(7)])->count(),
            'expired' => Auth::user()->vpsInstances()->where(function($q) {
                $q->where('status', 'expired')->orWhere('expires_at', '<', now());
            })->where('status', '!=', 'deleted')->count(),
            'deleted' => Auth::user()->vpsInstances()->where('status', 'deleted')->count(),
        ];

        return view('client.vps.index', compact('instances', 'tab', 'counts'));
    }

    public function show(VpsInstance $instance)
    {
        // Chỉ cho phép xem VPS của chính mình
        if ($instance->user_id !== Auth::id()) {
            abort(403);
        }

        $instance->load(['plan', 'provider', 'orders']);

        // Lấy thông tin realtime từ provider
        $providerInfo = null;
        try {
            $manager = new VpsManager();
            $providerInfo = $manager->getVpsInfo($instance);
        } catch (\Exception $e) {
            $providerInfo = ['error' => $e->getMessage()];
        }

        return view('client.vps.show', compact('instance', 'providerInfo'));
    }

    public function action(Request $request, VpsInstance $instance)
    {
        if ($instance->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'action' => 'required|in:on,off,restart,cancel,on-auto-renew,off-auto-renew',
        ]);

        try {
            $manager = new VpsManager();
            $result = $manager->controlVps($instance, $request->action);

            $statusMap = [
                'on' => 'active',
                'off' => 'stopped',
            ];
            if (isset($statusMap[$request->action])) {
                $instance->update(['status' => $statusMap[$request->action]]);
            }

            return redirect()->back()->with('success', 'Thao tác thành công');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }

    public function rebuild(VpsInstance $instance)
    {
        if ($instance->user_id !== Auth::id()) {
            abort(403);
        }

        try {
            $manager = new VpsManager();
            $result = $manager->controlVps($instance, 'check-os-when-rebuild-vps');
            
            // Tìm mảng chứa danh sách OS, có thể là 'os-vps' hoặc 'list-os'
            $osList = $result['os-vps'] ?? ($result['list-os'] ?? ($result['data'] ?? []));

            return view('client.vps.rebuild', compact('instance', 'osList', 'result'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Lỗi kết nối bộ cài: ' . $e->getMessage());
        }
    }

    public function confirmRebuild(Request $request, VpsInstance $instance)
    {
        if ($instance->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'os_id' => 'required|integer',
        ]);

        try {
            $manager = new VpsManager();
            $manager->controlVps($instance, 'confirm-rebuild-vps', [
                'os_id' => $request->os_id
            ]);

            return redirect()->route('client.vps.show', $instance)->with('success', 'Lệnh cài lại hệ điều hành đã được gửi. VPS sẽ sẵn sàng sau vài phút.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }

    public function renew(VpsInstance $instance)
    {
        if ($instance->user_id !== Auth::id()) abort(403);
        $plan = $instance->plan;
        if (!$plan) return redirect()->back()->with('error', 'VPS không thuộc gói nào, không thể tự gia hạn.');
        
        $billingCycles = $plan->sorted_pricing;
        
        return view('client.vps.renew', compact('instance', 'plan', 'billingCycles'));
    }

    public function confirmRenew(Request $request, VpsInstance $instance)
    {
        if ($instance->user_id !== Auth::id()) abort(403);
        
        $request->validate(['billing_cycle' => 'required|string']);
        
        $plan = $instance->plan;
        $pricingData = $plan->sorted_pricing;
        $selectedCycle = $request->billing_cycle;
        
        $unitPrice = isset($pricingData[$selectedCycle]['selling_price']) 
            ? (float) $pricingData[$selectedCycle]['selling_price'] 
            : (float) $plan->selling_price;
            
        $user = Auth::user();

        if ($user->balance < $unitPrice) {
            return redirect()->back()->with('error', 'Số dư không đủ để gia hạn (Cần ' . number_format($unitPrice) . 'đ)');
        }

        try {
            return DB::transaction(function () use ($instance, $plan, $user, $request, $unitPrice, $selectedCycle) {
                // Trừ tiền
                $paymentService = new PaymentService();
                $paymentService->purchase($user, $unitPrice, "Gia hạn VPS #{$instance->id} gói {$plan->name}");

                // Ghi Order
                $order = Order::create([
                    'user_id' => $user->id,
                    'vps_plan_id' => $plan->id,
                    'vps_instance_id' => $instance->id,
                    'amount' => $unitPrice,
                    'billing_cycle' => $selectedCycle,
                    'duration_months' => 1,
                    'type' => 'renew',
                    'status' => 'pending',
                    'payload' => [
                        'billing_cycle' => $selectedCycle
                    ],
                ]);

                return redirect()->route('client.vps.show', $instance)
                    ->with('success', 'Đã ghi nhận yêu cầu gia hạn VPS. Hệ thống đang tiến hành xử lý ngầm trong vòng vài phút.');
            });
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Lỗi hệ thống: ' . $e->getMessage());
        }
    }

    public function upgrade(VpsInstance $instance)
    {
        if ($instance->user_id !== Auth::id()) abort(403);
        
        $addons = [];
        try {
            $manager = new VpsManager();
            $driver = $manager->resolveProvider($instance->provider);
            $allProducts = $driver->getProducts();
            
            foreach ($allProducts as $product) {
                $productName = $product['name'] ?? '';
                if (stripos($productName, 'Mua thêm') !== false || stripos($productName, 'addon') !== false) {
                    $addonPricing = $product['pricing'] ?? [];
                    // Fallback using Monthly price
                    $addonMonthly = isset($addonPricing['monthly']['amount']) ? (float)$addonPricing['monthly']['amount'] : 0;
                    
                    // Markup for addon (Let's assume Provider calculateSellingPrice applies)
                    $sellingPrice = $instance->provider->calculateSellingPrice($addonMonthly);

                    $type = 'unknown';
                    if (stripos($productName, 'cpu') !== false) $type = 'cpu';
                    if (stripos($productName, 'ram') !== false) $type = 'ram';
                    if (stripos($productName, 'disk') !== false) $type = 'disk';
                    
                    if ($type !== 'unknown') {
                        $addons[$type] = [
                            'name' => $productName,
                            'provider_price' => $addonMonthly,
                            'selling_price' => $sellingPrice
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Không thể lấy thông tin giá Nâng cấp: ' . $e->getMessage());
        }
        
        return view('client.vps.upgrade', compact('instance', 'addons'));
    }

    public function confirmUpgrade(Request $request, VpsInstance $instance)
    {
        if ($instance->user_id !== Auth::id()) abort(403);
        
        $request->validate([
            'addon_cpu' => 'integer|min:0',
            'addon_ram' => 'integer|min:0',
            'addon_disk' => 'integer|min:0|multiple_of:10',
            'total_price' => 'required|numeric|min:1'
        ]);

        $cpu = (int) $request->addon_cpu;
        $ram = (int) $request->addon_ram;
        $disk = (int) $request->addon_disk;

        if ($cpu == 0 && $ram == 0 && $disk == 0) {
            return redirect()->back()->with('error', 'Vui lòng chọn ít nhất 1 tài nguyên để nâng cấp.');
        }

        // Normally we should re-calculate $expectedPrice from session or API exactly, 
        // to prevent users inspect-element tampering `$request->total_price`.
        // Let's re-fetch the addon prices to validate calculation!
        $addons = [];
        try {
            $manager = new VpsManager();
            $driver = $manager->resolveProvider($instance->provider);
            $allProducts = $driver->getProducts();
            foreach ($allProducts as $product) {
                $productName = $product['name'] ?? '';
                if (stripos($productName, 'Mua thêm') !== false || stripos($productName, 'addon') !== false) {
                    $addonMonthly = isset($product['pricing']['monthly']['amount']) ? (float)$product['pricing']['monthly']['amount'] : 0;
                    $sellingPrice = $instance->provider->calculateSellingPrice($addonMonthly);
                    if (stripos($productName, 'cpu') !== false) $addons['cpu'] = $sellingPrice;
                    if (stripos($productName, 'ram') !== false) $addons['ram'] = $sellingPrice;
                    if (stripos($productName, 'disk') !== false) $addons['disk'] = $sellingPrice;
                }
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Lỗi xác minh giá nâng cấp.');
        }

        $expectedPrice = 0;
        if ($cpu > 0) $expectedPrice += ($addons['cpu'] ?? 0) * $cpu;
        if ($ram > 0) $expectedPrice += ($addons['ram'] ?? 0) * $ram;
        if ($disk > 0) $expectedPrice += ($addons['disk'] ?? 0) * ($disk / 10);

        if ($expectedPrice <= 0) {
            return redirect()->back()->with('error', 'Lỗi: Không tính được tổng tiền hợp lệ.');
        }

        $user = Auth::user();
        if ($user->balance < $expectedPrice) {
            return redirect()->back()->with('error', 'Số dư không đủ. Cần ' . number_format($expectedPrice) . 'đ');
        }

        try {
            return DB::transaction(function () use ($instance, $user, $cpu, $ram, $disk, $expectedPrice) {
                $paymentService = new PaymentService();
                $paymentService->purchase($user, $expectedPrice, "Nâng cấp VPS #{$instance->id} (+{$cpu}CPU, +{$ram}RAM, +{$disk}GB)");

                $order = Order::create([
                    'user_id' => $user->id,
                    'vps_plan_id' => $instance->plan_id,
                    'vps_instance_id' => $instance->id,
                    'amount' => $expectedPrice,
                    'billing_cycle' => 'upgrade',
                    'duration_months' => 0,
                    'type' => 'upgrade',
                    'status' => 'pending',
                    'payload' => [
                        'addon_cpu' => $cpu,
                        'addon_ram' => $ram,
                        'addon_disk' => $disk,
                    ],
                ]);

                return redirect()->route('client.vps.show', $instance)
                    ->with('success', 'Đã tiếp nhận yêu cầu nâng cấp cấu hình máy chủ. Hệ thống đang tiến hành nâng cấp!');
            });
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Lỗi hệ thống: ' . $e->getMessage());
        }
    }
}
