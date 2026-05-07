@extends('layouts.admin')
@section('title', 'Dashboard')
@section('page_title', 'Dashboard')

@section('content')
<!-- Stats -->
<div class="row">
    <div class="col-lg-4 col-md-6">
        <div class="stat-card mb-4" style="--bg1:#6366f1;--bg2:#8b5cf6;">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <p class="mb-1" style="font-size:0.85rem;opacity:0.8;">Tổng người dùng</p>
            <h3 class="mb-0" style="font-weight:700;">{{ number_format($stats['total_users']) }}</h3>
        </div>
    </div>
    <div class="col-lg-4 col-md-6">
        <div class="stat-card mb-4" style="--bg1:#10b981;--bg2:#059669;">
            <div class="stat-icon"><i class="fas fa-server"></i></div>
            <p class="mb-1" style="font-size:0.85rem;opacity:0.8;">VPS đang hoạt động</p>
            <h3 class="mb-0" style="font-weight:700;">{{ number_format($stats['active_vps']) }} / {{ number_format($stats['total_vps']) }}</h3>
        </div>
    </div>
    <div class="col-lg-4 col-md-6">
        <div class="stat-card mb-4" style="--bg1:#f59e0b;--bg2:#d97706;">
            <div class="stat-icon"><i class="fas fa-coins"></i></div>
            <p class="mb-1" style="font-size:0.85rem;opacity:0.8;">Tổng doanh thu</p>
            <h3 class="mb-0" style="font-weight:700;">{{ number_format($stats['total_revenue'], 0, ',', '.') }}đ</h3>
        </div>
    </div>
</div>

<!-- H2Cloud Balance + Pending -->
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-cloud mr-2" style="color:#6366f1;"></i>H2Cloud - Số dư đại lý</h3></div>
            <div class="card-body">
                @if($h2cloudInfo && !isset($h2cloudInfo['error']))
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted">Tên đại lý</span>
                        <strong>{{ $h2cloudInfo['agency_name'] ?? 'N/A' }}</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted">Số dư</span>
                        <strong style="color:#10b981; font-size:1.2rem;">{{ number_format($h2cloudInfo['credit'] ?? 0, 0, ',', '.') }}đ</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted">Tổng dịch vụ</span>
                        <strong>{{ $h2cloudInfo['total_service'] ?? 0 }}</strong>
                    </div>
                @elseif($h2cloudInfo && isset($h2cloudInfo['error']))
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-exclamation-triangle text-warning mr-2"></i> {{ $h2cloudInfo['error'] }}
                    </div>
                @else
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-plug mr-2"></i> Chưa cấu hình nhà cung cấp H2Cloud
                        <br><a href="{{ route('admin.providers.create') }}" class="btn btn-primary btn-sm mt-2">Thêm nhà cung cấp</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-clock mr-2" style="color:#f59e0b;"></i>Thông tin nhanh</h3></div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted">Đơn hàng chờ xử lý</span>
                    <span class="badge badge-warning" style="font-size:1rem;">{{ $stats['pending_orders'] }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted">Tổng nạp tiền</span>
                    <strong style="color:#6366f1;">{{ number_format($stats['total_deposits'], 0, ',', '.') }}đ</strong>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Orders -->
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-shopping-bag mr-2"></i>Đơn hàng gần đây</h3></div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover">
                    <thead><tr><th>User</th><th>Gói</th><th>Số tiền</th><th>Trạng thái</th><th>Thời gian</th></tr></thead>
                    <tbody>
                        @forelse($recentOrders as $order)
                        <tr>
                            <td>{{ $order->user->name ?? 'N/A' }}</td>
                            <td>{{ $order->vpsPlan->name ?? 'N/A' }}</td>
                            <td>{{ number_format($order->amount, 0, ',', '.') }}đ</td>
                            <td><span class="badge badge-{{ $order->status_badge }}">{{ $order->status }}</span></td>
                            <td>{{ $order->created_at->diffForHumans() }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">Chưa có đơn hàng</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-exchange-alt mr-2"></i>Giao dịch gần đây</h3></div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover">
                    <thead><tr><th>User</th><th>Loại</th><th>Số tiền</th><th>Thời gian</th></tr></thead>
                    <tbody>
                        @forelse($recentTransactions as $tx)
                        <tr>
                            <td>{{ $tx->user->name ?? 'N/A' }}</td>
                            <td><span class="badge badge-{{ $tx->type_badge }}">{{ $tx->type_label }}</span></td>
                            <td style="color:{{ $tx->amount >= 0 ? '#10b981' : '#ef4444' }}; font-weight:600;">
                                {{ $tx->formatted_amount }}
                            </td>
                            <td>{{ $tx->created_at->diffForHumans() }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">Chưa có giao dịch</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
