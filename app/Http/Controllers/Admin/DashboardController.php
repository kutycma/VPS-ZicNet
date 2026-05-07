<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VpsInstance;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\VpsProvider;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users' => User::where('role', 'user')->count(),
            'total_vps' => VpsInstance::where('status', '!=', 'deleted')->count(),
            'active_vps' => VpsInstance::where('status', 'active')
                ->where(function($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
                })->count(),
            'total_revenue' => Order::where('status', 'completed')->sum('amount'),
            'total_deposits' => Transaction::where('type', 'deposit')->sum('amount'),
            'pending_orders' => Order::where('status', 'pending')->count(),
        ];

        $recentOrders = Order::with(['user', 'vpsPlan'])->latest()->limit(10)->get();
        $recentTransactions = Transaction::with('user')->latest()->limit(10)->get();

        // Lấy thông tin đại lý H2Cloud nếu có
        $h2cloudInfo = null;
        $provider = VpsProvider::where('slug', 'h2cloud')->where('status', 'active')->first();
        if ($provider) {
            try {
                $vpsManager = new \App\Services\Vps\VpsManager();
                $h2cloudInfo = $vpsManager->getAgencyInfo($provider);
            } catch (\Exception $e) {
                $h2cloudInfo = ['error' => $e->getMessage()];
            }
        }

        return view('admin.dashboard', compact('stats', 'recentOrders', 'recentTransactions', 'h2cloudInfo'));
    }
}
