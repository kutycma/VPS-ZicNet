<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckProviderBalance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'provider:check-balance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kiểm tra tiền tại các Nhà Cung Cấp. Báo động qua Telegram nếu dưới ngưỡng cấu hình.';

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
        $threshold = (float) \App\Models\Setting::get('provider_balance_threshold', 200000);
        $providers = \App\Models\VpsProvider::where('status', 'active')->get();
        $telegram = new \App\Services\Telegram\TelegramService();
        $vpsManager = new \App\Services\Vps\VpsManager();

        foreach ($providers as $provider) {
            try {
                $driver = $vpsManager->resolveProvider($provider);
                $info = $driver->getAgencyInfo(); // assuming getAgencyInfo returns an array with 'balance' or 'credit'
                $balance = null;
                if (isset($info['balance'])) $balance = (float) $info['balance'];
                elseif (isset($info['credit'])) $balance = (float) $info['credit'];

                if ($balance !== null && $balance < $threshold) {
                    $telegram->sendToAdmin("⚠️ BÁO ĐỘNG SỐ DƯ NCC ⚠️\n\nNhà cung cấp: {$provider->name}\nSố dư hiện tại: ".number_format($balance)."đ\nNgưỡng báo động: ".number_format($threshold)."đ\nVui lòng nạp tiền ngay để tránh gián đoạn dịch vụ!");
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("CheckBalance: Error checking {$provider->name} - " . $e->getMessage());
            }
        }

        return 0;
    }
}
