<?php

namespace App\Console\Commands;

use App\Services\Billing\BankVerificationService;
use Illuminate\Console\Command;

class CheckBankPayments extends Command
{
    protected $signature = 'payments:check-bank';
    protected $description = 'Kiểm tra API bank và tự động khớp giao dịch nạp tiền';

    public function handle()
    {
        $service = new BankVerificationService();

        // 1. Expire các request quá hạn
        $expired = $service->expirePendingRequests();
        if ($expired > 0) {
            $this->info("Đã expire {$expired} deposit requests");
        }

        // 2. Xử lý giao dịch mới
        $matched = $service->processNewTransactions();

        if ($matched > 0) {
            $this->info("✅ Đã khớp {$matched} giao dịch thành công");
        } else {
            $this->line('Không có giao dịch mới khớp');
        }

        return 0;
    }
}
