<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VpsProvider;
use App\Services\Vps\VpsManager;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VpsProviderController extends Controller
{
    public function index()
    {
        $providers = VpsProvider::withCount('plans')->get();
        return view('admin.providers.index', compact('providers'));
    }

    public function create()
    {
        $planGroups = \App\Models\VpsPlanGroup::orderBy('sort_order')->get();
        return view('admin.providers.create', compact('planGroups'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'default_group_id' => 'nullable|exists:vps_plan_groups,id',
            'name' => 'required|string|max:255',
            'api_endpoint' => 'required|url',
            'api_username' => 'required|string',
            'api_app' => 'required|string',
            'api_secret' => 'required|string',
            'markup_type' => 'required|in:percentage,fixed,manual',
            'markup_value' => 'required|numeric|min:0',
        ]);

        $data = $request->all();
        $data['slug'] = Str::slug($request->name);

        VpsProvider::create($data);

        return redirect()->route('admin.providers.index')->with('success', 'Thêm nhà cung cấp thành công');
    }

    public function edit(VpsProvider $provider)
    {
        $planGroups = \App\Models\VpsPlanGroup::orderBy('sort_order')->get();
        return view('admin.providers.edit', compact('provider', 'planGroups'));
    }

    public function update(Request $request, VpsProvider $provider)
    {
        $request->validate([
            'default_group_id' => 'nullable|exists:vps_plan_groups,id',
            'name' => 'required|string|max:255',
            'api_endpoint' => 'required|url',
            'markup_type' => 'required|in:percentage,fixed,manual',
            'markup_value' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        $data = $request->except(['_token', '_method']);
        if ($request->filled('api_secret')) {
            $data['api_secret'] = $request->api_secret;
        } else {
            unset($data['api_secret']);
        }

        $provider->update($data);

        return redirect()->route('admin.providers.index')->with('success', 'Cập nhật nhà cung cấp thành công');
    }

    public function destroy(VpsProvider $provider)
    {
        $provider->delete();
        return redirect()->route('admin.providers.index')->with('success', 'Xóa nhà cung cấp thành công');
    }

    /**
     * Test kết nối API
     */
    public function testConnection(VpsProvider $provider)
    {
        try {
            $manager = new VpsManager();
            $driver = $manager->resolveProvider($provider);

            $startTime = microtime(true);
            $token = $driver->authenticate();
            $duration = round((microtime(true) - $startTime) * 1000);

            return response()->json([
                'success' => true,
                'message' => 'Kết nối thành công!',
                'details' => [
                    'token' => substr($token, 0, 20) . '...',
                    'endpoint' => $provider->api_endpoint,
                    'username' => $provider->api_username,
                    'response_time' => $duration . 'ms',
                ],
            ]);
        } catch (\Exception $e) {
            // Đọc log gần nhất để xem chi tiết request/response
            $recentLogs = '';
            $logFile = storage_path('logs/laravel.log');
            if (file_exists($logFile)) {
                $lines = array_slice(file($logFile), -15);
                $relevant = array_filter($lines, function ($line) {
                    return strpos($line, 'H2Cloud') !== false;
                });
                $recentLogs = implode('', array_slice($relevant, -5));
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'details' => [
                    'error_type' => get_class($e),
                    'endpoint' => $provider->api_endpoint,
                    'username' => $provider->api_username,
                    'app' => $provider->api_app,
                    'file' => basename($e->getFile()) . ':' . $e->getLine(),
                    'recent_log' => trim($recentLogs) ?: 'Không có log',
                ],
            ]);
        }
    }

    /**
     * Đồng bộ gói VPS từ provider
     */
    public function syncPlans(VpsProvider $provider)
    {
        try {
            $manager = new VpsManager();
            $count = $manager->syncPlans($provider);

            return redirect()->back()->with('success', "Đồng bộ thành công {$count} gói VPS");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Lỗi đồng bộ gói VPS: ' . $e->getMessage());
        }
    }

    /**
     * Đồng bộ Các Máy chủ ảo (VPS) đang chạy trên provider về Database nội bộ
     */
    public function syncInstances(VpsProvider $provider)
    {
        try {
            $manager = new VpsManager();
            $count = $manager->syncInstances($provider);

            return redirect()->back()->with('success', "Đã đồng bộ xong. Có {$count} VPS mới được kéo về DB (tự động gắn cho Admin).");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Lỗi đồng bộ danh sách VPS: ' . $e->getMessage());
        }
    }

    /**
     * Xem thông tin đại lý trên H2Cloud (balance)
     */
    public function agencyInfo(VpsProvider $provider)
    {
        try {
            $manager = new VpsManager();
            $info = $manager->getAgencyInfo($provider);
            return response()->json(['success' => true, 'data' => $info]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
