@extends('layouts.admin')
@section('title', 'Sửa người dùng')
@section('page_title', 'Sửa: ' . $user->name)
@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="card"><div class="card-header"><h3 class="card-title">Thông tin</h3></div>
        <form action="{{ route('admin.users.update', $user) }}" method="POST"><div class="card-body">@csrf @method('PUT')
            <div class="form-group"><label>Tên</label><input type="text" name="name" class="form-control" value="{{ $user->name }}" required></div>
            <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" value="{{ $user->email }}" required></div>
            <div class="form-group"><label>Phone</label><input type="text" name="phone" class="form-control" value="{{ $user->phone }}"></div>
            <div class="row">
                <div class="col-md-6"><div class="form-group"><label>Role</label><select name="role" class="form-control"><option value="user" {{ $user->role=='user'?'selected':'' }}>User</option><option value="admin" {{ $user->role=='admin'?'selected':'' }}>Admin</option></select></div></div>
                <div class="col-md-6"><div class="form-group"><label>Trạng thái</label><select name="status" class="form-control"><option value="active" {{ $user->status=='active'?'selected':'' }}>Active</option><option value="suspended" {{ $user->status=='suspended'?'selected':'' }}>Suspended</option><option value="banned" {{ $user->status=='banned'?'selected':'' }}>Banned</option></select></div></div>
            </div>
            <div class="form-group"><label>Mật khẩu mới <small class="text-muted">(để trống = không đổi)</small></label><input type="password" name="password" class="form-control"></div>
        </div><div class="card-footer"><button type="submit" class="btn btn-primary">Cập nhật</button></div></form>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card"><div class="card-header"><h3 class="card-title">Điều chỉnh số dư</h3><span class="float-right badge badge-info" style="font-size:1rem;">{{ number_format($user->balance, 0, ',', '.') }}đ</span></div>
        <form action="{{ route('admin.users.adjust-balance', $user) }}" method="POST"><div class="card-body">@csrf
            <div class="form-group"><label>Số tiền (+ cộng, - trừ)</label><input type="number" name="amount" class="form-control" step="1" required placeholder="VD: 100000 hoặc -50000"></div>
            <div class="form-group"><label>Lý do</label><input type="text" name="description" class="form-control" required placeholder="VD: Nạp tiền qua chuyển khoản"></div>
        </div><div class="card-footer"><button type="submit" class="btn btn-success">Điều chỉnh</button></div></form>
        </div>
    </div>
</div>
@endsection
