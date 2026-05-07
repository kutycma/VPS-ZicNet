<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VpsPlanGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VpsPlanGroupController extends Controller
{
    public function index()
    {
        $groups = VpsPlanGroup::withCount('plans')
            ->orderBy('sort_order')
            ->paginate(20);

        return view('admin.plan-groups.index', compact('groups'));
    }

    public function create()
    {
        return view('admin.plan-groups.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer',
            'status' => 'required|in:active,inactive',
        ]);

        VpsPlanGroup::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'icon' => $request->icon ?: 'fas fa-server',
            'sort_order' => $request->sort_order ?? 0,
            'status' => $request->status,
        ]);

        return redirect()->route('admin.plan-groups.index')->with('success', 'Tạo nhóm VPS thành công');
    }

    public function edit(VpsPlanGroup $planGroup)
    {
        return view('admin.plan-groups.edit', compact('planGroup'));
    }

    public function update(Request $request, VpsPlanGroup $planGroup)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer',
            'status' => 'required|in:active,inactive',
        ]);

        $planGroup->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'icon' => $request->icon ?: 'fas fa-server',
            'sort_order' => $request->sort_order ?? 0,
            'status' => $request->status,
        ]);

        return redirect()->route('admin.plan-groups.index')->with('success', 'Cập nhật nhóm VPS thành công');
    }

    public function destroy(VpsPlanGroup $planGroup)
    {
        if ($planGroup->plans()->count() > 0) {
            return redirect()->back()->with('error', 'Không thể xóa nhóm đang có gói VPS');
        }

        $planGroup->delete();
        return redirect()->route('admin.plan-groups.index')->with('success', 'Xóa nhóm VPS thành công');
    }
}
