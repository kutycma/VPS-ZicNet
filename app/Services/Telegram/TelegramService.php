<?php

namespace App\Services\Telegram;

use Illuminate\Support\Facades\Http;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected $botToken;

    public function __construct()
    {
        $this->botToken = Setting::get('telegram_bot_token');
    }

    /**
     * Send message to a specific Telegram chat ID
     */
    public function sendMessage($chatId, $message)
    {
        if (empty($this->botToken) || empty($chatId)) {
            return false;
        }

        try {
            $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";
            $response = Http::post($url, [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]);

            if (!$response->successful()) {
                Log::error('Telegram Send Error: ' . $response->body());
            }

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Telegram Exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send notification strictly to the configured Admin ID
     */
    public function sendToAdmin($message)
    {
        $adminChatId = Setting::get('telegram_admin_id');
        if ($adminChatId) {
            return $this->sendMessage($adminChatId, "🔔 <b>THÔNG BÁO ADMIN</b>\n\n" . $message);
        }
        return false;
    }

    /**
     * Send notification to a specific user (if they have linked telegram_id)
     */
    public function sendToUser(User $user, $message)
    {
        if (!empty($user->telegram_id)) {
            return $this->sendMessage($user->telegram_id, "ℹ️ <b>Thông báo từ ZicNet</b>\n\n" . $message);
        }
        return false;
    }
}
