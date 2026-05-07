<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ProcessPendingOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info("Đang kiểm tra các đơn hàng đang chờ xử lý (pending)...");

        $orders = \App\Models\Order::with(['vpsPlan', 'user', 'vpsInstance'])
            ->where('status', 'pending')
            ->whereIn('type', ['new', 'renew', 'upgrade'])
            ->get();

        if ($orders->isEmpty()) {
            return 0;
        }

        $this->info("Tìm thấy {$orders->count()} đơn hàng cần xử lý.");

        $vpsManager = new \App\Services\Vps\VpsManager();
        $telegram = new \App\Services\Telegram\TelegramService();

        foreach ($orders as $order) {
            $this->line("-> Đang xử lý đơn hàng #{$order->id} (Loại: {$order->type}) của User {$order->user_id}");

            $plan = $order->vpsPlan;
            $user = $order->user;
            $params = $order->payload ?? [];

            try {
                if ($order->type === 'new') {
                    // Gọi API tạo VPS
                    $result = $vpsManager->createVps($plan, $params);

                    // Tạo VPS instances
                    $vpsData = $result['data'] ?? [];
                    $instancesCreated = [];
                    foreach ($vpsData as $vps) {
                        $instance = \App\Models\VpsInstance::create([
                            'user_id' => $user->id,
                            'plan_id' => $plan->id,
                            'provider_id' => $plan->provider_id,
                            'vps_provider_id' => $vps['vps-id'] ?? null,
                            'ip_address' => $vps['ip'] ?? null,
                            'os' => $params['os'] ?? null,
                            'username' => $vps['username'] ?? null,
                            'password' => $vps['password'] ?? null,
                            'status' => $vps['vps-status'] ?? 'progressing',
                            'expires_at' => isset($vps['next_due_date']) ? $vps['next_due_date'] : null,
                            'provider_data' => $vps,
                        ]);
                        $instancesCreated[] = $instance->id;
                        $order->update(['vps_instance_id' => $instance->id]);
                    }
                    
                    $this->info("   -> Khởi tạo thành công! (Instance IDs: " . implode(',', $instancesCreated) . ")");
                    
                    try {
                        if ($user->hasVerifiedEmail()) {
                            \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\OrderSuccessMail($order));
                        }
                        if ($user->telegram_id) {
                            $telegram->sendToUser($user, 'Đơn hàng hệ thống VPS mới nhất của bạn (Mã: #' . $order->id . ') đã được hệ thống tự động khởi tạo thành công! Chi phí: ' . number_format((float)$order->amount) . 'đ.');
                        }
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error("Failed to send order success notification: " . $e->getMessage());
                    }

                    $order->update(['status' => 'completed', 'api_response' => $result, 'notes' => 'Khởi tạo thành công qua Cron Worker']);
                
                } elseif ($order->type === 'renew') {
                    $instance = $order->vpsInstance;
                    if (!$instance) throw new \Exception("Không tìm thấy VPS để gia hạn.");
                    
                    $result = $vpsManager->controlVps($instance, 'renew-vps', [
                        'billing_cycle' => $params['billing_cycle'] ?? '1 Tháng'
                    ]);
                    
                    $this->info("   -> Gia hạn thành công VPS #{$instance->id}!");
                    
                    try {
                        if ($user->hasVerifiedEmail()) {
                            \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\OrderSuccessMail($order));
                        }
                        if ($user->telegram_id) {
                            $telegram->sendToUser($user, 'Yêu cầu Gia hạn VPS #' . $instance->vps_provider_id . ' (Mã: #' . $order->id . ') đã được xử lý thành công! Chi phí: ' . number_format((float)$order->amount) . 'đ.');
                        }
                    } catch (\Exception $e) {}

                    $order->update(['status' => 'completed', 'api_response' => $result, 'notes' => 'Gia hạn thành công qua Cron']);
                
                } elseif ($order->type === 'upgrade') {
                    $instance = $order->vpsInstance;
                    if (!$instance) throw new \Exception("Không tìm thấy VPS để nâng cấp.");

                    $result = $vpsManager->controlVps($instance, 'addon-vps', [
                        'addon_cpu' => $params['addon_cpu'] ?? 0,
                        'addon_ram' => $params['addon_ram'] ?? 0,
                        'addon_disk' => $params['addon_disk'] ?? 0,
                    ]);
                    
                    $this->info("   -> Nâng cấp thành công VPS #{$instance->id}!");
                    
                    try {
                        if ($user->hasVerifiedEmail()) {
                            \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\OrderSuccessMail($order));
                        }
                        if ($user->telegram_id) {
                            $telegram->sendToUser($user, 'Yêu cầu Nâng cấp cấu hình VPS #' . $instance->vps_provider_id . ' (Mã: #' . $order->id . ') đã được hệ thống cấp phép thành công! Mời khởi động lại máy chủ để nhận cấu hình mới. Chi phí: ' . number_format((float)$order->amount) . 'đ.');
                        }
                    } catch (\Exception $e) {}

                    $order->update(['status' => 'completed', 'api_response' => $result, 'notes' => 'Nâng cấp thành công qua Cron']);
                }

            } catch (\Exception $e) {
                $errorMsg = $e->getMessage();
                $this->error("   -> Lỗi khởi tạo: {$errorMsg}");

                // Update attempts and notes, NHƯNG giữ status = pending theo yêu cầu khách hàng
                $order->increment('attempts');
                $order->update([
                    'last_attempt_at' => now(),
                    'notes' => 'Lỗi khởi tạo (Lượt ' . $order->attempts . '): ' . $errorMsg,
                ]);

                // Chỉ thông báo lần đầu api lỗi để tránh spam mọi phút
                if ($order->attempts == 1) {
                    try {
                        $telegram->sendToAdmin("⚠️ LỖI KHỞI TẠO VPS ⚠️\nĐơn hàng: #{$order->id}\nKhách hàng: {$user->email}\nGói: {$plan->name}\nLỗi: {$errorMsg}\n\nĐơn hàng vẫn đang treo ở trạng thái Pending. Vui lòng kiểm tra API hoặc số dư nhà cung cấp!");
                    } catch (\Exception $e) {}
                }
            }

            sleep(2); // Giảm tải API
        }

        return 0;
    }
}
