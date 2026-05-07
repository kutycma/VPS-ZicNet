<?php

namespace App\Services\Vps\Providers;

use App\Models\VpsProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class BaseApiProvider
{
    protected VpsProvider $provider;

    public function __construct(VpsProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Headers chung cho mọi request (sau khi authenticate)
     */
    protected function getHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'api-username' => $this->provider->api_username,
            'api-app' => $this->provider->api_app,
            'api-secret' => $this->provider->api_secret,
            'auth-token' => $this->getValidToken(),
        ];
    }

    /**
     * Headers cho request authenticate (không cần auth-token)
     */
    protected function getAuthHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'api-username' => $this->provider->api_username,
            'api-app' => $this->provider->api_app,
            'api-secret' => $this->provider->api_secret,
        ];
    }

    /**
     * Lấy token hợp lệ, tự động refresh nếu hết hạn
     */
    protected function getValidToken(bool $forceRefresh = false): string
    {
        if (!$forceRefresh && $this->provider->fresh()->isTokenValid()) {
            return $this->provider->auth_token;
        }

        return $this->authenticate();
    }

    /**
     * Kiểm tra xem lỗi có phải do token hết hạn không
     */
    protected function isTokenError(array $result = null, int $httpStatus = 0): bool
    {
        // HTTP 401/403 = unauthorized
        if (in_array($httpStatus, [401, 403])) {
            return true;
        }

        // H2Cloud error code 2 = auth error
        if ($result && isset($result['error']) && $result['error'] == 2) {
            return true;
        }

        // Check message keywords
        if ($result && isset($result['message'])) {
            $msg = strtolower($result['message']);
            if (strpos($msg, 'token') !== false || strpos($msg, 'auth') !== false || strpos($msg, 'unauthorized') !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Mask secret để log an toàn
     */
    protected function maskHeaders(array $headers): array
    {
        $masked = $headers;
        if (isset($masked['api-secret'])) {
            $secret = $masked['api-secret'];
            $len = strlen($secret);
            if ($len > 8) {
                $masked['api-secret'] = substr($secret, 0, 4) . str_repeat('*', $len - 8) . substr($secret, -4);
            } else {
                $masked['api-secret'] = '***';
            }
        }
        if (isset($masked['auth-token'])) {
            $token = $masked['auth-token'];
            $masked['auth-token'] = substr($token, 0, 8) . '***';
        }
        return $masked;
    }

    /**
     * Gọi API POST (auto-retry on token error)
     */
    protected function post(string $endpoint, array $data = [], bool $withAuth = true): array
    {
        return $this->doPost($endpoint, $data, $withAuth, false);
    }

    private function doPost(string $endpoint, array $data, bool $withAuth, bool $isRetry): array
    {
        $url = rtrim($this->provider->api_endpoint, '/') . $endpoint;
        $headers = $withAuth ? $this->getHeaders() : $this->getAuthHeaders();

        Log::info("H2Cloud API POST [{$endpoint}]" . ($isRetry ? ' [RETRY]' : ''), [
            'url' => $url,
            'headers' => $this->maskHeaders($headers),
            'body' => $data,
        ]);

        try {
            $response = Http::withHeaders($headers)
                ->timeout(30)
                ->post($url, $data);

            $httpStatus = $response->status();
            $rawBody = $response->body();
            $result = $response->json();

            Log::info("H2Cloud Response [{$endpoint}]", [
                'http_status' => $httpStatus,
                'body_length' => strlen($rawBody),
                'body_preview' => substr($rawBody, 0, 500),
            ]);

            // Auto-retry on token error (chỉ retry 1 lần, chỉ khi có auth)
            if ($withAuth && !$isRetry && $this->isTokenError($result, $httpStatus)) {
                Log::warning("H2Cloud: Token error on POST [{$endpoint}], refreshing token and retrying...");
                $this->getValidToken(true);
                return $this->doPost($endpoint, $data, $withAuth, true);
            }

            if ($httpStatus >= 400) {
                $msg = "HTTP {$httpStatus} từ {$endpoint}";
                if ($result && isset($result['message'])) {
                    $msg .= ': ' . $result['message'];
                } else {
                    $msg .= ' | Raw: ' . substr($rawBody, 0, 300);
                }
                throw new \Exception($msg);
            }

            if (!$result) {
                throw new \Exception("Response không phải JSON từ {$endpoint}. HTTP {$httpStatus}. Body: " . substr($rawBody, 0, 200));
            }

            if (isset($result['error']) && $result['error'] != 0) {
                $message = $result['message'] ?? 'Lỗi không xác định';
                Log::error("H2Cloud API Error [{$endpoint}]: {$message}", $result);
                throw new \Exception("H2Cloud: {$message}");
            }

            return $result;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error("H2Cloud Connection Error [{$endpoint}]: " . $e->getMessage());
            throw new \Exception("Không thể kết nối đến {$url}: " . $e->getMessage());
        }
    }

    /**
     * Gọi API GET (auto-retry on token error)
     */
    protected function get(string $endpoint, array $data = []): array
    {
        return $this->doGet($endpoint, $data, false);
    }

    private function doGet(string $endpoint, array $data, bool $isRetry): array
    {
        $url = rtrim($this->provider->api_endpoint, '/') . $endpoint;

        Log::info("H2Cloud API GET [{$endpoint}]" . ($isRetry ? ' [RETRY]' : ''), [
            'url' => $url,
            'params' => $data,
        ]);

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout(30)
                ->get($url, $data);

            $httpStatus = $response->status();
            $rawBody = $response->body();
            $result = $response->json();

            Log::info("H2Cloud Response [{$endpoint}]", [
                'http_status' => $httpStatus,
                'body_preview' => substr($rawBody, 0, 500),
            ]);

            // Auto-retry on token error
            if (!$isRetry && $this->isTokenError($result, $httpStatus)) {
                Log::warning("H2Cloud: Token error on GET [{$endpoint}], refreshing token and retrying...");
                $this->getValidToken(true);
                return $this->doGet($endpoint, $data, true);
            }

            if ($httpStatus >= 400) {
                $msg = "HTTP {$httpStatus} từ {$endpoint}";
                if ($result && isset($result['message'])) {
                    $msg .= ': ' . $result['message'];
                } else {
                    $msg .= ' | Raw: ' . substr($rawBody, 0, 300);
                }
                throw new \Exception($msg);
            }

            if (!$result) {
                throw new \Exception("Response không phải JSON từ {$endpoint}. HTTP {$httpStatus}. Body: " . substr($rawBody, 0, 200));
            }

            if (isset($result['error']) && $result['error'] != 0) {
                $message = $result['message'] ?? 'Lỗi không xác định';
                Log::error("H2Cloud API Error [{$endpoint}]: {$message}", $result);
                throw new \Exception("H2Cloud: {$message}");
            }

            return $result;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error("H2Cloud Connection Error [{$endpoint}]: " . $e->getMessage());
            throw new \Exception("Không thể kết nối đến {$url}: " . $e->getMessage());
        }
    }

    /**
     * Gọi API GET (Nhưng ép gửi body JSON - Dành cho các API dị của H2Cloud)
     * Auto-retry on token error
     */
    protected function getWithBody(string $endpoint, array $data = []): array
    {
        return $this->doGetWithBody($endpoint, $data, false);
    }

    private function doGetWithBody(string $endpoint, array $data, bool $isRetry): array
    {
        $url = rtrim($this->provider->api_endpoint, '/') . $endpoint;

        Log::info("H2Cloud API GET_WITH_BODY [{$endpoint}]" . ($isRetry ? ' [RETRY]' : ''), [
            'url' => $url,
            'body' => $data,
        ]);

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->withBody(json_encode($data), 'application/json')
                ->timeout(30)
                ->send('GET', $url);

            $httpStatus = $response->status();
            $rawBody = $response->body();
            $result = $response->json();

            Log::info("H2Cloud Response [{$endpoint}]", [
                'http_status' => $httpStatus,
                'body_preview' => substr($rawBody, 0, 500),
            ]);

            // Auto-retry on token error
            if (!$isRetry && $this->isTokenError($result, $httpStatus)) {
                Log::warning("H2Cloud: Token error on GET_WITH_BODY [{$endpoint}], refreshing token and retrying...");
                $this->getValidToken(true);
                return $this->doGetWithBody($endpoint, $data, true);
            }

            if ($httpStatus >= 400) {
                $msg = "HTTP {$httpStatus} từ {$endpoint}";
                throw new \Exception($msg);
            }

            if (!$result) {
                throw new \Exception("Response không phải JSON từ {$endpoint}. HTTP {$httpStatus}");
            }

            if (isset($result['error']) && $result['error'] != 0) {
                $message = $result['message'] ?? 'Lỗi không xác định';
                Log::error("H2Cloud API Error [{$endpoint}]: {$message}", $result);
                throw new \Exception("H2Cloud: {$message}");
            }

            return $result;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error("H2Cloud Connection Error [{$endpoint}]: " . $e->getMessage());
            throw new \Exception("Không thể kết nối đến {$url}: " . $e->getMessage());
        }
    }

    /**
     * Authenticate - phải implement ở class con
     */
    abstract public function authenticate(): string;
}
