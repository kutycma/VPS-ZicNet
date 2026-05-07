<?php

namespace App\Services\Vps;

use App\Models\VpsProvider;
use App\Models\VpsPlan;
use App\Models\VpsInstance;
use App\Services\Vps\Contracts\VpsProviderInterface;
use App\Services\Vps\Providers\H2CloudProvider;
use Illuminate\Support\Facades\Log;

class VpsManager
{
    /**
     * Map slug → Provider class
     */
    protected array $providerMap = [
        'h2cloud' => H2CloudProvider::class,
    ];

    /**
     * Resolve provider driver dựa trên VpsProvider model
     */
    public function resolveProvider(VpsProvider $provider): VpsProviderInterface
    {
        $slug = $provider->slug;

        if (!isset($this->providerMap[$slug])) {
            throw new \Exception("Provider driver [{$slug}] chưa được hỗ trợ.");
        }

        $class = $this->providerMap[$slug];
        return new $class($provider);
    }

    /**
     * Tạo VPS mới qua provider API
     */
    public function createVps(VpsPlan $plan, array $params): array
    {
        $provider = $plan->provider;
        $driver = $this->resolveProvider($provider);

        if ($plan->type === 'vps_vn') {
            return $driver->createVpsVN(array_merge($params, [
                'product_id' => $plan->provider_plan_id,
            ]));
        } elseif ($plan->type === 'vps_nn') {
            return $driver->createVpsNN(array_merge($params, [
                'product_id' => $plan->provider_plan_id,
            ]));
        }

        throw new \Exception("Loại VPS [{$plan->type}] không được hỗ trợ.");
    }

    /**
     * Điều khiển VPS (start, stop, reboot, ...)
     */
    public function controlVps(VpsInstance $instance, string $action, array $extra = []): array
    {
        $provider = $instance->provider;
        $driver = $this->resolveProvider($provider);
        
        $type = 'vps_vn';
        if ($instance->plan) {
            $type = $instance->plan->type;
        } elseif (isset($instance->provider_data['_system_type'])) {
            $type = $instance->provider_data['_system_type'];
        }

        if ($type === 'vps_vn') {
            return $driver->actionVpsVN($instance->vps_provider_id, $action, $extra);
        } elseif ($type === 'vps_nn') {
            return $driver->actionVpsNN($instance->vps_provider_id, $action, $extra);
        }

        throw new \Exception("Loại VPS [{$type}] không được hỗ trợ.");
    }

    /**
     * Lấy thông tin VPS từ provider
     */
    public function getVpsInfo(VpsInstance $instance): array
    {
        $provider = $instance->provider;
        $driver = $this->resolveProvider($provider);

        $type = 'vps_vn';
        if ($instance->plan) {
            $type = $instance->plan->type;
        } elseif (isset($instance->provider_data['_system_type'])) {
            $type = $instance->provider_data['_system_type'];
        }

        if ($type === 'vps_vn') {
            return $driver->getInfoVpsVN($instance->vps_provider_id);
        } elseif ($type === 'vps_nn') {
            return $driver->getInfoVpsNN($instance->vps_provider_id);
        }

        throw new \Exception("Loại VPS [{$type}] không được hỗ trợ.");
    }

