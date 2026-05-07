@extends('layouts.admin')
@section('title', 'Người dùng')
@section('page_title', 'Quản lý người dùng')
@section('content')
<div class="card">
    <div class="card-header">
        <form class="form-inline" method="GET">
            <input type="text" name="search" class="form-control form-control-sm mr-2" placeholder="Tìm tên, email..." value="{{ request('search') }}">
            <select name="role" class="form-control form-control-sm mr-2"><option value="">Tất cả role</option><option value="admin" {{ request('role')=='admin'?'selected':'' }}>Admin</option><option value="user" {{ request('role')=='user'?'selected':'' }}>User</option></select>
            <button class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
        </form>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover">
            <thead><tr><th>Tên</th><th>Email</th><th>Role</th><th>Số dư</th><th>VPS</th><th>Trạng thái</th><th>Đăng ký</th><th></th></tr></thead>
            <tbody>
                @foreach($users as $user)
                <tr>
                    <td><strong>{{ $user->name }}</strong></td>
                    <td>{{ $user->email }}</td>
                    <td><span class="badge badge-{{ $user->role==='admin'?'danger':'primary' }}">{{ $user->role }}</span></td>
                    <td style="color:#10b981;font-weight:600;">{{ number_format($user->balance, 0, ',', '.') }}đ</td>
                    <td>{{ $user->vps_instances_count }}</td>
                    <td><span class="badge badge-{{ $user->status==='active'?'success':'danger' }}">{{ $user->status }}</span></td>
                    <td>{{ $user->created_at->format('d/m/Y') }}</td>
                    <td><a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="px-3">{{ $users->links() }}</div>
    </div>
</div>
@endsection
