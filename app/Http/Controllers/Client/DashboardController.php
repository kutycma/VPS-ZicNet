<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\VpsInstance;
use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $stats = [
            'balance' => $user->balance,
            'total_vps' => $user->vpsInstances()->where('status', '!=', 'deleted')->count(),
            'active_vps' => $user->vpsInstances()->where('status', 'active')
                ->where(function($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
                })->count(),
            'total_spent' => $user->orders()->where('status', 'completed')->sum('amount'),
        ];

        $recentVps = $user->vpsInstances()->with('plan')->latest()->limit(5)->get();
        $recentTransactions = $user->transactions()->latest()->limit(5)->get();

        return view('client.dashboard', compact('stats', 'recentVps', 'recentTransactions'));
    }
}
