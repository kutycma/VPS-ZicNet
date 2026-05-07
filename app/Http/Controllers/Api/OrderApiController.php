<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VpsPlan;
use App\Models\Order;
use App\Services\Billing\PaymentService;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderApiController extends Controller
{
    /**
     * GET /api/orders?per_page=10
     */
    public function index(Request $request)
    {
        $perPage = in_array($request->input('per_page'), [10, 20, 50]) ? (int)$request->input('per_page') : 10;

        $orders = Auth::user()->orders()
            ->with(['vpsPlan', 'vpsInstance'])
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'data' => array_map(function ($o) { return $this->formatOrder($o); }, $orders->items()),
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page'    => $orders->lastPage(),
                'per_page'     => $orders->perPage(),
                'total'        => $orders->total(),
            ],
        ]);
    }

    /**
     * POST /api/orders
     */
    public function store(Request $request)
    {
        $request->validate([
            'plan_id'       => 'required|exists:vps_plans,id',
            'billing_cycle' => 'required|string',
            'os'            => 'nullable|string',
            'quantity'      => 'required|integer|min:1|max:50',
            'state'         => 'nullable|string',
            'coupon_code'   => 'nullable|string|max:50',
        ]);

        $plan = VpsPlan::with('provider')->findOrFail($request->plan_id);
        $user = Auth::user();

        $pricingData   = $plan->sorted_pricing;
        $selectedCycle = $request->billing_cycle;
        $unitPrice = isset($pricingData[$selectedCycle]['selling_price'])
            ? (float) $pricingData[$selectedCycle]['selling_price']
            : (float) $plan->display_price;

        $totalAmount    = $unitPrice * $request->quantity;
        $coupon         = null;
        $couponDiscount = 0;

        if ($request->filled('coupon_code')) {
            $coupon = Coupon::where('code', strtoupper(trim($request->coupon_code)))->first();
            if ($coupon && $coupon->canBeUsedBy($user) && $coupon->appliesTo($plan)) {
                $couponDiscount = $coupon->calculateDiscount($totalAmount);
                $totalAmount    = max(0, $totalAmount - $couponDiscount);
            } else {
                $coupon = null;
            }
        }

        if ($user->balance < $totalAmount) {
            return response()->json([
                'message' => 'Insufficient balance. Need ' . number_format($totalAmount, 0, ',', '.') . 'đ, have ' . number_format((float)$user->balance, 0, ',', '.') . 'đ',
            ], 422);
        }

        try {
            return DB::transaction(function () use ($plan, $user, $request, $totalAmount, $coupon, $couponDiscount, $selectedCycle) {
                (new PaymentService())->purchase(
                    $user,
                    $totalAmount,
                    "Mua {$request->quantity}x {$plan->name}" . ($coupon ? " (Mã: {$coupon->code})" : '')
                );

                $order = Order::create([
                    'user_id'         => $user->id,
                    'vps_plan_id'     => $plan->id,
                    'amount'          => $totalAmount,
                    'billing_cycle'   => $selectedCycle,
                    'duration_months' => 1,
                    'type'            => 'new',
                    'status'          => 'pending',
                    'payload'         => [
                        'billing_cycle'   => $selectedCycle,
                        'os'              => $request->os ?? '',
                        'quantity'        => $request->quantity,
                        'state'           => $request->state ?? '',
                        'coupon_code'     => $coupon ? $coupon->code : null,
                        'coupon_discount' => $couponDiscount,
                        'addon_cpu'       => 0,
                        'addon_ram'       => 0,
                        'addon_disk'      => 0,
                    ],
                ]);

                if ($coupon && $couponDiscount > 0) {
                    $coupon->recordUsage($user, $order->id, $couponDiscount);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Order placed successfully. Your VPS will be provisioned shortly.',
                    'order'   => $this->formatOrder($order),
                ], 201);
            });
        } catch (\Exception $e) {
            Log::error('OrderApi store failed: ' . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/orders/apply-coupon
     */
    public function applyCoupon(Request $request)
    {
        $request->validate([
            'code'    => 'required|string|max:50',
            'plan_id' => 'required|exists:vps_plans,id',
            'amount'  => 'required|numeric|min:0',
        ]);

        $user   = Auth::user();
        $plan   = VpsPlan::find($request->plan_id);
        $code   = strtoupper(trim($request->code));
        $amount = (float) $request->amount;
        $coupon = Coupon::where('code', $code)->first();

        if (!$coupon) {
            return response()->json(['valid' => false, 'message' => 'Coupon not found.']);
        }
        if (!$coupon->canBeUsedBy($user)) {
            return response()->json(['valid' => false, 'message' => 'Coupon cannot be used.']);
        }
        if (!$coupon->appliesTo($plan)) {
            return response()->json(['valid' => false, 'message' => 'Coupon does not apply to this plan.']);
        }

        $discount = $coupon->calculateDiscount($amount);
        if ($discount <= 0) {
            return response()->json([
                'valid'   => false,
                'message' => 'Minimum order: ' . number_format((float)$coupon->min_order_amount, 0, ',', '.') . 'đ',
            ]);
        }

        return response()->json([
            'valid'    => true,
            'discount' => $discount,
            'message'  => 'Discount: ' . number_format($discount, 0, ',', '.') . 'đ' . ($coupon->type === 'percent' ? " ({$coupon->value}%)" : ''),
        ]);
    }

    private function formatOrder(Order $order): array
    {
        return [
            'id'               => $order->id,
            'amount'           => (float) $order->amount,
            'amount_formatted' => number_format((float) $order->amount, 0, ',', '.') . 'đ',
            'billing_cycle'    => $order->billing_cycle,
            'type'             => $order->type,
            'status'           => $order->status,
            'plan'             => $order->vpsPlan ? ['id' => $order->vpsPlan->id, 'name' => $order->vpsPlan->name] : null,
            'vps_instance_id'  => $order->vps_instance_id,
            'created_at'       => $order->created_at ? $order->created_at->toISOString() : null,
        ];
    }
}
