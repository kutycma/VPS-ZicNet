@extends('layouts.admin')
@section('title', 'Chi tiết VPS')
@section('page_title', 'VPS #' . ($instance->vps_provider_id ?? $instance->id))
@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Thông tin VPS</h3></div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr><td class="text-muted" width="150">User</td><td>
                        <strong>{{ $instance->user->name ?? 'N/A' }}</strong> ({{ $instance->user->email ?? '' }})
                    </td></tr>
                    <tr><td class="text-muted">Gói</td><td>{{ $instance->plan->name ?? 'N/A' }}</td></tr>
                    <tr><td class="text-muted">IP</td><td><code>{{ $instance->ip_address ?? '—' }}</code></td></tr>
                    <tr><td class="text-muted">OS</td><td>{{ $instance->os ?? '—' }}</td></tr>
                    <tr><td class="text-muted">Username</td><td><code>{{ $instance->username ?? '—' }}</code></td></tr>
                    <tr><td class="text-muted">Password</td><td><code>{{ $instance->password ?? '—' }}</code></td></tr>
                    <tr><td class="text-muted">Trạng thái</td><td><span class="badge badge-{{ $instance->status_badge }}">{{ $instance->status }}</span></td></tr>
                    <tr><td class="text-muted">Auto renew</td><td>{{ $instance->auto_renew ? 'Bật' : 'Tắt' }}</td></tr>
                    <tr><td class="text-muted">Hạn</td><td>{{ $instance->expires_at ? $instance->expires_at->format('d/m/Y H:i') : '—' }} @if(isset($instance->provider_data['day-left'])) <span class="text-info ml-2">({{ $instance->provider_data['day-left'] }})</span> @endif</td></tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-gamepad mr-2"></i>Điều khiển</h3></div>
            <div class="card-body text-center">
                @php
                    $pendingOrders = $instance->orders->where('status', 'pending');
                @endphp
                @if($pendingOrders->isNotEmpty())
                    <div class="alert alert-warning mb-3 py-2 text-left" style="border-radius: 10px;">
                        <i class="fas fa-spinner fa-spin mr-2"></i> <strong>Hệ thống đang xử lý cấu hình...</strong> (Tiến trình đang chạy ngầm)
                    </div>
                @endif
                <div class="mb-3" id="vpsActionsContainer">
                    <form action="{{ route('admin.vps.action', $instance) }}" method="POST" class="d-inline ajax-action-form">@csrf
                        <input type="hidden" name="action" value="on">
                        <button type="submit" class="btn btn-success m-1"><i class="fas fa-play mr-1"></i> Bật</button>
                    </form>
                    <form action="{{ route('admin.vps.action', $instance) }}" method="POST" class="d-inline ajax-action-form">@csrf
                        <input type="hidden" name="action" value="off">
                        <button type="submit" class="btn btn-warning m-1"><i class="fas fa-stop mr-1"></i> Tắt</button>
                    </form>
                    <form action="{{ route('admin.vps.action', $instance) }}" method="POST" class="d-inline ajax-action-form">@csrf
                        <input type="hidden" name="action" value="restart">
                        <button type="submit" class="btn btn-info m-1"><i class="fas fa-redo mr-1"></i> Restart</button>
                    </form>
                    
                    @if($instance->auto_renew)
                        <form action="{{ route('admin.vps.action', $instance) }}" method="POST" class="d-inline ajax-action-form" id="autoRenewFormOff">@csrf
                            <input type="hidden" name="action" value="off-auto-renew">
                            <button type="submit" class="btn btn-secondary m-1"><i class="fas fa-sync-alt mr-1"></i> Tắt Auto-Renew</button>
                        </form>
                    @else
                        <form action="{{ route('admin.vps.action', $instance) }}" method="POST" class="d-inline ajax-action-form" id="autoRenewFormOn">@csrf
                            <input type="hidden" name="action" value="on-auto-renew">
                            <button type="submit" class="btn btn-primary m-1"><i class="fas fa-sync-alt mr-1"></i> Bật Auto-Renew</button>
                        </form>
                    @endif
                </div>

                <hr>

                <div class="mb-2">
                    <a href="{{ route('admin.vps.rebuild', $instance) }}" class="btn btn-dark m-1"><i class="fas fa-compact-disc mr-1"></i> Cài lại OS</a>
                    <a href="{{ route('admin.vps.renew', $instance) }}" class="btn btn-success m-1"><i class="fas fa-calendar-plus mr-1"></i> Gia Hạn</a>
                    <a href="{{ route('admin.vps.upgrade', $instance) }}" class="btn btn-warning m-1"><i class="fas fa-arrow-up mr-1"></i> Nâng Cấp</a>
                </div>
                
                <form action="{{ route('admin.vps.action', $instance) }}" method="POST" class="d-inline" onsubmit="return confirm('Hủy VPS này? Hành động này không thể hoàn tác!')">@csrf
                    <input type="hidden" name="action" value="cancel">
                    <button class="btn btn-danger btn-sm m-1 mt-3"><i class="fas fa-trash mr-1"></i> Xóa & Phá Hủy VPS</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.ajax-action-form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var url = form.attr('action');
        var data = form.serialize();
        var btn = form.find('button[type="submit"]');
        var originalText = btn.html();

        btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

        $.ajax({
            type: "POST",
            url: url,
            data: data,
            dataType: 'json',
            success: function(res) {
                btn.html(originalText).prop('disabled', false);
                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Thành công',
                        text: res.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    
                    // Force a simple page reload after 1.5s so everything is fresh (status badges, auto renew text, etc)
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi',
                        text: res.message || 'Có lỗi xảy ra!'
                    });
                }
            },
            error: function(err) {
                btn.html(originalText).prop('disabled', false);
                let msg = 'Lỗi kết nối hoặc thao tác thất bại!';
                if (err.responseJSON && err.responseJSON.message) {
                    msg = err.responseJSON.message;
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi',
                    text: msg
                });
            }
        });
    });
});
</script>
@endpush
