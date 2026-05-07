<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;
use App\Services\Telegram\TelegramService;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Telegram sends updates as JSON
        $update = $request->all();
        
        if (isset($update['message']['text'])) {
            $chatId = $update['message']['chat']['id'];
            $text = trim($update['message']['text']);

            // Expecting: /start {token}
            if (strpos($text, '/start ') === 0) {
                $token = explode(' ', $text)[1] ?? null;
                
                if ($token) {
                    $user = User::where('telegram_verify_token', $token)->first();
                    
                    if ($user) {
                        $user->telegram_id = $chatId;
                        $user->telegram_verify_token = null; // Clear token after use
                        $user->save();

                        $telegramService = new TelegramService();
                        $telegramService->sendMessage($chatId, "✅ Xin chào {$user->name}, tài khoản Telegram của bạn đã được liên kết thành công trên hệ thống VPS ZicNet. Bạn sẽ nhận được các thông báo tự động tại đây.");
                    } else {
                        $telegramService = new TelegramService();
                        $telegramService->sendMessage($chatId, "❌ Mã xác thực không hợp lệ hoặc đã hết hạn.");
                    }
                }
            }
        }
        
        return response()->json(['status' => 'success']);
    }
}
