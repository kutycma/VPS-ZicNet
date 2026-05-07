<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\VpsInstance;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\VpsExpiringMail;
use Illuminate\Support\Facades\Log;

class CheckVpsExpiration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vps:check-expiration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for VPS instances expiring in 3 days and send notifications';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(TelegramService $telegram)
    {
        $this->info("Bắt đầu quét hình VPS sắp hết hạn...");

        $expiringVps = VpsInstance::where('status', '!=', 'cancelled')
            ->whereNotNull('expires_at')
            ->whereNull('expiration_notified_at')
            ->where('expires_at', '<=', Carbon::now()->addDays(3))
            ->where('expires_at', '>', Carbon::now()) // Don't notify if already expired to avoid spam
            ->with('user')
            ->get();

        if ($expiringVps->isEmpty()) {
            $this->info("Không có VPS nào cần thông báo.");
            return 0;
        }

        $this->info("Tìm thấy {$expiringVps->count()} VPS sắp hết hạn.");

        foreach ($expiringVps as $vps) {
            $user = $vps->user;
            if (!$user) continue;

            $this->info("- Gửi thông báo cho VPS #{$vps->id} (User: {$user->email})");

            try {
                // Email
                if ($user->hasVerifiedEmail()) {
                    Mail::to($user->email)->send(new VpsExpiringMail($vps));
                }

                // Telegram
                if ($user->telegram_id) {
                    $telegram->sendToUser($user, '⚠️ CẢNH BÁO: Dịch vụ VPS #' . ($vps->vps_provider_id ?? $vps->id) . ' sắp hết hạn vào ngày ' . Carbon::parse($vps->expires_at)->format('d/m/Y H:i') . '. Vui lòng đăng nhập hệ thống và gia hạn để không bị gián đoạn hoạt động!');
                }

                $vps->update(['expiration_notified_at' => now()]);
            } catch (\Exception $e) {
                Log::error("Failed to send VPS Expiring notification for VPS #{$vps->id}: " . $e->getMessage());
                $this->error("Lỗi khi gửi thông báo: " . $e->getMessage());
            }
        }

        $this->info("Hoàn tất quét.");
        return 0;
    }
}
