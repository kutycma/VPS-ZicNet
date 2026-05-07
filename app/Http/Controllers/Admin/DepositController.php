<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DepositRequest;
use App\Services\Billing\BankVerificationService;
use Illuminate\Http\Request;

class DepositController extends Controller
{
    /**
     * Danh sách tất cả deposit requests
     */
    public function index(Request $request)
    {
        $query = DepositRequest::with('user')->latest();

        // Filter theo status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter theo user
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('transaction_code', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($q2) use ($search) {
                      $q2->where('email', 'like', "%{$search}%")
                         ->orWhere('name', 'like', "%{$search}%");
                  });
            });
        }

        $deposits = $query->paginate(20);

        // Thống kê
        $stats = [
            'pending' => DepositRequest::where('status', 'pending')->where('expires_at', '>=', now())->count(),
            'completed_today' => DepositRequest::where('status', 'completed')->whereDate('matched_at', today())->count(),
            'total_today' => DepositRequest::where('status', 'completed')
                ->whereDate('matched_at', today())
                ->sum('actual_amount'),
        ];

        return view('admin.deposits.index', compact('deposits', 'stats'));
    }

    /**
     * Admin duyệt thủ công
     */
    public function approve(Request $request, DepositRequest $deposit)
    {
        $request->validate([
            'actual_amount' => 'required|numeric|min:1000',
            'admin_note' => 'nullable|string|max:500',
        ]);

        if ($deposit->status === 'completed') {
            return redirect()->back()->with('error', 'Deposit này đã được duyệt trước đó.');
        }

        $service = new BankVerificationService();
        $success = $service->manualApprove(
            $deposit,
            (float) $request->actual_amount,
            $request->admin_note ?? 'Duyệt thủ công bởi Admin'
        );

        if ($success) {
            return redirect()->back()->with('success', "Đã duyệt thành công {$deposit->transaction_code} - " . number_format($request->actual_amount, 0, ',', '.') . 'đ');
        }

        return redirect()->back()->with('error', 'Có lỗi khi duyệt. Kiểm tra log.');
    }

    /**
     * Admin từ chối
     */
    public function reject(Request $request, DepositRequest $deposit)
    {
        if (!in_array($deposit->status, ['pending', 'expired'])) {
            return redirect()->back()->with('error', 'Không thể từ chối deposit này.');
        }

        $deposit->update([
            'status' => 'rejected',
            'admin_note' => $request->admin_note ?? 'Từ chối bởi Admin',
        ]);

        return redirect()->back()->with('success', "Đã từ chối deposit {$deposit->transaction_code}");
    }

    /**
     * Manual check bank API
     */
    public function checkNow()
    {
        $service = new BankVerificationService();
        $expired = $service->expirePendingRequests();
        $matched = $service->processNewTransactions();

        $msg = "Kiểm tra xong. Khớp: {$matched} giao dịch.";
        if ($expired > 0) {
            $msg .= " Expired: {$expired} requests.";
        }

        return redirect()->back()->with('success', $msg);
    }
}
