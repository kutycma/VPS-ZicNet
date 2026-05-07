@extends('layouts.admin')
@section('title', 'Nhóm VPS')
@section('page_title', 'Quản lý nhóm VPS (Tabs)')
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Danh sách nhóm</h3>
        <a href="{{ route('admin.plan-groups.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> Thêm nhóm</a>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th width="50">#</th>
                    <th>Icon</th>
                    <th>Tên nhóm</th>
                    <th>Slug</th>
                    <th>Số gói</th>
                    <th>Thứ tự</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($groups as $group)
                <tr>
                    <td>{{ $group->id }}</td>
                    <td><i class="{{ $group->icon }}" style="font-size:1.2rem;color:#6366f1;"></i></td>
                    <td><strong>{{ $group->name }}</strong></td>
                    <td><code>{{ $group->slug }}</code></td>
                    <td><span class="badge badge-info">{{ $group->plans_count }}</span></td>
                    <td>{{ $group->sort_order }}</td>
                    <td><span class="badge badge-{{ $group->status === 'active' ? 'success' : 'danger' }}">{{ $group->status }}</span></td>
                    <td>
                        <a href="{{ route('admin.plan-groups.edit', $group) }}" class="btn btn-sm btn-outline-warning"><i class="fas fa-edit"></i></a>
                        <form action="{{ route('admin.plan-groups.destroy', $group) }}" method="POST" style="display:inline;" onsubmit="return confirm('Xóa nhóm này?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center py-4 text-muted">Chưa có nhóm nào. Tạo nhóm đầu tiên!</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $groups->links() }}
    </div>
</div>

<div class="callout callout-info mt-3">
    <h5><i class="fas fa-info-circle mr-1"></i> Hướng dẫn</h5>
    <p class="mb-0">Nhóm VPS sẽ hiển thị dưới dạng các <strong>tab</strong> trên trang chọn gói VPS của khách hàng. Bạn có thể gán nhóm mặc định cho nhà cung cấp tại mục <strong>Cài đặt NCC</strong>, hoặc gán thủ công cho từng gói tại <strong>Sửa gói VPS</strong>.</p>
</div>
@endsection
