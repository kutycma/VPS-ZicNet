@extends('layouts.admin')
@section('title', 'Sửa nhóm VPS')
@section('page_title', 'Sửa nhóm: ' . $planGroup->name)
@section('content')
<div class="row"><div class="col-md-6">
<div class="card">
    <form action="{{ route('admin.plan-groups.update', $planGroup) }}" method="POST">@csrf @method('PUT')
    <div class="card-body">
        <div class="form-group">
            <label>Tên nhóm <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $planGroup->name) }}" required>
        </div>
        <div class="form-group">
            <label>Icon (FontAwesome)</label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="{{ $planGroup->icon }}"></i></span>
                </div>
                <input type="text" name="icon" class="form-control" value="{{ old('icon', $planGroup->icon) }}">
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Thứ tự</label>
                    <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $planGroup->sort_order) }}">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Trạng thái</label>
                    <select name="status" class="form-control">
                        <option value="active" {{ $planGroup->status === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ $planGroup->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Cập nhật</button>
        <a href="{{ route('admin.plan-groups.index') }}" class="btn btn-secondary">Quay lại</a>
    </div>
    </form>
</div>
</div></div>
@endsection
