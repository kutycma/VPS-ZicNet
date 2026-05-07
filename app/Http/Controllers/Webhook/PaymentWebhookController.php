<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Billing\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    /**
     * Xử lý webhook từ cổng thanh toán
     * Tự động cộng tiền khi nhận được callback
     */
    public function handle(Request $request)
    {
        Log::info('Payment Webhook received', $request->all());

        // Validate webhook signature (tùy cổng thanh toán)
        // TODO: Implement signature validation based on payment gateway

        $transactionId = $request->input('transaction_id');
        $amount = $request->input('amount');
        $userId = $request->input('user_id');
        $status = $request->input('status');

        if (!$amount || !$userId || $status !== 'success') {
            Log::warning('Webhook: Invalid or failed payment', $request->all());
            return response()->json(['status' => 'ignored'], 200);
        }

        $user = User::find($userId);
        if (!$user) {
            Log::error("Webhook: User not found #{$userId}");
            return response()->json(['status' => 'user_not_found'], 404);
        }

        try {
            $paymentService = new PaymentService();
            $paymentService->deposit(
                $user,
                (float) $amount,
                'Nạp tiền tự động qua cổng thanh toán',
                $transactionId
            );

            Log::info("Webhook: Deposited {$amount} to user #{$userId}");

            return response()->json(['status' => 'success'], 200);
        } catch (\Exception $e) {
            Log::error("Webhook: Deposit failed - " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