    /**
     * Đồng bộ danh sách gói VPS từ provider
     *
     * H2Cloud product structure (đã flatten bởi getProducts()):
     * product_id, name, cpu, ram (GB!), disk, bandwidth, pricing{monthly{amount}...}
     */
    public function syncPlans(VpsProvider $provider): int
    {
        $driver = $this->resolveProvider($provider);
        $products = $driver->getProducts();

        Log::info("syncPlans from [{$provider->name}]: " . count($products) . " products found");

        if (empty($products)) {
            Log::warning("syncPlans: Không tìm thấy sản phẩm nào từ [{$provider->name}]");
            return 0;
        }

        $count = 0;

        foreach ($products as $product) {
            $planId = $product['product_id'];
            $name = $product['name'] ?? "Plan #{$planId}";

            // Bỏ qua addon products: "Mua thêm CPU/RAM/DISK"
            // Đây là options mua thêm khi tạo VPS, không phải plan riêng
            if (
                stripos($name, 'Mua thêm') !== false ||
                stripos($name, 'addon') !== false ||
                stripos($name, 'add-on') !== false
            ) {
                Log::info("syncPlans: SKIP addon [{$name}] id={$planId}");
                continue;
            }

            // Lấy giá tháng (monthly) làm giá gốc
            $pricing = $product['pricing'] ?? [];
            $monthlyPrice = 0;
            if (isset($pricing['monthly']['amount'])) {
                $monthlyPrice = (float) $pricing['monthly']['amount'];
            }

            // Build pricing_data: áp dụng markup cho từng billing cycle
            // Sắp xếp theo thời gian: tháng → quý → năm
            // Nếu plan đã tồn tại, giữ lại giá bán custom (admin đã chỉnh)
            $existingPlan = VpsPlan::where('provider_id', $provider->id)
                ->where('provider_plan_id', (string) $planId)->first();
            $existingPricing = $existingPlan ? ($existingPlan->pricing_data ?? []) : [];

            $pricingData = [];
            foreach ($pricing as $cycleKey => $cycleInfo) {
                if (!is_array($cycleInfo) || !isset($cycleInfo['amount'])) continue;
                $providerAmount = (float) $cycleInfo['amount'];

                // Giữ lại selling_price custom nếu admin đã sửa tay
                $existingSelling = $existingPricing[$cycleKey]['selling_price'] ?? null;
                $autoSelling = $provider->calculateSellingPrice($providerAmount);

                $cycleLabel = VpsPlan::BILLING_CYCLES[$cycleKey]['label'] ?? ($cycleInfo['billing_cycle'] ?? $cycleKey);
                $cycleSort = VpsPlan::BILLING_CYCLES[$cycleKey]['sort'] ?? 99;

                $pricingData[$cycleKey] = [
                    'label' => $cycleLabel,
                    'provider_price' => $providerAmount,
                    'selling_price' => $existingSelling ?: $autoSelling,
                    'sort' => $cycleSort,
                ];
            }

            // Sort by duration
            uasort($pricingData, function ($a, $b) {
                return ($a['sort'] ?? 99) - ($b['sort'] ?? 99);
            });

            // RAM: H2Cloud trả về GB → chuyển sang MB cho DB
            $ramGb = (int) ($product['ram'] ?? 0);
            $ramMb = $ramGb * 1024;

            $cpu = (int) ($product['cpu'] ?? 0);
            $disk = (int) ($product['disk'] ?? 0);

            // Bandwidth
            $bw = $product['bandwidth'] ?? '';
            $bandwidthMbps = is_numeric($bw) ? (int) $bw : 0;

            // Xác định type từ _category hoặc tên
            $groupName = $product['_group_name'] ?? '';
            $type = 'vps_vn';
            if (
                stripos($name, 'VPS NN') !== false ||
                stripos($groupName, 'NN') !== false ||
                stripos($groupName, 'nước ngoài') !== false
            ) {
                $type = 'vps_nn';
            }

            $plan = VpsPlan::firstOrNew([
                'provider_id' => $provider->id,
                'provider_plan_id' => (string) $planId,
            ]);

            $isNew = !$plan->exists;

            $plan->name = $name;
            $plan->type = $type;
            $plan->cpu_cores = $cpu;
            $plan->ram_mb = $ramMb;
            $plan->disk_gb = $disk;
            $plan->bandwidth_mbps = $bandwidthMbps;
            $plan->provider_price = $monthlyPrice;
            $plan->selling_price = $provider->calculateSellingPrice($monthlyPrice);
            $plan->pricing_data = $pricingData;
            $plan->provider_data = json_encode($product, JSON_UNESCAPED_UNICODE);

            if ($isNew) {
                $plan->status = 'inactive';
                // Auto-assign nhóm mặc định từ provider khi tạo mới
                if ($provider->default_group_id) {
                    $plan->group_id = $provider->default_group_id;
                }
            }

            $plan->save();
            $count++;

            Log::info("syncPlans: [{$name}] id={$planId} cpu={$cpu} ram={$ramGb}GB disk={$disk}GB monthly={$monthlyPrice}đ cycles=" . count($pricingData) . " type={$type}");
        }

        Log::info("Synced {$count} plans from [{$provider->name}]");

        return $count;
    }

    /**
     * Lấy thông tin đại lý (balance h2cloud)
     */
    public function getAgencyInfo(VpsProvider $provider): array
    {
        $driver = $this->resolveProvider($provider);
        return $driver->getAgencyInfo();
    }

