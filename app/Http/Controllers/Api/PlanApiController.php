<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VpsPlan;
use App\Models\VpsPlanGroup;
use App\Services\Vps\VpsManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PlanApiController extends Controller
{
    /**
     * GET /api/plan-groups
     * Public — list of active plan groups/tabs.
     */
    public function groups()
    {
        $groups = VpsPlanGroup::where('status', 'active')
            ->orderBy('sort_order')
            ->get()
            ->map(fn($g) => [
                'id'         => $g->id,
                'name'       => $g->name,
                'slug'       => $g->slug,
                'sort_order' => $g->sort_order,
            ]);

        return response()->json($groups);
    }

    /**
     * GET /api/plans?group={slug}
     * Public — list active plans, grouped by type (vps_hn / vps_nn).
     */
    public function index(Request $request)
    {
        $query = VpsPlan::with(['provider', 'group'])
            ->where('status', 'active')
            ->whereHas('provider', fn($q) => $q->where('status', 'active'));

        if ($selectedGroup = $request->query('group')) {
            $groupModel = VpsPlanGroup::where('slug', $selectedGroup)->first();
            if ($groupModel) {
                $query->where('group_id', $groupModel->id);
            }
        }

        $plans = $query
            ->orderBy('sort_order')
            ->orderBy('type')
            ->orderBy('selling_price')
            ->get()
            ->map(fn($p) => $this->formatPlan($p));

        // Group by type
        $grouped = $plans->groupBy('type');

        return response()->json([
            'plans'   => $grouped,
            'all'     => $plans->values(),
        ]);
    }

    /**
     * GET /api/plans/{plan}
     * Public — plan detail with billing cycles and available OS list.
     */
    public function show(VpsPlan $plan)
    {
        if (!$plan->isActive()) {
            return response()->json(['message' => 'Plan not available.'], 404);
        }

        $operatingSystems = [];
        $states = [];

        try {
            $manager = new VpsManager();
            $driver  = $manager->resolveProvider($plan->provider);

            $operatingSystems = $driver->getOperatingSystems();

            if ($plan->type === 'vps_nn') {
                $states = $driver->getStates();
            }
        } catch (\Exception $e) {
            Log::error('PlanApiController@show: ' . $e->getMessage());
        }

        return response()->json([
            'plan'              => $this->formatPlan($plan),
            'billing_cycles'    => $plan->sorted_pricing,
            'operating_systems' => $operatingSystems,
            'states'            => $states,
        ]);
    }

    private function formatPlan(VpsPlan $plan): array
    {
        return [
            'id'                 => $plan->id,
            'name'               => $plan->name,
            'type'               => $plan->type,
            'type_label'         => $plan->type === 'vps_hn' ? 'VPS Hà Nội' : 'VPS Nước Ngoài',
            'cpu_cores'          => $plan->cpu_cores,
            'ram_mb'             => $plan->ram_mb,
            'ram_gb'             => $plan->ram_gb,
            'disk_gb'            => $plan->disk_gb,
            'bandwidth_mbps'     => $plan->bandwidth_mbps,
            'display_price'      => (float) $plan->display_price,
            'display_price_formatted' => number_format((float) $plan->display_price, 0, ',', '.') . 'đ',
            'display_price_cycle'=> $plan->display_price_cycle,
            'pricing'            => $plan->sorted_pricing,
            'status'             => $plan->status,
            'description'        => $plan->description,
            'group'              => $plan->group ? [
                'id'   => $plan->group->id,
                'name' => $plan->group->name,
                'slug' => $plan->group->slug,
            ] : null,
        ];
    }
}
