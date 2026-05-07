<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\VpsInstance;
use App\Models\VpsPlan;
use App\Services\Vps\VpsManager;
use Illuminate\Support\Facades\Log;

class EnrichVpsData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vps:enrich';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lấy thông tin chi tiết các VPS bị thiếu OS hoặc Plan_id từ nhà cung cấp';

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
        $this->info("Bắt đầu quy trình Enrich dữ liệu VPS...");
        
        $instances = VpsInstance::where('status', '!=', 'deleted')
            ->where(function ($q) {
                $q->whereNull('os')
                  ->orWhereNull('plan_id');
            })
            ->get();

        $this->info("Tìm thấy " . $instances->count() . " VPS cần cập nhật.");

        if ($instances->isEmpty()) {
            return 0;
        }

        $manager = new VpsManager();
        $updatedCount = 0;

        foreach ($instances as $instance) {
            $this->line("Đang kiểm tra VPS ID {$instance->id} (Provider ID: {$instance->vps_provider_id})...");
            
            try {
                // Sửa lỗi Double-JSON encoding làm provider_data bị biến thành string
                $pData = is_string($instance->provider_data) ? json_decode($instance->provider_data, true) : $instance->provider_data;
                if (is_string($pData)) {
                    $pData = json_decode($pData, true);
                }
                // Cố gắng đoán type nếu cũ chưa có (đa số là vps_vn)
                $systemType = $pData['_system_type'] ?? null;
                $isGuess = false;
                if (empty($systemType)) {
                    $systemType = 'vps_vn'; // Default fallback
                    $pData['_system_type'] = $systemType;
                    $isGuess = true;
                }

                // Gán tạm vào instance object để VpsManager có thể đọc mảng
                $instance->provider_data = $pData;

                $info = null;
                try {
                    $info = $manager->getVpsInfo($instance);
                } catch (\Exception $e) {
                    if ($isGuess && $systemType === 'vps_vn') {
                        // Thử lại với NN
                        $pData['_system_type'] = 'vps_nn';
                        $instance->provider_data = $pData;
                        $info = $manager->getVpsInfo($instance);
                    } else {
                        throw $e; // Re-throw
                    }
                }

                $data = $info['data'] ?? ($info['vps'] ?? $info);
                
                // Nếu trả về mảng con (ví dụ [ { "vps-id":... } ])
                if (isset($data[0]) && is_array($data[0])) {
                    $data = $data[0];
                }
                
                if (empty($data)) {
                    $this->error(" -> Lỗi: Không có dữ liệu trả về cho VPS #{$instance->id}");
                    continue;
                }

                $updateData = [];

                if (is_null($instance->os) && !empty($data['os'])) {
                    $updateData['os'] = $data['os'];
                }

                $providerPlanId = $data['product_id'] ?? ($data['plan_id'] ?? null);
                if (is_null($instance->plan_id)) {
                    $plan = null;
                    if ($providerPlanId) {
                        $plan = VpsPlan::where('provider_id', $instance->provider_id)
                                       ->where('provider_plan_id', $providerPlanId)
                                       ->first();
                    } else if (isset($data['cpu']) && isset($data['ram']) && isset($data['disk'])) {
                        // Cố gắng map gói bằng cấu hình phần cứng!
                        // Chú ý: RAM từ H2Cloud là GB, trên hệ thống lưu là MB
                        $plan = VpsPlan::where('provider_id', $instance->provider_id)
                                       ->where('cpu_cores', (int)$data['cpu'])
                                       ->where('ram_mb', (int)$data['ram'] * 1024)
                                       ->where('disk_gb', (int)$data['disk'])
                                       ->first();
                    }
                    
                    if ($plan) {
                        $updateData['plan_id'] = $plan->id;
                    }
                }

                if (!empty($updateData)) {
                    // Update only needed keys so we don't accidentally save the array into a double encoded string again here (let syncInstances fix that later)
                    $instance->update($updateData);
                    $this->info(" -> Đã cập nhật thành công VPS #{$instance->id}: " . json_encode($updateData));
                    $updatedCount++;
                } else {
                    $this->line(" -> Không tìm thấy OS mới hoặc Plan hợp lệ cho VPS #{$instance->id}");
                }

            } catch (\Exception $e) {
                $this->error(" -> Lỗi khi lấy thông tin VPS #{$instance->id}: " . $e->getMessage());
                Log::error("EnrichVpsData: Lỗi VPS ID {$instance->id} - " . $e->getMessage());
            }

            sleep(1);
        }

        $this->info("Hoàn tất! Đã cập nhật thành công $updatedCount VPS.");
        return 0;
    }
}
