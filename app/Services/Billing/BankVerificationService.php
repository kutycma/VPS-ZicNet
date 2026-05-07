<?php

namespace App\Services\Billing;

use App\Models\DepositRequest;
use App\Models\PaymentMethod;
use App\Models\Setting;
use App\Models\User;
use App\Services\Telegram\TelegramService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class BankVerificationService
{
    protected PaymentService $paymentService;
    protected TelegramService $telegramService;

    public function __construct()
    {
        $this->paymentService = new PaymentService();
        $this->telegramService = new TelegramService();
    }

    /**
     * Gọi API bank lấy lịch sử giao dịch cho 1 PaymentMethod cụ thể
     */
    public function fetchBankHistory(PaymentMethod $method): ?array
    {
        if (!$method->hasApiConfig()) {
            return null;
        }

        // Rate limit per method: tối đa 1 lần/30 giây
        $cacheKey = 'bank_api_last_call_' . $method->id;
        if (Cache::has($cacheKey)) {
            Log::debug("BankVerification [{$method->name}]: Rate limited, skipping...");
            return null;
        }

        try {
            $url = $method->getFullApiUrl();
            $response = Http::timeout(15)->get($url);

            if (!$response->successful()) {
                Log::error("BankVerification [{$method->name}]: API trả về lỗi HTTP " . $response->status());
                return null;
            }

            $data = $response->json();

            if (($data['codeStatus'] ?? 0) !== 200) {
                Log::error("BankVerification [{$method->name}]: API trả về codeStatus != 200", $data);
                return null;
            }

            // Set cache 30 giây
            Cache::put($cacheKey, true, 30);

            return $data['data'] ?? [];
        } catch (\Exception $e) {
            Log::error("BankVerification [{$method->name}]: Exception - " . $e->getMessage());
            return null;
        }
    }

    /**
     * Xử lý giao dịch mới từ TẤT CẢ payment methods có cấu hình API
     * Return: tổng số giao dịch khớp thành công
     */
    public function processNewTransactions(): int
    {
        $methods = PaymentMethod::active()
            ->whereNotNull('api_key')
            ->where('api_key', '!=', '')
            ->whereNotNull('api_url')
            ->where('api_url', '!=', '')
            ->get();

        if ($methods->isEmpty()) {
            Log::debug('BankVerification: Không có payment method nào có cấu hình API');
            return 0;
        }

        $totalMatched = 0;
        $prefix = Setting::get('deposit_prefix', 'ZICNET');

        foreach ($methods as $method) {
            $matched = $this->processMethodTransactions($method, $prefix);
            $totalMatched += $matched;
        }

        return $totalMatched;
    }

    /**
     * Xử lý giao dịch cho 1 payment method cụ thể
     */
    protected function processMethodTransactions(PaymentMethod $method, string $prefix): int
    {
        $transactions = $this->fetchBankHistory($method);

        if ($transactions === null) {
            return 0;
        }

        $matched = 0;

        foreach ($transactions as $tx) {
            if (($tx['type'] ?? '') !== 'IN') {
                continue;
            }

            $description = strtoupper($tx['description'] ?? '');
            $amount = (float) ($tx['amount'] ?? 0);
            $bankTxId = (string) ($tx['transactionNumber'] ?? '');

            if (empty($bankTxId) || $amount <= 0) {
                continue;
            }

            $transactionCode = $this->extractTransactionCode($description, $prefix);

            if (!$transactionCode) {
                continue;
            }

            // Chống duplicate
            $alreadyProcessed = DepositRequest::where('bank_transaction_id', $bankTxId)->exists();
            if ($alreadyProcessed) {
                continue;
            }

            // Tìm deposit request pending thuộc payment method này
            $deposit = DepositRequest::where('transaction_code', $transactionCode)
                ->where('payment_method_id', $method->id)
                ->where('status', 'pending')
                ->first();

            // Fallback: tìm bất kỳ deposit pending nào khớp mã (phòng trường hợp cũ không có payment_method_id)
            if (!$deposit) {
                $deposit = DepositRequest::where('transaction_code', $transactionCode)
                    ->where('status', 'pending')
                    ->whereNull('payment_method_id')
                    ->first();
            }

            if (!$deposit) {
                Log::info("BankVerification [{$method->name}]: Tìm thấy mã {$transactionCode} nhưng không có deposit pending");
                continue;
            }

            if ($this->matchAndCredit($deposit, $amount, $bankTxId, $description)) {
                $matched++;
            }
        }

        return $matched;
    }

    /**
     * Trích xuất mã giao dịch từ nội dung CK
     */
    protected function extractTransactionCode(string $description, string $prefix): ?string
    {
        $pattern = '/(' . preg_quote($prefix, '/') . '\d+)/i';

        if (preg_match($pattern, $description, $matches)) {
            return strtoupper($matches[1]);
        }

        return null;
    }

    /**
     * Khớp giao dịch và cộng tiền cho user
     */
    protected function matchAndCredit(DepositRequest $deposit, float $actualAmount, string $bankTxId, string $bankDescription): bool
    {
        try {
            $deposit->update([
                'status' => 'completed',
                'actual_amount' => $actualAmount,
                'bank_transaction_id' => $bankTxId,
                'bank_description' => $bankDescription,
                'matched_at' => now(),
            ]);

            $user = $deposit->user;
            $this->paymentService->deposit(
                $user,
                $actualAmount,
                "Nạp tiền tự động - Mã: {$deposit->transaction_code}",
                $deposit->transaction_code
            );

            Log::info("BankVerification: ✅ Khớp thành công {$deposit->transaction_code} - {$actualAmount}đ cho user #{$user->id}");

            $this->notifySuccess($deposit, $user, $actualAmount);

            return true;
        } catch (\Exception $e) {
            Log::error("BankVerification: ❌ Lỗi khớp GD {$deposit->transaction_code} - " . $e->getMessage());
            $deposit->update(['status' => 'pending', 'bank_transaction_id' => null]);
            return false;
        }
    }

    /**
     * Đánh dấu expired cho các request quá hạn
     */
    public function expirePendingRequests(): int
    {
        $count = DepositRequest::where('status', 'pending')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);

        if ($count > 0) {
            Log::info("BankVerification: Đã expire {$count} deposit requests");
        }

        return $count;
    }

    /**
     * Gửi thông báo khi khớp thành công
     */
    protected function notifySuccess(DepositRequest $deposit, User $user, float $amount): void
    {
        $formattedAmount = number_format($amount, 0, ',', '.');

        $userMsg = "💰 <b>Nạp tiền thành công!</b>\n\n"
            . "Mã GD: <code>{$deposit->transaction_code}</code>\n"
            . "Số tiền: <b>{$formattedAmount}đ</b>\n"
            . "Số dư mới: <b>" . number_format((float) $user->fresh()->balance, 0, ',', '.') . "đ</b>\n\n"
            . "Cảm ơn bạn đã sử dụng VPS ZicNet! 🎉";
        $this->telegramService->sendToUser($user, $userMsg);

        $adminMsg = "💰 <b>Nạp tiền tự động</b>\n\n"
            . "User: {$user->name} ({$user->email})\n"
            . "Mã GD: <code>{$deposit->transaction_code}</code>\n"
            . "Số tiền: <b>{$formattedAmount}đ</b>\n"
            . "Bank TxID: {$deposit->bank_transaction_id}";
        $this->telegramService->sendToAdmin($adminMsg);
    }

    /**
     * Admin duyệt thủ công
     */
    public function manualApprove(DepositRequest $deposit, float $amount, string $adminNote = ''): bool
    {
        try {
            $deposit->update([
                'status' => 'completed',
                'actual_amount' => $amount,
                'matched_at' => now(),
                'admin_note' => $adminNote ?: 'Duyệt thủ công bởi Admin',
            ]);

            $user = $deposit->user;
            $this->paymentService->deposit(
                $user,
                $amount,
                "Nạp tiền (Admin duyệt) - Mã: {$deposit->transaction_code}",
                $deposit->transaction_code
            );

            Log::info("BankVerification: Admin duyệt thủ công {$deposit->transaction_code} - {$amount}đ cho user #{$user->id}");

            $this->notifySuccess($deposit, $user, $amount);

            return true;
        } catch (\Exception $e) {
            Log::error("BankVerification: Lỗi duyệt thủ công {$deposit->transaction_code} - " . $e->getMessage());
            return false;
        }
    }
}
