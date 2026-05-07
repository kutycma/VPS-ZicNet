@extends('layouts.admin')
@section('title', 'Quản lý nạp tiền')
@section('page_title', 'Quản lý nạp tiền')
@section('content')

{{-- Stats --}}
<div class="row mb-4">
    <div class="col-md-4">
        <div class="stat-card" style="--bg1:#f59e0b;--bg2:#d97706;">
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
            <p class="mb-1" style="font-size:0.85rem; opacity:0.8;">Đang chờ xử lý</p>
            <h3 class="mb-0" style="font-weight:700;">{{ $stats['pending'] }}</h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card" style="--bg1:#10b981;--bg2:#059669;">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <p class="mb-1" style="font-size:0.85rem; opacity:0.8;">Hoàn thành hôm nay</p>
            <h3 class="mb-0" style="font-weight:700;">{{ $stats['completed_today'] }}</h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card" style="--bg1:#6366f1;--bg2:#8b5cf6;">
            <div class="stat-icon"><i class="fas fa-coins"></i></div>
            <p class="mb-1" style="font-size:0.85rem; opacity:0.8;">Tổng nạp hôm nay</p>
            <h3 class="mb-0" style="font-weight:700;">{{ number_format((float)$stats['total_today'], 0, ',', '.') }}đ</h3>
        </div>
    </div>
</div>

<div class="card" style="border-radius:16px;">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
        <h3 class="card-title"><i class="fas fa-money-bill-wave mr-2"></i>Danh sách yêu cầu nạp tiền</h3>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            {{-- Manual check button --}}
            <form action="{{ route('admin.deposits.check-now') }}" method="POST" style="display:inline;">
                @csrf
                <button type="submit" class="btn btn-sm btn-info" style="border-radius:8px;">
                    <i class="fas fa-sync-alt mr-1"></i> Kiểm tra bank ngay
                </button>
            </form>

            {{-- Filter --}}
            <div class="btn-group">
                <a href="{{ route('admin.deposits.index') }}" class="btn btn-sm {{ !request('status') ? 'btn-primary' : 'btn-outline-primary' }}" style="border-radius:8px 0 0 8px;">Tất cả</a>
                <a href="{{ route('admin.deposits.index', ['status' => 'pending']) }}" class="btn btn-sm {{ request('status') === 'pending' ? 'btn-warning' : 'btn-outline-warning' }}">Pending</a>
                <a href="{{ route('admin.deposits.index', ['status' => 'completed']) }}" class="btn btn-sm {{ request('status') === 'completed' ? 'btn-success' : 'btn-outline-success' }}">Completed</a>
                <a href="{{ route('admin.deposits.index', ['status' => 'expired']) }}" class="btn btn-sm {{ request('status') === 'expired' ? 'btn-secondary' : 'btn-outline-secondary' }}" style="border-radius:0 8px 8px 0;">Expired</a>
            </div>
        </div>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Mã GD</th>
                    <th>User</th>
                    <th>Yêu cầu</th>
                    <th>Thực nhận</th>
                    <th>Trạng thái</th>
                    <th>Hết hạn</th>
                    <th>Ghi chú</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                @forelse($deposits as $d)
                <tr style="{{ $d->status === 'pending' && !$d->expires_at->isPast() ? 'background:#fffbeb;' : '' }}">
                    <td>{{ $d->id }}</td>
                    <td>
                        <code style="font-weight:700; color:#6366f1; font-size:0.95rem;">{{ $d->transaction_code }}</code>
                    </td>
                    <td>
                        <div>
                            <strong>{{ $d->user->name ?? '—' }}</strong><br>
                            <small class="text-muted">{{ $d->user->email ?? '—' }}</small>
                        </div>
                    </td>
                    <td style="font-weight:600;">{{ number_format((float)$d->amount, 0, ',', '.') }}đ</td>
                    <td style="font-weight:600; color:#10b981;">
                        {{ $d->actual_amount ? number_format((float)$d->actual_amount, 0, ',', '.') . 'đ' : '—' }}
                    </td>
                    <td>
                        <span class="badge badge-{{ $d->status_badge }}">{{ $d->status_label }}</span>
                        @if($d->status === 'pending' && $d->expires_at->isPast())
                            <br><small class="text-danger">Đã quá hạn</small>
                        @endif
                    </td>
                    <td>
                        <small>{{ $d->expires_at->format('d/m H:i') }}</small><br>
                        <small class="text-muted">Tạo: {{ $d->created_at->format('d/m H:i') }}</small>
                    </td>
                    <td>
                        @if($d->bank_transaction_id)
                            <small class="text-muted">Bank TX: {{ $d->bank_transaction_id }}</small><br>
                        @endif
                        @if($d->admin_note)
                            <small class="text-info">{{ $d->admin_note }}</small>
                        @endif
                    </td>
                    <td>
                        @if(in_array($d->status, ['pending', 'expired']))
                        {{-- Approve button --}}
                        <button type="button" class="btn btn-sm btn-success mb-1" style="border-radius:8px;"
                            onclick="approveDeposit({{ $d->id }}, '{{ $d->transaction_code }}', {{ (float)$d->amount }})">
                            <i class="fas fa-check"></i> Duyệt
                        </button>
                        {{-- Reject button --}}
                        <form action="{{ route('admin.deposits.reject', $d) }}" method="POST" style="display:inline;" onsubmit="return confirm('Từ chối deposit {{ $d->transaction_code }}?')">
                            @csrf
                            <input type="hidden" name="admin_note" value="Từ chối bởi Admin">
                            <button class="btn btn-sm btn-outline-danger" style="border-radius:8px;">
                                <i class="fas fa-times"></i>
                            </button>
                        </form>
                        @elseif($d->status === 'completed')
                        <span class="text-success"><i class="fas fa-check-circle"></i></span>
                        @else
                        <span class="text-muted">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center py-4 text-muted">
                        <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                        Chưa có yêu cầu nạp tiền nào
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        {{ $deposits->withQueryString()->links() }}
    </div>
