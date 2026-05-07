<?php

namespace App\Services\Vps\Contracts;

interface VpsProviderInterface
{
    /**
     * Authenticate và lấy token
     */
    public function authenticate(): string;

    /**
     * Lấy thông tin đại lý (balance, tổng dịch vụ...)
     */
    public function getAgencyInfo(): array;

    /**
     * Lấy danh sách sản phẩm
     */
    public function getProducts(): array;

    /**
     * Lấy danh sách hệ điều hành
     */
    public function getOperatingSystems(): array;

    /**
     * Lấy danh sách billing cycle (thời hạn thuê)
     */
    public function getBillingCycles(): array;

    /**
     * Lấy danh sách bang (VPS NN)
     */
    public function getStates(): array;

    // === VPS VN ===

    /**
     * Tạo VPS VN mới
     */
    public function createVpsVN(array $params): array;

    /**
     * Lấy danh sách VPS VN
     */
    public function getListVpsVN(string $type = 'all', int $quantity = 30, int $page = 0): array;

    /**
     * Lấy thông tin chi tiết VPS VN
     */
    public function getInfoVpsVN($vpsId): array;

    /**
     * Thực hiện action trên VPS VN (on, off, restart, cancel, rebuild, addon, renew...)
     */
    public function actionVpsVN(string $vpsId, string $action, array $extra = []): array;

    // === VPS NN ===

    /**
     * Tạo VPS NN mới
     */
    public function createVpsNN(array $params): array;

    /**
     * Lấy danh sách VPS NN
     */
    public function getListVpsNN(string $type = 'all', int $quantity = 30, int $page = 0): array;

    /**
     * Lấy thông tin chi tiết VPS NN
     */
    public function getInfoVpsNN($vpsId): array;

    /**
     * Thực hiện action trên VPS NN (on, off, restart, cancel, rebuild, renew...)
     */
    public function actionVpsNN(string $vpsId, string $action, array $extra = []): array;

    // === Giao dịch ===

    /**
     * Lấy danh sách giao dịch trên NCC
     */
    public function getTransactions(): array;
}
