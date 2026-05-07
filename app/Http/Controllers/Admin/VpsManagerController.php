<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VpsInstance;
use App\Models\Order;
use App\Models\Transaction;
use App\Services\Vps\VpsManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VpsManagerController extends Controller
{
    public function index(Request $request)
    {
        $query = VpsInstance::with(['user', 'plan', 'provider']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ip_address', 'like', "%{$search}%")
                  ->orWhere('vps_provider_id', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $instances = $query->latest()->paginate(20);

        return view('admin.vps.index', compact('instances'));
    }

    public function show(VpsInstance $instance)
    {
        $instance->load(['user', 'plan', 'provider', 'orders']);

        // Lấy thông tin realtime từ provider
        $providerInfo = null;
        try {
            $manager = new VpsManager();
            $providerInfo = $manager->getVpsInfo($instance);
        } catch (\Exception $e) {
            $providerInfo = ['error' => $e->getMessage()];
        }

        return view('admin.vps.show', compact('instance', 'providerInfo'));
    }

    public function assignUser(Request $request, VpsInstance $instance)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.exists' => 'Không tìm thấy khách hàng nào với Email này trong hệ thống.'
        ]);

        try {
            $oldUser = $instance->user;
            $newUser = \App\Models\User::where('email', $request->email)->firstOrFail();
            
            if ($oldUser && $oldUser->id === $newUser->id) {
                return redirect()->back()->with('error', 'Khách hàng này hiện đã là chủ sở hữu của VPS!');
            }

            $instance->user_id = $newUser->id;
            $instance->save();

            $oldEmail = $oldUser ? $oldUser->email : 'N/A';
            $oldName = $oldUser ? $oldUser->name : 'N/A';

            \Illuminate\Support\Facades\Log::info("VPS {$instance->id} assigned from User {$oldEmail} to {$newUser->email} by Admin.");

            return redirect()->back()->with('success', "Gán VPS chuyển từ {$oldName} sang cho {$newUser->name} thành công!");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Lỗi khi chuyển quyền VPS: ' . $e->getMessage());
        }
    }

    public function action(Request $request, VpsInstance $instance)
    {
        $request->validate([
            'action' => 'required|in:on,off,restart,cancel,on-auto-renew,off-auto-renew',
        ]);

        try {
            $manager = new VpsManager();
            $result = $manager->controlVps($instance, $request->action);

            $statusMap = [
                'on' => 'active',
                'off' => 'stopped',
                'cancel' => 'cancelled',
            ];
            if (isset($statusMap[$request->action])) {
                $instance->update(['status' => $statusMap[$request->action]]);
            }

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Thao tác thành công.',
                    'new_status' => $instance->status,
                    'new_status_badge' => $instance->status_badge,
                    'auto_renew' => $instance->auto_renew,
                ]);
            }

            return redirect()->back()->with('success', 'Thao tác thành công');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
            }
            return redirect()->back()->with('error', 'Lỗi: ' . $e->getMessage());
        }
    }

    public function rebuild(VpsInstance $instance)
    {
        try {
            $manager = new VpsManager();
            $result = $manager->controlVps($instance, 'check-os-when-rebuild-vps');
            
            // Tìm mảng chứa danh sách OS, có thể là 'os-vps' hoặc 'list-os'
            $osList = $result['os-vps'] ?? ($result['list-os'] ?? ($result['data'] ?? []));

            return view('admin.vps.rebuild', compact('instance', 'osList'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Không lấy được danh sách hệ điều hành: ' . $e->getMessage());
        }
    }

    public function confirmRebuild(Request $request, VpsInstance $instance)
    {
        $request->validate(['os_id' => 'required|integer']);
        try {
            $manager = new VpsManager();
            $manager->controlVps($instance, 'confirm-rebuild-vps', [
                'os_id' => $request->os_id
            ]);
            return redirect()->route('admin.vps.show', $instance)->with('success', 'Đã gởi yêu cầu cài lại hệ điều hành thành công.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Lỗi cài lại HĐH: ' . $e->getMessage());
        }
    }

    public function renew(VpsInstance $instance)
    {
        $billingCycles = $instance->plan->sorted_pricing;
        
        return view('admin.vps.renew', compact('instance', 'billingCycles'));
    }

    public function confirmRenew(Request $request, VpsInstance $instance)
    {
        $request->validate([
            'billing_cycle' => 'required|string',
            'custom_amount' => 'required|numeric|min:0',
        ]);

        $customAmount = (float) $request->custom_amount;
        $user = $instance->user;

        if ($customAmount > 0 && $user->balance < $customAmount) {
            return redirect()->back()->with('error', 'Tài khoản khách hàng không đủ số dư để gia hạn (Yêu cầu: ' . number_format($customAmount) . 'đ).');
        }

        try {
            DB::transaction(function () use ($user, $instance, $request, $customAmount) {
                if ($customAmount > 0) {
                    $user->decrement('balance', $customAmount);
                    Transaction::create([
                        'user_id' => $user->id,
                        'type' => 'payment',
                        'amount' => $customAmount,
                        'description' => "Thanh toán giao dịch Gia hạn VPS #{$instance->id} (Admin thao tác)",
                        'status' => 'completed',
                    ]);
                }

                Order::create([
                    'user_id' => $user->id,
                    'vps_plan_id' => $instance->vps_plan_id,
                    'vps_instance_id' => $instance->id,
                    'type' => 'renew',
                    'amount' => $customAmount,
                    'billing_cycle' => $request->billing_cycle,
                    'status' => 'pending',
                    'payload' => json_encode(['billing_cycle' => $request->billing_cycle])
                ]);
            });

            return redirect()->route('admin.vps.show', $instance)->with('success', 'Đã tiếp nhận yêu cầu Gia hạn VPS cho khách hàng. Hệ thống đang tiến hành xử lý cấu hình ngầm...');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Lỗi xử lý giao dịch: ' . $e->getMessage());
        }
    }

    public function upgrade(VpsInstance $instance)
    {
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
        
        return view('admin.vps.upgrade', compact('instance', 'addons'));
    }

    public function confirmUpgrade(Request $request, VpsInstance $instance)
    {
        $request->validate([
            'addon_cpu' => 'nullable|integer|min:0',
            'addon_ram' => 'nullable|integer|min:0',
            'addon_disk' => 'nullable|integer|min:0',
            'custom_amount' => 'required|numeric|min:0',
        ]);

        $addonCpu = (int) $request->input('addon_cpu', 0);
        $addonRam = (int) $request->input('addon_ram', 0);
        $addonDisk = (int) $request->input('addon_disk', 0);

        if ($addonCpu == 0 && $addonRam == 0 && $addonDisk == 0) {
            return redirect()->back()->with('error', 'Vui lòng chọn ít nhất một thông số cấu hình để nâng cấp.');
        }

        $customAmount = (float) $request->custom_amount;
        $user = $instance->user;

        if ($customAmount > 0 && $user->balance < $customAmount) {
            return redirect()->back()->with('error', 'Tài khoản khách hàng không đủ số dư để nâng cấp (Yêu cầu: ' . number_format($customAmount) . 'đ).');
        }

        try {
            DB::transaction(function () use ($user, $instance, $addonCpu, $addonRam, $addonDisk, $customAmount) {
                if ($customAmount > 0) {
                    $user->decrement('balance', $customAmount);
                    Transaction::create([
                        'user_id' => $user->id,
                        'type' => 'payment',
                        'amount' => $customAmount,
                        'description' => "Thanh toán Nâng cấp tài nguyên VPS #{$instance->id} (Admin thao tác)",
                        'status' => 'completed',
                    ]);
                }

                Order::create([
                    'user_id' => $user->id,
                    'vps_plan_id' => $instance->vps_plan_id,
                    'vps_instance_id' => $instance->id,
                    'type' => 'upgrade',
                    'amount' => $customAmount,
                    'status' => 'pending',
                    'payload' => json_encode([
                        'addon_cpu' => $addonCpu,
                        'addon_ram' => $addonRam,
                        'addon_disk' => $addonDisk,
                    ])
                ]);
            });

            return redirect()->route('admin.vps.show', $instance)->with('success', 'Đã tiếp nhận thông tin phần cứng Nâng cấp VPS cho khách. Hệ thống đang thực hiện chạy ngầm tiến trình trên Serve...');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Lỗi xử lý tiến trình: ' . $e->getMessage());
        }
    }
}