</div>

{{-- Approve Modal --}}
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="approveForm">
            @csrf
            <div class="modal-content" style="border-radius:16px;">
                <div class="modal-header" style="border-bottom:1px solid #f1f5f9;">
                    <h5 class="modal-title"><i class="fas fa-check-circle text-success mr-2"></i>Duyệt nạp tiền thủ công</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <p>Mã giao dịch: <strong id="modalTxCode" class="text-primary"></strong></p>
                    <div class="form-group">
                        <label>Số tiền thực nhận (VNĐ) <span class="text-danger">*</span></label>
                        <input type="number" name="actual_amount" id="modalAmount" class="form-control" required min="1000" step="1000" style="border-radius:10px;">
                    </div>
                    <div class="form-group">
                        <label>Ghi chú admin</label>
                        <input type="text" name="admin_note" class="form-control" placeholder="VD: Duyệt thủ công, đã kiểm tra bank" style="border-radius:10px;">
                    </div>
                    <div class="alert alert-warning" style="border-radius:10px;">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        <strong>Lưu ý:</strong> Tiền sẽ được cộng ngay vào tài khoản user. Hãy xác nhận đã nhận được tiền từ bank.
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #f1f5f9;">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" style="border-radius:8px;">Hủy</button>
                    <button type="submit" class="btn btn-success" style="border-radius:8px;">
                        <i class="fas fa-check mr-1"></i> Xác nhận duyệt
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function approveDeposit(id, txCode, amount) {
    document.getElementById('modalTxCode').textContent = txCode;
    document.getElementById('modalAmount').value = amount;
    document.getElementById('approveForm').action = '/admin/deposits/' + id + '/approve';
    $('#approveModal').modal('show');
}
</script>
@endpush
@endsection
