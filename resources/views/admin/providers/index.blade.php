@extends('layouts.admin')
@section('title', 'Nhà cung cấp')
@section('page_title', 'Quản lý nhà cung cấp VPS')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Danh sách nhà cung cấp</h3>
        <a href="{{ route('admin.providers.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> Thêm mới</a>
    </div>
    <div class="card-body table-responsive">
        <table class="table table-hover">
            <thead>
                <tr><th>Tên</th><th>Endpoint</th><th>Markup</th><th>Số gói</th><th>Trạng thái</th><th>Thao tác</th></tr>
            </thead>
            <tbody>
                @forelse($providers as $provider)
                <tr>
                    <td><strong>{{ $provider->name }}</strong><br><small class="text-muted">{{ $provider->slug }}</small></td>
                    <td><code>{{ $provider->api_endpoint }}</code></td>
                    <td>
                        @if($provider->markup_type === 'percentage')
                            <span class="badge badge-info">+{{ $provider->markup_value }}%</span>
                        @elseif($provider->markup_type === 'fixed')
                            <span class="badge badge-info">+{{ number_format($provider->markup_value, 0, ',', '.') }}đ</span>
                        @else
                            <span class="badge badge-secondary">Thủ công</span>
                        @endif
                    </td>
                    <td><span class="badge badge-primary">{{ $provider->plans_count }} gói</span></td>
                    <td><span class="badge badge-{{ $provider->status === 'active' ? 'success' : 'danger' }}">{{ $provider->status }}</span></td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary btn-test-connection" data-id="{{ $provider->id }}" title="Test kết nối"><i class="fas fa-plug"></i></button>
                            <form action="{{ route('admin.providers.sync-plans', $provider) }}" method="POST" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn btn-outline-success" title="Đồng bộ gói VPS"><i class="fas fa-boxes"></i></button>
                            </form>
                            <form action="{{ route('admin.providers.sync-instances', $provider) }}" method="POST" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn btn-outline-info" title="Lấy danh sách VPS từ NCC"><i class="fas fa-cloud-download-alt"></i></button>
                            </form>
                            <a href="{{ route('admin.providers.edit', $provider) }}" class="btn btn-outline-warning" title="Sửa"><i class="fas fa-edit"></i></a>
                            <form action="{{ route('admin.providers.destroy', $provider) }}" method="POST" style="display:inline;" onsubmit="return confirm('Bạn chắc chắn?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger" title="Xóa"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-4 text-muted"><i class="fas fa-server fa-2x mb-2 d-block"></i>Chưa có nhà cung cấp nào</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
$('.btn-test-connection').click(function() {
    var btn = $(this);
    var id = btn.data('id');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

    $.post('/admin/providers/' + id + '/test-connection', function(res) {
        var detailHtml = '';
        var logHtml = '';
        if (res.details) {
            detailHtml = '<div style="text-align:left;margin-top:15px;background:#f8fafc;border-radius:8px;padding:12px;font-size:0.85rem;">';
            $.each(res.details, function(key, val) {
                if (key === 'recent_log') {
                    if (val && val !== 'Không có log') {
                        logHtml = '<div style="text-align:left;margin-top:10px;"><strong style="font-size:0.8rem;color:#64748b;">📋 Log chi tiết:</strong><pre style="background:#1e293b;color:#10b981;padding:10px;border-radius:8px;font-size:0.75rem;max-height:200px;overflow:auto;white-space:pre-wrap;margin-top:5px;">' + $('<div>').text(val).html() + '</pre></div>';
                    }
                    return;
                }
                var label = {
                    'token': 'Token', 'endpoint': 'Endpoint', 'username': 'Username',
                    'response_time': 'Thời gian phản hồi', 'error_type': 'Loại lỗi',
                    'app': 'API App', 'file': 'File:Line'
                }[key] || key;
                detailHtml += '<div style="display:flex;justify-content:space-between;margin-bottom:4px;"><span style="color:#64748b;">' + label + '</span><code style="color:#1e293b;word-break:break-all;">' + val + '</code></div>';
            });
            detailHtml += '</div>';
        }

        if (res.success) {
            Swal.fire({
                icon: 'success',
                title: 'Kết nối thành công!',
                html: res.message + detailHtml,
                confirmButtonColor: '#6366f1'
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Kết nối thất bại!',
                html: '<p style="color:#ef4444;font-weight:600;">' + res.message + '</p>' + detailHtml + logHtml,
                confirmButtonColor: '#ef4444',
                width: logHtml ? '700px' : '500px'
            });
        }
    }).fail(function(xhr) {
        var msg = 'Không thể kết nối đến server';
        if (xhr.status) msg += ' (HTTP ' + xhr.status + ')';
        if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
        Swal.fire({ icon: 'error', title: 'Lỗi!', text: msg, confirmButtonColor: '#ef4444' });
    }).always(function() {
        btn.prop('disabled', false).html('<i class="fas fa-plug"></i>');
    });
});
</script>
@endpush
