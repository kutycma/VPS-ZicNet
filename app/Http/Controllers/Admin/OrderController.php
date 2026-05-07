<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Services\Billing\PaymentService;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['user', 'vpsPlan', 'vpsInstance'])->latest();

        // Lọc theo thông tin user (tên, email)
        if ($request->filled('username')) {
            $keyword = $request->username;
            $query->whereHas('user', function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('email', 'like', "%{$keyword}%");
            });
        }

        // Lọc theo IP của VPS
        if ($request->filled('ip')) {
            $ip = $request->ip;
            $query->whereHas('vpsInstance', function ($q) use ($ip) {
                $q->where('ip_address', 'like', "%{$ip}%");
            });
        }

        // Lọc theo loại hình dịch vụ
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $perPage = $request->input('per_page', 10);
        if (!in_array($perPage, [10, 20, 50])) {
            $perPage = 10;
        }

        $orders = $query->paginate($perPage)->withQueryString();

        return view('admin.orders.index', compact('orders'));
    }

    public function cancel(Order $order, Request $request)
    {
        if ($order->status !== 'pending') {
            return redirect()->back()->with('error', 'Chỉ có thể hủy đơn hàng đang pending.');
        }

        try {
            DB::transaction(function () use ($order) {
                $paymentService = new PaymentService();
                
                // Refund the user
                $paymentService->refund(
                    $order->user, 
                    (float)$order->amount, 
                    "Hoàn tiền - Admin huỷ đơn hàng #{$order->id}"
                );

                // Update order status
                $order->update([
                    'status' => 'refunded',
                    'notes' => 'Admin huỷ đơn & hoàn tiền',
                ]);
            });

            return redirect()->back()->with('success', "Đã huỷ và hoàn tiền " . number_format((float)$order->amount) . "đ cho đơn hàng #{$order->id}.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Lỗi khi hủy đơn hàng: ' . $e->getMessage());
        }
    }
}