    /**
     * Đồng bộ danh sách Máy chủ ảo (VPS Instances) đang có trên Provider về DB Hệ thống.
     * Nếu không xác định được User, mặc định gán cho Admin.
     */
    public function syncInstances(VpsProvider $provider): int
    {
        $driver = $this->resolveProvider($provider);
        $count = 0;
        
        $adminUserId = \App\Models\User::where('role', 'admin')->first()->id ?? 1;

        // 1. Lấy VPS VN
        try {
            // Check nếu driver có method getListVpsVN
            if (method_exists($driver, 'getListVpsVN')) {
                $resultVN = $driver->getListVpsVN('all', 100);
                $vpsListVN = $resultVN['list-service'] ?? ($resultVN['data'] ?? ($resultVN['vps'] ?? []));
                
                foreach ($vpsListVN as $v) {
                    $vpsId = $v['vps-id'] ?? ($v['id'] ?? null);
                    if (!$vpsId) continue;

                    // Map status
                    $rawStatus = strtolower($v['vps-status'] ?? ($v['status'] ?? 'active'));
                    $mappedStatus = 'active';
                    if ($rawStatus === 'off') $mappedStatus = 'stopped';
                    if (strpos($rawStatus, 'expired') !== false) $mappedStatus = 'expired';

                    // Map Date
                    $expiresAt = $v['next_due_date_vps'] ?? ($v['next_due_date'] ?? null);
                    if ($expiresAt && preg_match('/^\d{2}-\d{2}-\d{4}$/', $expiresAt)) {
                        $expiresAt = \Carbon\Carbon::createFromFormat('d-m-Y', $expiresAt)->format('Y-m-d');
                    }

                    $v['_system_type'] = 'vps_vn';

                    $instance = VpsInstance::where('vps_provider_id', $vpsId)->first();

                    if (strpos($rawStatus, 'delete') !== false) {
                        if ($instance) {
                            $instance->update(['status' => 'deleted', 'provider_data' => json_encode($v, JSON_UNESCAPED_UNICODE)]);
                        }
                        continue;
                    }

                    if (!$instance) {
                        VpsInstance::create([
                            'user_id' => $adminUserId,
                            'provider_id' => $provider->id,
                            'vps_provider_id' => $vpsId,
                            'ip_address' => $v['ip'] ?? null,
                            'os' => $v['os'] ?? null,
                            'username' => $v['username'] ?? null,
                            'password' => $v['password'] ?? null,
                            'status' => $mappedStatus,
                            'expires_at' => $expiresAt,
                            'provider_data' => json_encode($v, JSON_UNESCAPED_UNICODE),
                        ]);
                        $count++;
                    } else {
                        $instance->update([
                            'ip_address' => $v['ip'] ?? $instance->ip_address,
                            'status' => $mappedStatus,
                            'expires_at' => $expiresAt,
                            'provider_data' => json_encode($v, JSON_UNESCAPED_UNICODE),
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("syncInstances VN error: " . $e->getMessage());
        }

        // 2. Lấy VPS NN
        try {
            if (method_exists($driver, 'getListVpsNN')) {
                $resultNN = $driver->getListVpsNN('all', 100);
                $vpsListNN = $resultNN['list-service'] ?? ($resultNN['data'] ?? ($resultNN['vps'] ?? []));
                
                foreach ($vpsListNN as $v) {
                    $vpsId = $v['vps-id'] ?? ($v['id'] ?? null);
                    if (!$vpsId) continue;

                    // Map status
                    $rawStatus = strtolower($v['vps-status'] ?? ($v['status'] ?? 'active'));
                    $mappedStatus = 'active';
                    if ($rawStatus === 'off') $mappedStatus = 'stopped';
                    if (strpos($rawStatus, 'expired') !== false) $mappedStatus = 'expired';

                    // Map Date
                    $expiresAt = $v['next_due_date_vps'] ?? ($v['next_due_date'] ?? null);
                    if ($expiresAt && preg_match('/^\d{2}-\d{2}-\d{4}$/', $expiresAt)) {
                        $expiresAt = \Carbon\Carbon::createFromFormat('d-m-Y', $expiresAt)->format('Y-m-d');
                    }

                    $v['_system_type'] = 'vps_nn';

                    $instance = VpsInstance::where('vps_provider_id', $vpsId)->first();

                    if (strpos($rawStatus, 'delete') !== false) {
                        if ($instance) {
                            $instance->update(['status' => 'deleted', 'provider_data' => json_encode($v, JSON_UNESCAPED_UNICODE)]);
                        }
                        continue;
                    }

                    if (!$instance) {
                        VpsInstance::create([
                            'user_id' => $adminUserId,
                            'provider_id' => $provider->id,
                            'vps_provider_id' => $vpsId,
                            'ip_address' => $v['ip'] ?? null,
                            'os' => $v['os'] ?? null,
                            'username' => $v['username'] ?? null,
                            'password' => $v['password'] ?? null,
                            'status' => $mappedStatus,
                            'expires_at' => $expiresAt,
                            'provider_data' => json_encode($v, JSON_UNESCAPED_UNICODE),
                        ]);
                        $count++;
                    } else {
                        $instance->update([
                            'ip_address' => $v['ip'] ?? $instance->ip_address,
                            'status' => $mappedStatus,
                            'expires_at' => $expiresAt,
                            'provider_data' => json_encode($v, JSON_UNESCAPED_UNICODE),
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("syncInstances NN error: " . $e->getMessage());
        }

        return $count;
    }
}
