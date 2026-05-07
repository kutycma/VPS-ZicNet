@extends('layouts.admin')
@section('title', 'Quản lý Đơn hàng')
@section('page_title', 'Tất cả Đơn hàng')
@section('content')

<div class="card">
    <div class="card-header border-bottom">
        <h4 class="card-title">Danh sách Đơn hàng</h4>
    </div>
    <div class="card-body">
        
        <form method="GET" action="{{ route('admin.orders.index') }}" class="mb-4 bg-light p-3 rounded" style="border: 1px solid #dee2e6;">
            <div class="row gx-2 gy-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small text-muted font-weight-bold mb-1">Khách hàng</label>
                    <input type="text" name="username" class="form-control form-control-sm" placeholder="Tên hoặc email..." value="{{ request('username') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted font-weight-bold mb-1">IP máy chủ</label>
                    <input type="text" name="ip" class="form-control form-control-sm" placeholder="Nhập địa chỉ IP..." value="{{ request('ip') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted font-weight-bold mb-1">Loại Đơn</label>
                    <select name="type" class="form-control form-control-sm">
                        <option value="">Tất cả</option>
                        <option value="new" {{ request('type') == 'new' ? 'selected' : '' }}>Mua mới (New)</option>
                        <option value="renew" {{ request('type') == 'renew' ? 'selected' : '' }}>Gia hạn (Renew)</option>
                        <option value="upgrade" {{ request('type') == 'upgrade' ? 'selected' : '' }}>Nâng cấp (Upgrade)</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted font-weight-bold mb-1">Hiển thị</label>
                    <select name="per_page" class="form-control form-control-sm">
                        <option value="10" {{ (request('per_page') ?? 10) == 10 ? 'selected' : '' }}>10 / trang</option>
                        <option value="20" {{ request('per_page') == 20 ? 'selected' : '' }}>20 / trang</option>
                        <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50 / trang</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-primary px-3 rounded-pill"><i class="fas fa-search mr-1"></i> Lọc</button>
                    <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-outline-secondary px-3 rounded-pill ml-1">Bỏ lọc</a>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th width="80">Mã ĐH</th>
                        <th>User</th>
                        <th>Gói Dịch Vụ</th>
                        <th>Cấu hình Mua (Payload)</th>
                        <th>Tổng tiền</th>
                        <th>Status</th>
                        <th>Lỗi/Ghi chú</th>
                        <th width="120">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td>#{{ $order->id }}</td>
                            <td>
                                <strong>{{ $order->user->name ?? 'N/A' }}</strong><br>
                                <small class="text-muted">{{ $order->user->email ?? '' }}</small>
                            </td>
                            <td>
                                @if($order->vpsPlan)
                                    <span class="badge bg-primary">{{ $order->vpsPlan->name }}</span>
                                    <br><small>{{ $order->billing_cycle }}</small>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                                
                                <div class="mt-1">
                                    @if($order->type === 'new')
                                        <span class="badge bg-success" style="font-size: 0.7rem;">Mua Mới</span>
                                    @elseif($order->type === 'renew')
                                        <span class="badge bg-info" style="font-size: 0.7rem;">Gia Hạn</span>
                                        @if($order->vpsInstance)
                                            <div class="small text-muted mt-1"><i class="fas fa-network-wired"></i> IP: <strong>{{ $order->vpsInstance->ip_address ?? 'N/A' }}</strong></div>
                                        @endif
                                    @elseif($order->type === 'upgrade')
                                        <span class="badge bg-warning text-dark" style="font-size: 0.7rem;">Nâng Cấp</span>
                                        @if($order->vpsInstance)
                                            <div class="small text-muted mt-1"><i class="fas fa-network-wired"></i> IP: <strong>{{ $order->vpsInstance->ip_address ?? 'N/A' }}</strong></div>
                                        @endif
                                    @else
                                        <span class="badge bg-secondary" style="font-size: 0.7rem;">{{ strtoupper($order->type) }}</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if(!empty($order->payload))
                                    @php $payload = is_string($order->payload) ? json_decode($order->payload, true) : $order->payload; @endphp
                                    <ul class="list-unstyled mb-0 small">
                                        <li><strong>HĐH:</strong> {{ $payload['os'] ?? 'N/A' }}</li>
                                        @if(!empty($payload['addon_cpu']) || !empty($payload['addon_ram']) || !empty($payload['addon_disk']))
                                        <li>
                                            <strong>Addon:</strong>
                                            @if(!empty($payload['addon_cpu'])) +{{ $payload['addon_cpu'] }} Core, @endif
                                            @if(!empty($payload['addon_ram'])) +{{ $payload['addon_ram'] }}GB RAM, @endif
                                            @if(!empty($payload['addon_disk'])) +{{ $payload['addon_disk'] }}GB Disk @endif
                                        </li>
                                        @endif
                                    </ul>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-success font-weight-bold">{{ number_format((float)$order->amount) }}đ</td>
                            <td>
                                <span class="badge badge-{{ $order->status_badge }}">
                                    {{ strtoupper($order->status) }}
                                </span>
                            </td>
                            <td>
                                @if($order->status === 'pending' && $order->attempts > 0)
                                    <small class="text-danger d-block font-weight-bold mb-1"><i class="fas fa-exclamation-triangle"></i> Lỗi x{{ $order->attempts }}</small>
                                @endif
                                @if($order->notes)
                                    <textarea class="form-control form-control-sm text-muted" readonly style="min-width: 200px; height: 50px; font-size: 0.8rem; resize: both; background-color: #f8f9fa; border: 1px solid #dee2e6;">{{ $order->notes }}</textarea>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td>
                                @if($order->status === 'pending')
                                    <form action="{{ route('admin.orders.cancel', $order) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn huỷ đơn hàng này? Hệ thống sẽ HOÀN LẠI {{ number_format((float)$order->amount) }}đ cho khách hàng!');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-danger" title="Huỷ và Hoàn tiền">
                                            <i class="fas fa-times-circle"></i> Huỷ & Hoàn tiền
                                        </button>
                                    </form>
                                @else
                                    <span class="text-muted small">Không có HĐ</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">Chưa có đơn hàng nào</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-3">
            {{ $orders->links() }}
        </div>
    </div>
</div>
@endsection
