<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VpsPlan;
use App\Models\VpsProvider;
use Illuminate\Http\Request;

class VpsPlanController extends Controller
{
    public function index(Request $request)
    {
        $query = VpsPlan::with(['provider', 'group']);

        if ($request->filled('provider_id')) {
            $query->where('provider_id', $request->provider_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $plans = $query->orderBy('sort_order')->orderBy('selling_price')->paginate(20);
        $providers = VpsProvider::all();

        return view('admin.plans.index', compact('plans', 'providers'));
    }

    public function create()
    {
        $providers = VpsProvider::where('status', 'active')->get();
        return view('admin.plans.create', compact('providers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'provider_id' => 'required|exists:vps_providers,id',
            'name' => 'required|string|max:255',
            'type' => 'required|in:vps_vn,vps_nn',
            'cpu_cores' => 'required|integer|min:1',
            'ram_mb' => 'required|integer|min:512',
            'disk_gb' => 'required|integer|min:10',
            'provider_price' => 'required|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
        ]);

        $data = $request->all();

        // Auto-calculate selling price nếu không nhập tay
        if (empty($data['selling_price']) || $data['selling_price'] == 0) {
            $provider = VpsProvider::find($request->provider_id);
            $data['selling_price'] = $provider->calculateSellingPrice($request->provider_price);
        }

        VpsPlan::create($data);

        return redirect()->route('admin.plans.index')->with('success', 'Thêm gói VPS thành công');
    }

    public function edit(VpsPlan $plan)
    {
        $providers = VpsProvider::where('status', 'active')->get();
        return view('admin.plans.edit', compact('plan', 'providers'));
    }

    public function update(Request $request, VpsPlan $plan)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:vps_vn,vps_nn',
            'cpu_cores' => 'required|integer|min:1',
            'ram_mb' => 'required|integer|min:512',
            'disk_gb' => 'required|integer|min:10',
            'provider_price' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive',
            'pricing' => 'nullable|array',
            'group_id' => 'nullable|exists:vps_plan_groups,id',
        ]);

        // Build pricing_data from form
        $pricingInput = $request->input('pricing', []);
        $existingPricing = $plan->pricing_data ?? [];
        $pricingData = [];

        foreach (VpsPlan::BILLING_CYCLES as $key => $meta) {
            $provPrice = null;
            $sellPrice = null;

            // Lấy từ form
            if (isset($pricingInput[$key])) {
                $provPrice = $pricingInput[$key]['provider_price'] ?? null;
                $sellPrice = $pricingInput[$key]['selling_price'] ?? null;
            }

            // Bỏ qua chu kỳ hoàn toàn trống
            if (($provPrice === null || $provPrice === '') && ($sellPrice === null || $sellPrice === '')) {
                // Giữ lại dữ liệu cũ nếu có
                if (isset($existingPricing[$key]) && !empty($existingPricing[$key]['provider_price'])) {
                    $pricingData[$key] = $existingPricing[$key];
                }
                continue;
            }

            $provPriceNum = (float) ($provPrice ?: 0);
            $sellPriceNum = (float) ($sellPrice ?: 0);

            // Auto-calculate selling nếu trống
            if ($sellPriceNum <= 0 && $provPriceNum > 0) {
                $sellPriceNum = $plan->provider->calculateSellingPrice($provPriceNum);
            }

            $pricingData[$key] = [
                'label' => $meta['label'],
                'provider_price' => $provPriceNum,
                'selling_price' => $sellPriceNum,
                'sort' => $meta['sort'],
            ];
        }

        // selling_price field = monthly selling_price (compat)
        $monthlySellingPrice = $pricingData['monthly']['selling_price'] ?? 0;
        if ($monthlySellingPrice <= 0) {
            // Fallback: giá bán đầu tiên có giá trị
            foreach ($pricingData as $c) {
                if (!empty($c['selling_price']) && $c['selling_price'] > 0) {
                    $monthlySellingPrice = $c['selling_price'];
                    break;
                }
            }
        }

        $plan->update([
            'name' => $request->name,
            'type' => $request->type,
            'group_id' => $request->group_id ?: null,
            'cpu_cores' => $request->cpu_cores,
            'ram_mb' => $request->ram_mb,
            'disk_gb' => $request->disk_gb,
            'bandwidth_mbps' => $request->bandwidth_mbps,
            'provider_plan_id' => $request->provider_plan_id,
            'provider_price' => $request->provider_price,
            'selling_price' => $monthlySellingPrice,
            'pricing_data' => $pricingData,
            'status' => $request->status,
            'sort_order' => $request->sort_order,
            'description' => $request->description,
        ]);

        return redirect()->route('admin.plans.index')->with('success', 'Cập nhật gói VPS thành công');
    }

    public function destroy(VpsPlan $plan)
    {
        if ($plan->instances()->count() > 0) {
            return redirect()->back()->with('error', 'Không thể xóa gói VPS đang có instances hoạt động');
        }

        $plan->delete();
        return redirect()->route('admin.plans.index')->with('success', 'Xóa gói VPS thành công');
    }

    /**
     * Nhanh chóng Ẩn/Hiện gói VPS (Toggle status)
     */
    public function toggleStatus(VpsPlan $plan)
    {
        $plan->status = $plan->status === 'active' ? 'inactive' : 'active';
        $plan->save();

        $message = $plan->status === 'active' ? 'Đã hiện gói VPS này!' : 'Đã ẩn gói VPS này!';
        return redirect()->back()->with('success', $message);
    }

    /**
     * API: Tính giá bán tự động (AJAX)
     */
    public function calculatePrice(Request $request)
    {
        $provider = VpsProvider::find($request->provider_id);
        if (!$provider) {
            return response()->json(['selling_price' => 0]);
        }

        $sellingPrice = $provider->calculateSellingPrice((float) $request->provider_price);
        return response()->json(['selling_price' => $sellingPrice]);
    }
}
