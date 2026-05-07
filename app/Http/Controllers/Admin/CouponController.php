<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\VpsPlan;
use App\Models\VpsPlanGroup;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CouponController extends Controller
{
    public function index(Request $request)
    {
        $query = Coupon::with(['plan', 'group', 'user']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where('code', 'like', '%' . $request->search . '%');
        }

        $coupons = $query->latest()->paginate(20)->appends($request->query());

        return view('admin.coupons.index', compact('coupons'));
    }

    public function create()
    {
        $plans = VpsPlan::where('status', 'active')->orderBy('name')->get();
        $groups = VpsPlanGroup::where('status', 'active')->orderBy('sort_order')->get();
        $users = User::orderBy('name')->get();

        return view('admin.coupons.create', compact('plans', 'groups', 'users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'mode' => 'required|in:single,bulk',
            'code' => 'required_if:mode,single|nullable|string|max:50|unique:coupons,code',
            'bulk_prefix' => 'required_if:mode,bulk|nullable|string|max:20',
            'bulk_count' => 'required_if:mode,bulk|nullable|integer|min:1|max:500',
            'bulk_length' => 'nullable|integer|min:4|max:20',
            'description' => 'nullable|string|max:255',
            'type' => 'required|in:fixed,percent',
            'value' => 'required|numeric|min:0.01',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'max_uses_per_user' => 'nullable|integer|min:1',
            'plan_id' => 'nullable|exists:vps_plans,id',
            'group_id' => 'nullable|exists:vps_plan_groups,id',
            'user_id' => 'nullable|exists:users,id',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'status' => 'required|in:active,inactive',
        ]);

        $baseData = [
            'description' => $request->description,
            'type' => $request->type,
            'value' => $request->value,
            'min_order_amount' => $request->min_order_amount ?? 0,
            'max_discount' => $request->max_discount,
            'max_uses' => $request->max_uses,
            'max_uses_per_user' => $request->max_uses_per_user ?? 1,
            'plan_id' => $request->plan_id ?: null,
            'group_id' => $request->group_id ?: null,
            'user_id' => $request->user_id ?: null,
            'starts_at' => $request->starts_at,
            'expires_at' => $request->expires_at,
            'status' => $request->status,
        ];

        if ($request->mode === 'single') {
            // Tạo 1 mã
            Coupon::create(array_merge($baseData, [
                'code' => strtoupper($request->code),
            ]));

            return redirect()->route('admin.coupons.index')
                ->with('success', 'Tạo mã giảm giá thành công: ' . strtoupper($request->code));
        }

        // Tạo mã hàng loạt
        $prefix = strtoupper($request->bulk_prefix);
        $count = (int) $request->bulk_count;
        $length = (int) ($request->bulk_length ?? 8);
        $created = 0;

        for ($i = 0; $i < $count; $i++) {
            $code = $prefix . strtoupper(Str::random($length));

            // Đảm bảo unique
            $attempts = 0;
            while (Coupon::where('code', $code)->exists() && $attempts < 10) {
                $code = $prefix . strtoupper(Str::random($length));
                $attempts++;
            }

            if ($attempts >= 10) continue;

            Coupon::create(array_merge($baseData, [
                'code' => $code,
            ]));
            $created++;
        }

        return redirect()->route('admin.coupons.index')
            ->with('success', "Đã tạo {$created}/{$count} mã giảm giá với tiền tố [{$prefix}]");
    }

    public function edit(Coupon $coupon)
    {
        $plans = VpsPlan::where('status', 'active')->orderBy('name')->get();
        $groups = VpsPlanGroup::where('status', 'active')->orderBy('sort_order')->get();
        $users = User::orderBy('name')->get();

        return view('admin.coupons.edit', compact('coupon', 'plans', 'groups', 'users'));
    }

    public function update(Request $request, Coupon $coupon)
    {
        $request->validate([
            'description' => 'nullable|string|max:255',
            'type' => 'required|in:fixed,percent',
            'value' => 'required|numeric|min:0.01',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'max_uses_per_user' => 'nullable|integer|min:1',
            'plan_id' => 'nullable|exists:vps_plans,id',
            'group_id' => 'nullable|exists:vps_plan_groups,id',
            'user_id' => 'nullable|exists:users,id',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'status' => 'required|in:active,inactive',
        ]);

        $coupon->update([
            'description' => $request->description,
            'type' => $request->type,
            'value' => $request->value,
            'min_order_amount' => $request->min_order_amount ?? 0,
            'max_discount' => $request->max_discount,
            'max_uses' => $request->max_uses,
            'max_uses_per_user' => $request->max_uses_per_user,
            'plan_id' => $request->plan_id ?: null,
            'group_id' => $request->group_id ?: null,
            'user_id' => $request->user_id ?: null,
            'starts_at' => $request->starts_at,
            'expires_at' => $request->expires_at,
            'status' => $request->status,
        ]);

        return redirect()->route('admin.coupons.index')
            ->with('success', 'Cập nhật mã giảm giá thành công');
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();
        return redirect()->route('admin.coupons.index')
            ->with('success', 'Đã xóa mã giảm giá: ' . $coupon->code);
    }

    /**
     * Toggle active/inactive
     */
    public function toggleStatus(Coupon $coupon)
    {
        $coupon->update(['status' => $coupon->status === 'active' ? 'inactive' : 'active']);
        return redirect()->back()
            ->with('success', $coupon->status === 'active' ? 'Đã kích hoạt mã' : 'Đã vô hiệu hóa mã');
    }
}
