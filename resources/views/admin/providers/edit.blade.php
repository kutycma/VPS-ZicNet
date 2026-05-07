@extends('layouts.admin')
@section('title', 'Sửa nhà cung cấp')
@section('page_title', 'Sửa nhà cung cấp: ' . $provider->name)

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <form action="{{ route('admin.providers.update', $provider) }}" method="POST">
                @csrf @method('PUT')
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Tên nhà cung cấp <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $provider->name) }}" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>API Endpoint <span class="text-danger">*</span></label>
                        <input type="url" name="api_endpoint" class="form-control" value="{{ old('api_endpoint', $provider->api_endpoint) }}" required>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group"><label>API Username</label>
                                <input type="text" name="api_username" class="form-control" value="{{ old('api_username', $provider->api_username) }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group"><label>API App</label>
                                <input type="text" name="api_app" class="form-control" value="{{ old('api_app', $provider->api_app) }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group"><label>API Secret</label>
                                <input type="password" name="api_secret" class="form-control" placeholder="Để trống nếu không đổi">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group"><label>Loại markup</label>
                                <select name="markup_type" class="form-control">
                                    <option value="percentage" {{ $provider->markup_type === 'percentage' ? 'selected' : '' }}>Phần trăm (%)</option>
                                    <option value="fixed" {{ $provider->markup_type === 'fixed' ? 'selected' : '' }}>Cố định (VNĐ)</option>
                                    <option value="manual" {{ $provider->markup_type === 'manual' ? 'selected' : '' }}>Thủ công</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group"><label>Giá trị markup</label>
                                <input type="number" name="markup_value" class="form-control" value="{{ old('markup_value', $provider->markup_value) }}" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group"><label>Trạng thái</label>
                                <select name="status" class="form-control">
                                    <option value="active" {{ $provider->status === 'active' ? 'selected' : '' }}>Hoạt động</option>
                                    <option value="inactive" {{ $provider->status === 'inactive' ? 'selected' : '' }}>Tắt</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group"><label>Mô tả</label>
                        <textarea name="description" class="form-control" rows="3">{{ old('description', $provider->description) }}</textarea>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-layer-group mr-1"></i> Nhóm mặc định cho gói VPS đồng bộ</label>
                        <select name="default_group_id" class="form-control">
                            <option value="">— Không chọn (chỉ hiện ở tab "Tất cả") —</option>
                            @foreach(\App\Models\VpsPlanGroup::orderBy('sort_order')->get() as $group)
                                <option value="{{ $group->id }}" {{ old('default_group_id', $provider->default_group_id) == $group->id ? 'selected' : '' }}>
                                    {{ $group->name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Khi đồng bộ sản phẩm, các gói mới sẽ tự động được gán vào nhóm này.</small>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Cập nhật</button>
                    <a href="{{ route('admin.providers.index') }}" class="btn btn-secondary">Quay lại</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
