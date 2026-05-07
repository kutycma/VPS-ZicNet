<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\VpsPlan;
use App\Models\VpsInstance;
use App\Models\Order;
use App\Services\Billing\PaymentService;
use App\Services\Vps\VpsManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Telegram\TelegramService;
use App\Models\Coupon;

class OrderController extends Controller
{
    /**
     * Hiển thị danh sách gói VPS có thể mua
     */
    public function plans(Request $request)
    {
        $groups = \App\Models\VpsPlanGroup::where('status', 'active')
            ->orderBy('sort_order')
            ->get();

        $selectedGroup = $request->query('group'); // slug or null

        $query = VpsPlan::with(['provider', 'group'])
            ->where('status', 'active')
            ->whereHas('provider', function ($q) {
                $q->where('status', 'active');
            });

        // Filter by group if selected
        if ($selectedGroup) {
            $groupModel = \App\Models\VpsPlanGroup::where('slug', $selectedGroup)->first();
            if ($groupModel) {
                $query->where('group_id', $groupModel->id);
            }
        }

        $plans = $query
            ->orderBy('sort_order')
            ->orderBy('type')
            ->orderBy('selling_price')
            ->get()
            ->groupBy('type');

        return view('client.orders.plans', compact('plans', 'groups', 'selectedGroup'));
    }

    /**
     * Form tạo đơn hàng mới
     */
    public function create(VpsPlan $plan)
    {
        if (!$plan->isActive()) {
            return redirect()->route('client.orders.plans')->with('error', 'Gói VPS này không khả dụng');
        }

        // Billing cycles từ pricing_data đã sort theo thứ tự chu kỳ
        $billingCycles = $plan->sorted_pricing;

        // Lấy OS, states, addons từ provider API
        $operatingSystems = [];
        $states = [];
        $addons = [];

        try {
            $manager = new VpsManager();
            $driver = $manager->resolveProvider($plan->provider);

            $operatingSystems = $driver->getOperatingSystems();

            if ($plan->type === 'vps_nn') {
                $states = $driver->getStates();
            }

            // Lấy addon products: "Mua thêm CPU/RAM/DISK"
            $allProducts = $driver->getProducts();
            foreach ($allProducts as $product) {
                $productName = $product['name'] ?? '';
                if (
                    stripos($productName, 'Mua thêm') !== false ||
                    stripos($productName, 'addon') !== false ||
                    stripos($productName, 'add-on') !== false
                ) {
                    $addonPricing = $product['pricing'] ?? [];
                    $addonMonthly = isset($addonPricing['monthly']['amount']) ? $addonPricing['monthly']['amount'] : 0;
                    $addons[] = [
                        'product_id' => $product['product_id'],
                        'name' => $productName,
                        'price' => $addonMonthly,
                        'pricing' => $addonPricing,
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error('Error fetching provider options: ' . $e->getMessage());
        }

        $user = Auth::user();

        return view('client.orders.create', compact('plan', 'operatingSystems', 'billingCycles', 'states', 'addons', 'user'));
    }

    /**
     * Xử lý mua VPS - Luồng chính
     */
    public function store(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:vps_plans,id',
            'billing_cycle' => 'required|string',
            'os' => 'nullable',
            'quantity' => 'required|integer|min:1|max:50',
            'state' => 'nullable|string',
            'addon' => 'nullable|array',
            'addon.*' => 'nullable|integer|min:0',
            'addon_type' => 'nullable|array',
            'addon_type.*' => 'nullable|string',
            'coupon_code' => 'nullable|string|max:50',
        ]);

        $plan = VpsPlan::with('provider')->findOrFail($request->plan_id);
        $user = Auth::user();

        // Lấy giá bán theo billing cycle đã chọn
        $pricingData = $plan->sorted_pricing;
        $selectedCycle = $request->billing_cycle;
        if (isset($pricingData[$selectedCycle]['selling_price'])) {
            $unitPrice = (float) $pricingData[$selectedCycle]['selling_price'];
        } else {
            $unitPrice = (float) $plan->display_price; // fallback giá hiển thị
        }

        $totalAmount = $unitPrice * $request->quantity;

        // === Coupon processing ===
        $coupon = null;
        $couponDiscount = 0;
        if ($request->filled('coupon_code')) {
            $coupon = Coupon::where('code', strtoupper(trim($request->coupon_code)))->first();
            if ($coupon && $coupon->canBeUsedBy($user) && $coupon->appliesTo($plan)) {
                $couponDiscount = $coupon->calculateDiscount($totalAmount);
                $totalAmount = max(0, $totalAmount - $couponDiscount);
            } else {
                $coupon = null; // Invalid coupon, ignore
            }
        }

        // Kiểm tra balance
        if ($user->balance < $totalAmount) {
            return redirect()->back()->with('error',
                'Số dư không đủ. Cần ' . number_format((float)$totalAmount, 0, ',', '.') . 'đ, hiện có ' . number_format((float)$user->balance, 0, ',', '.') . 'đ'
            );
        }

        try {
            return DB::transaction(function () use ($plan, $user, $request, $totalAmount, $coupon, $couponDiscount) {
                $paymentService = new PaymentService();
                $vpsManager = new VpsManager();

                // 1. Trừ tiền
                $transaction = $paymentService->purchase(
                    $user,
                    $totalAmount,
                    "Mua {$request->quantity}x {$plan->name}" . ($coupon ? " (Mã GG: {$coupon->code})" : '')
                );

                // 2. Tạo Order với status pending và lưu payload
                $addons = $request->input('addon', []);
                $params = [
                    'billing_cycle' => $request->billing_cycle,
                    'os' => $request->os ?? '',
                    'quantity' => $request->quantity,
                    'addon_cpu' => 0,
                    'addon_ram' => 0,
                    'addon_disk' => 0,
                    'state' => $request->state ?? '',
                    'coupon_code' => $coupon ? $coupon->code : null,
                    'coupon_discount' => $couponDiscount,
                ];

                foreach ($addons as $productId => $qty) {
                    $qty = (int) $qty;
                    if ($qty <= 0) continue;

                    $addonType = $request->input("addon_type.{$productId}", '');
                    if (stripos($addonType, 'cpu') !== false) {
                        $params['addon_cpu'] = $qty;
                    } elseif (stripos($addonType, 'ram') !== false) {
                        $params['addon_ram'] = $qty;
                    } elseif (stripos($addonType, 'disk') !== false) {
                        $params['addon_disk'] = $qty;
                    }
                }

                $order = Order::create([
                    'user_id' => $user->id,
                    'vps_plan_id' => $plan->id,
                    'amount' => $totalAmount,
                    'billing_cycle' => $request->billing_cycle,
                    'duration_months' => 1,
                    'type' => 'new',
                    'status' => 'pending',
                    'payload' => $params,
                ]);

                // 3. Ghi nhận sử dụng coupon
                if ($coupon && $couponDiscount > 0) {
                    $coupon->recordUsage($user, $order->id, $couponDiscount);
                }

                // Thông báo ngay cho User là đã tiếp nhận đơn
                return redirect()->route('client.orders.history')->with('success', 'Đã tiếp nhận yêu cầu đặt mua VPS! Đơn hàng của bạn đang được hệ thống tự động khởi tạo trong ít phút.');
            });
        } catch (\Exception $e) {
            Log::error('Order process failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Lỗi xử lý đơn hàng: ' . $e->getMessage());
        }
    }

    /**
     * Lịch sử đơn hàng
     */
    public function history(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        if (!in_array($perPage, [10, 20, 50])) {
            $perPage = 10;
        }

        $orders = Auth::user()->orders()
            ->with(['vpsPlan', 'vpsInstance'])
            ->latest()
            ->paginate($perPage)
            ->appends($request->query());

        return view('client.orders.history', compact('orders', 'perPage'));
    }

    /**
     * AJAX: Validate & calculate coupon discount
     */
    public function applyCoupon(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:50',
            'plan_id' => 'required|exists:vps_plans,id',
            'amount' => 'required|numeric|min:0',
        ]);

