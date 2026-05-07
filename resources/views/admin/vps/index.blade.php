@extends('layouts.admin')
@section('title', 'Quản lý VPS')
@section('page_title', 'Quản lý VPS Instances')
@section('content')
<div class="card">
    <div class="card-header">
        <form class="form-inline" method="GET">
            <input type="text" name="search" class="form-control form-control-sm mr-2" placeholder="Tìm IP, User..." value="{{ request('search') }}">
            <select name="status" class="form-control form-control-sm mr-2">
                <option value="">Tất cả trạng thái</option>
                @foreach(['active','stopped','pending','progressing','expired','cancelled'] as $s)<option value="{{ $s }}" {{ request('status')==$s?'selected':'' }}>{{ ucfirst($s) }}</option>@endforeach
            </select>
            <button class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
        </form>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover">
            <thead><tr><th>ID</th><th>User</th><th>Gói</th><th>IP</th><th>Trạng thái</th><th>Hạn</th><th>Thao tác</th></tr></thead>
            <tbody>
                @forelse($instances as $vps)
                <tr class="{{ ($vps->status === 'expired' || $vps->isExpired()) ? 'table-danger' : '' }}">
                    <td>#{{ $vps->vps_provider_id ?? $vps->id }}</td>
                    <td>{{ $vps->user->name ?? 'N/A' }}<br><small class="text-muted">{{ $vps->user->email ?? '' }}</small></td>
                    <td>{{ $vps->plan->name ?? 'N/A' }}</td>
                    <td><code>{{ $vps->ip_address ?? '—' }}</code></td>
                    <td><span class="badge badge-{{ $vps->status_badge }}">{{ $vps->status }}</span></td>
                    <td>
                        @if($vps->expires_at)
                            @if($vps->status === 'expired' || $vps->isExpired())
                                <span class="text-danger font-weight-bold"><i class="fas fa-exclamation-triangle"></i> Đã hết hạn</span><br>
                                <small class="text-muted"><del>{{ $vps->expires_at->format('d/m/Y') }}</del></small>
                            @else
                                <strong>{{ $vps->expires_at->format('d/m/Y') }}</strong>
                                @if(isset($vps->provider_data['day-left'])) <br><small class="text-success font-weight-bold">{{ $vps->provider_data['day-left'] }}</small> @endif
                            @endif
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('admin.vps.show', $vps) }}" class="btn btn-sm btn-outline-primary mb-1"><i class="fas fa-eye"></i></a>
                        <button class="btn btn-sm btn-outline-warning mb-1" onclick="openAssignModal('{{ route('admin.vps.assign', $vps) }}', '{{ $vps->vps_provider_id ?? $vps->id }}')"><i class="fas fa-exchange-alt"></i></button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center py-4 text-muted">Chưa có VPS nào</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-3">{{ $instances->links() }}</div>
    </div>
</div>

<!-- Assign User Modal -->
<div class="modal fade" id="assignModal" tabindex="-1" role="dialog" aria-labelledby="assignModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form action="" method="POST" id="assignForm">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title" id="assignModalLabel">Gán VPS <span id="assignVpsId"></span> cho Khách hàng khác</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="alert alert-warning">Thay đổi chủ sở hữu sẽ chuyển toàn quyền kiểm soát và thanh toán của máy chủ này sang tài khoản đích. Hãy cẩn trọng.</div>
            <div class="form-group">
                <label>Email của khách hàng nhận VPS</label>
                <input type="email" name="email" class="form-control" placeholder="Nhập email tài khoản khách hàng..." required>
                <small class="form-text text-muted">Hệ thống sẽ tra cứu khách hàng dựa trên Email đăng nhập của họ.</small>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            <button type="submit" class="btn btn-primary">Xác nhận chuyển quyền</button>
          </div>
      </form>
    </div>
  </div>
</div>

@push('scripts')
<script>
function openAssignModal(actionUrl, vpsId) {
    document.getElementById('assignForm').action = actionUrl;
    document.getElementById('assignVpsId').textContent = '#' + vpsId;
    $('#assignModal').modal('show');
}
</script>
@endpush
@endsection
