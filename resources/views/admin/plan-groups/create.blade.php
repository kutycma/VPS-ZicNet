@extends('layouts.admin')
@section('title', 'Thêm nhóm VPS')
@section('page_title', 'Thêm nhóm VPS mới')
@section('content')
<div class="row"><div class="col-md-6">
<div class="card">
    <form action="{{ route('admin.plan-groups.store') }}" method="POST">@csrf
    <div class="card-body">
        <div class="form-group">
            <label>Tên nhóm <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required placeholder="VD: VPS Giá Rẻ, VPS Premium...">
        </div>
        <div class="form-group">
            <label>Icon (FontAwesome) <small class="text-muted">VD: fas fa-bolt</small></label>
            <input type="text" name="icon" class="form-control" value="{{ old('icon', 'fas fa-server') }}">
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Thứ tự</label>
                    <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', 0) }}">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Trạng thái</label>
                    <select name="status" class="form-control">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Tạo nhóm</button>
        <a href="{{ route('admin.plan-groups.index') }}" class="btn btn-secondary">Quay lại</a>
    </div>
    </form>
</div>
</div></div>
@endsection