        $user = Auth::user();
        $plan = VpsPlan::find($request->plan_id);
        $code = strtoupper(trim($request->code));
        $amount = (float) $request->amount;

        $coupon = Coupon::where('code', $code)->first();

        if (!$coupon) {
            return response()->json(['valid' => false, 'message' => 'Mã giảm giá không tồn tại']);
        }

        if (!$coupon->canBeUsedBy($user)) {
            if (!$coupon->isValid()) {
                return response()->json(['valid' => false, 'message' => 'Mã giảm giá đã hết hạn hoặc hết lượt sử dụng']);
            }
            if ($coupon->user_id && $coupon->user_id !== $user->id) {
                return response()->json(['valid' => false, 'message' => 'Mã giảm giá không dành cho bạn']);
            }
            return response()->json(['valid' => false, 'message' => 'Bạn đã dùng hết lượt sử dụng mã này']);
        }

        if (!$coupon->appliesTo($plan)) {
            return response()->json(['valid' => false, 'message' => 'Mã giảm giá không áp dụng cho gói VPS này']);
        }

        $discount = $coupon->calculateDiscount($amount);

        if ($discount <= 0) {
            $minOrder = number_format((float)$coupon->min_order_amount, 0, ',', '.');
            return response()->json(['valid' => false, 'message' => "Đơn hàng tối thiểu {$minOrder}đ để dùng mã này"]);
        }

        $discountDisplay = number_format($discount, 0, ',', '.');
        $message = "Giảm {$discountDisplay}đ" . ($coupon->type === 'percent' ? " ({$coupon->value}%)" : '');

        return response()->json([
            'valid' => true,
            'discount' => $discount,
            'message' => $message,
        ]);
    }
}
