<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\DepositRequest;
use App\Models\PaymentMethod;
use App\Models\Setting;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BillingController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $transactions = $user->transactions()->latest()->paginate(20);

        // Lấy 5 deposit gần nhất
        $recentDeposits = DepositRequest::where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        return view('client.billing.index', compact('user', 'transactions', 'recentDeposits'));
    }

    /**
     * Trang chọn phương thức nạp tiền + nhập số tiền
     */
    public function deposit()
    {
        // Kiểm tra có pending deposit không
        $pendingDeposit = DepositRequest::where('user_id', Auth::id())
            ->where('status', 'pending')
            ->where('expires_at', '>=', now())
            ->first();

        if ($pendingDeposit) {
            return redirect()->route('client.billing.deposit.waiting', $pendingDeposit);
        }

        $paymentMethods = PaymentMethod::active()->orderBy('sort_order')->get();
        return view('client.billing.deposit', compact('paymentMethods'));
    }

    /**
     * Tạo deposit request mới
     */
    public function storeDeposit(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:10000|max:100000000',
            'payment_method_id' => 'required|exists:payment_methods,id',
        ], [
            'amount.required' => 'Vui lòng nhập số tiền',
            'amount.numeric' => 'Số tiền phải là số',
            'amount.min' => 'Số tiền tối thiểu là 10,000đ',
            'amount.max' => 'Số tiền tối đa là 100,000,000đ',
            'payment_method_id.required' => 'Vui lòng chọn phương thức thanh toán',
            'payment_method_id.exists' => 'Phương thức thanh toán không hợp lệ',
        ]);

        $user = Auth::user();

        // Kiểm tra payment method active
        $paymentMethod = PaymentMethod::active()->find($request->payment_method_id);
        if (!$paymentMethod) {
            return redirect()->back()->with('error', 'Phương thức thanh toán không khả dụng.');
        }

        $expiryMinutes = (int) Setting::get('deposit_expiry_minutes', 30);

        try {
            $deposit = \DB::transaction(function () use ($user, $paymentMethod, $request, $expiryMinutes) {
                // Lock pending deposits của user để tránh double-click tạo 2 đơn
                $existingPending = DepositRequest::where('user_id', $user->id)
                    ->where('status', 'pending')
                    ->where('expires_at', '>=', now())
                    ->lockForUpdate()
                    ->first();

                if ($existingPending) {
                    return $existingPending; // Trả về đơn cũ thay vì tạo mới
                }

                return DepositRequest::create([
                    'user_id' => $user->id,
                    'payment_method_id' => $paymentMethod->id,
                    'transaction_code' => DepositRequest::generateTransactionCode(),
                    'amount' => (float) $request->amount,
                    'status' => 'pending',
                    'expires_at' => now()->addMinutes($expiryMinutes),
                ]);
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // Duplicate transaction_code (UNIQUE constraint) → hiếm nhưng possible
            if ($e->errorInfo[1] == 1062) {
                \Log::warning('DepositRequest: Duplicate code caught, retrying', ['user' => $user->id]);
                $deposit = DepositRequest::create([
                    'user_id' => $user->id,
                    'payment_method_id' => $paymentMethod->id,
                    'transaction_code' => DepositRequest::generateTransactionCode(),
                    'amount' => (float) $request->amount,
                    'status' => 'pending',
                    'expires_at' => now()->addMinutes($expiryMinutes),
                ]);
            } else {
                throw $e;
            }
        }

        return redirect()->route('client.billing.deposit.waiting', $deposit);
    }

    /**
     * Trang chờ xác nhận thanh toán
     */
    public function waitingDeposit(DepositRequest $deposit)
    {
        if ($deposit->user_id !== Auth::id()) {
            abort(403);
        }

        if ($deposit->status === 'completed') {
            return redirect()->route('client.billing.index')
                ->with('success', 'Nạp tiền thành công! Số tiền ' . number_format((float) $deposit->actual_amount, 0, ',', '.') . 'đ đã được cộng vào tài khoản.');
        }

        if ($deposit->status === 'pending' && $deposit->expires_at->isPast()) {
            $deposit->update(['status' => 'expired']);
            return redirect()->route('client.billing.deposit')
                ->with('error', 'Yêu cầu nạp tiền đã hết hạn. Vui lòng tạo yêu cầu mới.');
        }

        // Load the specific payment method for this deposit
        $paymentMethod = $deposit->paymentMethod;

        return view('client.billing.waiting', compact('deposit', 'paymentMethod'));
    }

    /**
     * AJAX: Kiểm tra trạng thái deposit
     */
    public function checkDepositStatus(DepositRequest $deposit)
    {
        if ($deposit->user_id !== Auth::id()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $deposit->refresh();

        if ($deposit->status === 'pending' && $deposit->expires_at->isPast()) {
            $deposit->update(['status' => 'expired']);
        }

        return response()->json([
            'status' => $deposit->status,
            'actual_amount' => $deposit->actual_amount,
            'formatted_amount' => $deposit->actual_amount
                ? number_format((float) $deposit->actual_amount, 0, ',', '.') . 'đ'
                : null,
            'new_balance' => $deposit->status === 'completed'
                ? number_format((float) Auth::user()->fresh()->balance, 0, ',', '.') . 'đ'
                : null,
            'remaining_seconds' => $deposit->status === 'pending'
                ? max(0, now()->diffInSeconds($deposit->expires_at, false))
                : 0,
        ]);
    }

    /**
     * Hủy deposit request
     */
    public function cancelDeposit(DepositRequest $deposit)
    {
        if ($deposit->user_id !== Auth::id()) {
            abort(403);
        }

        if ($deposit->status !== 'pending') {
            return redirect()->route('client.billing.index')
                ->with('error', 'Không thể hủy yêu cầu này.');
        }

        $deposit->update(['status' => 'cancelled']);

        return redirect()->route('client.billing.deposit')
            ->with('success', 'Đã hủy yêu cầu nạp tiền.');
    }
}
