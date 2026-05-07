@extends('layouts.admin')
@section('title', 'Thêm nhà cung cấp')
@section('page_title', 'Thêm nhà cung cấp mới')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <form action="{{ route('admin.providers.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Tên nhà cung cấp <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="VD: H2Cloud" required>
                                @error('name') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Nhóm VPS mặc định</label>
                                <select name="default_group_id" class="form-control">
                                    <option value="">-- Không chọn (Hiện ở tab Tất cả) --</option>
                                    @foreach($planGroups as $group)
                                        <option value="{{ $group->id }}" {{ old('default_group_id') == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>API Endpoint <span class="text-danger">*</span></label>
                        <input type="url" name="api_endpoint" class="form-control @error('api_endpoint') is-invalid @enderror" value="{{ old('api_endpoint', 'https://cloudserver.h2cloud.vn') }}" required>
                        @error('api_endpoint') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>API Username <span class="text-danger">*</span></label>
                                <input type="text" name="api_username" class="form-control" value="{{ old('api_username') }}" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>API App <span class="text-danger">*</span></label>
                                <input type="text" name="api_app" class="form-control" value="{{ old('api_app') }}" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>API Secret <span class="text-danger">*</span></label>
                                <input type="password" name="api_secret" class="form-control" value="{{ old('api_secret') }}" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Loại markup giá <span class="text-danger">*</span></label>
                                <select name="markup_type" class="form-control">
                                    <option value="percentage">Phần trăm (%)</option>
                                    <option value="fixed">Cố định (VNĐ)</option>
                                    <option value="manual">Thủ công (set tay từng gói)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Giá trị markup</label>
                                <input type="number" name="markup_value" class="form-control" value="{{ old('markup_value', 20) }}" step="0.01" min="0">
                                <small class="text-muted">VD: 20 = tăng 20% hoặc tăng 20.000đ</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Mô tả</label>
                        <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Lưu</button>
                    <a href="{{ route('admin.providers.index') }}" class="btn btn-secondary">Hủy</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
