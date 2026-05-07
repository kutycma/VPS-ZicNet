<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VpsInstance;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;

class DashboardApiController extends Controller
{
    /**
     * GET /api/dashboard
     */
    public function index()
    {
        $user = Auth::user();

        $activeVps = $user->vpsInstances()->where('status', 'active')->where(function ($q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
        })->count();

        $expiringVps = $user->vpsInstances()->where('status', 'active')
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays(7)])
            ->count();

        $pendingOrders = Order::where('user_id', $user->id)->where('status', 'pending')->count();

        $totalSpent = Order::where('user_id', $user->id)
            ->where('status', '!=', 'cancelled')
            ->sum('amount');

        $recentOrders = Order::where('user_id', $user->id)
            ->with('vpsPlan')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($o) {
                return [
                    'id'     => $o->id,
                    'plan'   => $o->vpsPlan ? $o->vpsPlan->name : null,
                    'amount' => number_format((float) $o->amount, 0, ',', '.') . 'đ',
                    'status' => $o->status,
                    'type'   => $o->type,
                    'date'   => $o->created_at ? $o->created_at->format('d/m/Y') : null,
                ];
            });

        return response()->json([
            'user' => [
                'name'              => $user->name,
                'balance'           => (float) $user->balance,
                'balance_formatted' => number_format((float) $user->balance, 0, ',', '.') . 'đ',
                'avatar_url'        => $user->getAvatarUrl(128),
            ],
            'stats' => [
                'active_vps'            => $activeVps,
                'expiring_vps'          => $expiringVps,
                'pending_orders'        => $pendingOrders,
                'total_spent'           => (float) $totalSpent,
                'total_spent_formatted' => number_format((float) $totalSpent, 0, ',', '.') . 'đ',
            ],
            'recent_orders' => $recentOrders->values()->all(),
        ]);
    }
}
