<?php

namespace App\Services\Vps\Providers;

use App\Models\VpsProvider;
use App\Services\Vps\Contracts\VpsProviderInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class H2CloudProvider extends BaseApiProvider implements VpsProviderInterface
{
    public function __construct(VpsProvider $provider)
    {
        parent::__construct($provider);
    }

    // =============================================
    // I. KẾT NỐI API - Authenticate
    // =============================================

    public function authenticate(): string
    {
        $payload = [
            'api-username' => $this->provider->api_username,
            'api-app' => $this->provider->api_app,
            'api-secret' => $this->provider->api_secret,
        ];

        $result = $this->post('/api/agency/get-token', $payload, false);

        $token = $result['auth-token'] ?? null;
        if (!$token) {
            throw new \Exception('Không nhận được auth-token từ H2Cloud. Response: ' . json_encode($result));
        }

        // Token reset cố định lúc 02:00 và 14:00 giờ Việt Nam (UTC+7)
        // Tính chính xác thời điểm reset tiếp theo, trừ 5 phút buffer
        $expiresAt = $this->getNextTokenResetTime()->subMinutes(5);

        $this->provider->update([
            'auth_token' => $token,
            'token_expires_at' => $expiresAt,
        ]);

        Log::info('H2Cloud: Token refreshed, expires at ' . $expiresAt->toDateTimeString());

        return $token;
    }

    /**
     * Tính thời điểm reset token tiếp theo của H2Cloud
     * H2Cloud reset token lúc 02:00 và 14:00 giờ Việt Nam (UTC+7) mỗi ngày
     */
    protected function getNextTokenResetTime(): Carbon
    {
        $now = Carbon::now('Asia/Ho_Chi_Minh');

        // 2 mốc reset trong ngày: 02:00 và 14:00 VN time
        $reset2am = $now->copy()->startOfDay()->addHours(2);
        $reset2pm = $now->copy()->startOfDay()->addHours(14);

        if ($now->lt($reset2am)) {
            // Trước 02:00 → reset tiếp theo là 02:00 hôm nay
            return $reset2am->setTimezone('UTC');
        } elseif ($now->lt($reset2pm)) {
            // Trước 14:00 → reset tiếp theo là 14:00 hôm nay
            return $reset2pm->setTimezone('UTC');
        } else {
            // Sau 14:00 → reset tiếp theo là 02:00 ngày mai
            return $reset2am->addDay()->setTimezone('UTC');
        }
    }

    // =============================================
    // II. CHI TIẾT ĐẠI LÝ
    // =============================================

    public function getAgencyInfo(): array
    {
        $result = $this->get('/api/agency/get-info');
        return $result['data'] ?? $result;
    }

    // =============================================
    // III. THÔNG TIN SẢN PHẨM
    // =============================================

    /**
     * Lấy danh sách sản phẩm từ H2Cloud
     *
     * Response structure:
     * {
     *   "error": 0,
     *   "products": {
     *     "vps": [
     *       {
     *         "group_product_name": "Đại lý cấp 2",
     *         "product": {
     *           "0": { "product_id": 107, "name": "...", "cpu": 1, "ram": 1, "disk": 20, "pricing": {...} },
     *           "limit-os": [],
     *           "1": { "product_id": 87, ... }
     *         }
     *       }
     *     ]
     *   }
     * }
     */
    public function getProducts(): array
    {
        $result = $this->get('/api/agency/get-product');
        $products = $result['products'] ?? [];

        // Flatten: products.vps[].product.{0,1,2...} → flat array
        $flatProducts = [];

        foreach ($products as $category => $groups) {
            // $category = "vps", "proxy", etc.
            if (!is_array($groups)) continue;

            foreach ($groups as $group) {
                if (!is_array($group)) continue;

                $groupName = $group['group_product_name'] ?? '';
                $productItems = $group['product'] ?? [];

                if (!is_array($productItems)) continue;

                foreach ($productItems as $key => $item) {
                    // Bỏ qua "limit-os" và các key không phải sản phẩm
                    if (!is_array($item) || !isset($item['product_id'])) continue;

                    // Thêm metadata
                    $item['_category'] = $category;       // "vps", "proxy"
                    $item['_group_name'] = $groupName;     // "Đại lý cấp 2"
                    $flatProducts[] = $item;
                }
            }
        }

        return $flatProducts;
    }

    /**
     * Lấy danh sách OS
     * H2Cloud response: {"error": 0, "os-vps": [
     *   {"os-id": 1, "os-name": "Windows Server 2012 R2"},
     *   {"os-id": 2, "os-name": "Windows Server 2016"},
     *   {"os-id": 3, "os-name": "Linux CentOS 7 64bit"},
     *   ...
     * ]}
     */
    public function getOperatingSystems(): array
    {
        $result = $this->get('/api/agency/get-list-os');
        $osData = $result['os-vps'] ?? [];

        $flatOs = [];
        foreach ($osData as $os) {
            if (is_array($os)) {
                $flatOs[] = [
                    'id' => $os['os-id'] ?? $os['id'] ?? null,
                    'name' => $os['os-name'] ?? $os['name'] ?? 'Unknown',
                ];
            }
        }

        return $flatOs;
    }

    public function getBillingCycles(): array
    {
        $result = $this->get('/api/agency/get-list-billing-cycle');
        return $result['billing-cycle'] ?? [];
    }

    public function getStates(): array
    {
        $result = $this->get('/api/agency/order/get-state');
        return $result['state'] ?? [];
    }

    // =============================================
    // V. GIAO DỊCH
    // =============================================

    public function getTransactions(): array
    {
        $result = $this->get('/api/agency/order/get-list-transaction');
        return $result['transactions'] ?? [];
    }

    // =============================================
    // V.2 GIAO DỊCH TẠO MỚI VPS VN
    // =============================================

    public function createVpsVN(array $params): array
    {
        // Params: product-id, billing-cycle, OS, quantity, addon-cpu, addon-ram, addon-disk
        $body = [
            'product-id' => $params['product_id'],
            'billing-cycle' => $params['billing_cycle'],
            'os' => $params['os'] ?? '',
            'quantity' => $params['quantity'] ?? 1,
            'addon-cpu' => $params['addon_cpu'] ?? 0,
            'addon-ram' => $params['addon_ram'] ?? 0,
            'addon-disk' => $params['addon_disk'] ?? 0,
        ];

        Log::info('H2Cloud createVpsVN request body', $body);

        $result = $this->post('/api/agency/order/create-order', $body);

        Log::info('H2Cloud createVpsVN response', $result);

        return $result;
    }

    // =============================================
    // V.3 GIAO DỊCH TẠO MỚI VPS NN
    // =============================================

    public function createVpsNN(array $params): array
    {
        // Params: product-id, billing-cycle, state, quantity
        $body = [
            'product-id' => $params['product_id'],
            'billing-cycle' => $params['billing_cycle'],
            'state' => $params['state'] ?? '',
            'quantity' => $params['quantity'] ?? 1,
        ];

        $result = $this->post('/api/agency/order/create-order-vps-nn', $body);

        Log::info('H2Cloud createVpsNN response', $result);

        return $result;
    }

    // =============================================
    // VI. VPS VN
    // =============================================

    public function getListVpsVN(string $type = 'all', int $quantity = 30, int $page = 0): array
    {
        $result = $this->get('/api/agency/vps/get-list-vps', [
            'type' => $type,
            'qtt' => $quantity,
            'page' => $page,
        ]);

        return $result;
    }

    public function getInfoVpsVN($vpsId): array
    {
        $result = $this->getWithBody('/api/agency/vps/get-info-vps', [
            'vps-id' => (int) $vpsId,
        ]);

        return $result['data'] ?? $result;
    }

    public function actionVpsVN(string $vpsId, string $action, array $extra = []): array
    {
        $body = array_merge([
            'vps-id' => (int) $vpsId,
            'action' => (string) $action,
        ], $this->formatExtraParams($extra));

        $result = $this->post('/api/agency/vps/action-vps', $body);

        Log::info("H2Cloud actionVpsVN [{$action}] on VPS #{$vpsId}", $result);

        return $result;
    }

    // =============================================
    // VII. VPS NN
    // =============================================

    public function getListVpsNN(string $type = 'all', int $quantity = 30, int $page = 0): array
    {
        $result = $this->get('/api/agency/vps/get-list-vps-nn', [
            'type' => $type,
            'qtt' => $quantity,
            'page' => $page,
        ]);

        return $result;
    }

    public function getInfoVpsNN($vpsId): array
    {
        $result = $this->getWithBody('/api/agency/vps/get-info-vps-nn', [
            'vps-id' => (int) $vpsId,
        ]);

        return $result['data'] ?? $result;
    }

    public function actionVpsNN(string $vpsId, string $action, array $extra = []): array
    {
        $body = array_merge([
            'vps-id' => (int) $vpsId,
            'action' => (string) $action,
        ], $this->formatExtraParams($extra));

        $result = $this->post('/api/agency/vps/action-vps-nn', $body);

        Log::info("H2Cloud actionVpsNN [{$action}] on VPS #{$vpsId}", $result);

        return $result;
    }

    // =============================================
    // Private Helpers
    // =============================================

    /**
     * Format extra parameters (addon, os-id, billing-cycle...) sang dạng key có dấu gạch
     */
    private function formatExtraParams(array $extra): array
    {
        $mapping = [
            'os_id' => 'os-id',
            'addon_cpu' => 'addon-cpu',
            'addon_ram' => 'addon-ram',
            'addon_disk' => 'addon-disk',
            'billing_cycle' => 'billing-cycle',
        ];

        $formatted = [];
        foreach ($extra as $key => $value) {
            $apiKey = $mapping[$key] ?? $key;
            $formatted[$apiKey] = $value;
        }

        return $formatted;
    }
}
