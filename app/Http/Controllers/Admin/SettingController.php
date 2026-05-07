<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\Setting\SystemSettingService;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->groupBy('group');
        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $settingService = new SystemSettingService();
        $settingService->updateMany($request->except('_token', '_method'), $request->get('group', 'general'));

        return redirect()->back()->with('success', 'Cập nhật cài đặt thành công');
    }
}
