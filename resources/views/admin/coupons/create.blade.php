@extends('layouts.admin')
@section('title', 'Tạo mã giảm giá')
@section('page_title', 'Tạo mã giảm giá')
@section('content')
<form action="{{ route('admin.coupons.store') }}" method="POST">@csrf
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-ticket-alt mr-1"></i> Thông tin mã</h3></div>
            <div class="card-body">
                {{-- Mode Selection --}}
                <div class="form-group">
                    <label>Chế độ tạo mã</label>
                    <div class="btn-group btn-group-toggle d-flex" data-toggle="buttons">
                        <label class="btn btn-outline-primary active" style="border-radius:8px 0 0 8px;">
                            <input type="radio" name="mode" value="single" checked onchange="toggleMode()"> <i class="fas fa-tag mr-1"></i> Tạo 1 mã
                        </label>
                        <label class="btn btn-outline-primary" style="border-radius:0 8px 8px 0;">
                            <input type="radio" name="mode" value="bulk" onchange="toggleMode()"> <i class="fas fa-tags mr-1"></i> Tạo hàng loạt
                        </label>
                    </div>
                </div>

                {{-- Single Code --}}
                <div id="singleMode">
                    <div class="form-group">
                        <label>Mã giảm giá <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control" value="{{ old('code') }}" placeholder="VD: SALE2026, NEWUSER50" style="text-transform:uppercase; font-weight:700; letter-spacing:1px;">
                    </div>
                </div>

                {{-- Bulk Codes --}}
                <div id="bulkMode" style="display:none;">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Tiền tố (Prefix) <span class="text-danger">*</span></label>
                                <input type="text" name="bulk_prefix" class="form-control" value="{{ old('bulk_prefix') }}" placeholder="VD: VPS" style="text-transform:uppercase;">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Số lượng mã <span class="text-danger">*</span></label>
                                <input type="number" name="bulk_count" class="form-control" value="{{ old('bulk_count', 10) }}" min="1" max="500">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Độ dài random</label>
                                <input type="number" name="bulk_length" class="form-control" value="{{ old('bulk_length', 8) }}" min="4" max="20">
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info py-2"><small><i class="fas fa-info-circle mr-1"></i> Mã sẽ có dạng: <code>VPS</code> + 8 ký tự ngẫu nhiên → <code>VPSA3F8K2MN</code></small></div>
                </div>

                <div class="form-group">
                    <label>Mô tả (tuỳ chọn)</label>
                    <input type="text" name="description" class="form-control" value="{{ old('description') }}" placeholder="VD: Giảm giá khai trương, Mã dành cho KH VIP...">
                </div>

                <hr>

                {{-- Discount Config --}}
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Loại giảm giá <span class="text-danger">*</span></label>
                            <select name="type" class="form-control" id="discountType" onchange="toggleMaxDiscount()">
                                <option value="fixed" {{ old('type')==='fixed'?'selected':'' }}>Giảm số tiền cố định (VNĐ)</option>
                                <option value="percent" {{ old('type')==='percent'?'selected':'' }}>Giảm phần trăm (%)</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Giá trị <span class="text-danger">*</span></label>
                            <input type="number" name="value" class="form-control" value="{{ old('value') }}" step="0.01" min="0.01" required placeholder="50000 hoặc 10">
                        </div>
                    </div>
                    <div class="col-md-4" id="maxDiscountGroup" style="display:none;">
                        <div class="form-group">
                            <label>Giảm tối đa (VNĐ)</label>
                            <input type="number" name="max_discount" class="form-control" value="{{ old('max_discount') }}" step="1" min="0" placeholder="VD: 100000">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Đơn hàng tối thiểu (VNĐ)</label>
                            <input type="number" name="min_order_amount" class="form-control" value="{{ old('min_order_amount', 0) }}" step="1" min="0">
                        </div>
                    </div>
                </div>

                <hr>

                {{-- Usage Limits --}}
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Tổng lượt sử dụng tối đa <small class="text-muted">(trống = không giới hạn)</small></label>
                            <input type="number" name="max_uses" class="form-control" value="{{ old('max_uses', 1) }}" min="1" placeholder="1">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Lượt SD tối đa / user <small class="text-muted">(trống = không giới hạn)</small></label>
                            <input type="number" name="max_uses_per_user" class="form-control" value="{{ old('max_uses_per_user', 1) }}" min="1" placeholder="1">
                        </div>
                    </div>
                </div>

                <hr>

                {{-- Date Range --}}
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Bắt đầu từ <small class="text-muted">(trống = ngay lập tức)</small></label>
                            <input type="datetime-local" name="starts_at" class="form-control" value="{{ old('starts_at') }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Hết hạn <small class="text-muted">(trống = vĩnh viễn)</small></label>
                            <input type="datetime-local" name="expires_at" class="form-control" value="{{ old('expires_at') }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Sidebar: Restrictions --}}
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-filter mr-1"></i> Giới hạn áp dụng</h3></div>
            <div class="card-body">
                <div class="form-group">
                    <label>Áp dụng cho gói VPS</label>
                    <select name="plan_id" class="form-control">
                        <option value="">— Tất cả gói —</option>
                        @foreach($plans as $plan)
                            <option value="{{ $plan->id }}" {{ old('plan_id')==$plan->id?'selected':'' }}>{{ $plan->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label>Áp dụng cho nhóm VPS</label>
                    <select name="group_id" class="form-control">
                        <option value="">— Tất cả nhóm —</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}" {{ old('group_id')==$group->id?'selected':'' }}>{{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-user-shield mr-1"></i> Mã độc quyền cho user</label>
                    <select name="user_id" class="form-control">
                        <option value="">— Tất cả user —</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('user_id')==$user->id?'selected':'' }}>{{ $user->name }} ({{ $user->email }})</option>
                        @endforeach
                    </select>
                    <small class="text-muted">Chỉ user được chọn mới dùng được mã này.</small>
                </div>

                <hr>

                <div class="form-group">
                    <label>Trạng thái</label>
                    <select name="status" class="form-control">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-save mr-1"></i> Tạo mã giảm giá</button>
                <a href="{{ route('admin.coupons.index') }}" class="btn btn-secondary btn-block">Quay lại</a>
            </div>
        </div>
    </div>
</div>
</form>

@push('scripts')
<script>
function toggleMode() {
    var mode = $('input[name=mode]:checked').val();
    if (mode === 'bulk') {
        $('#singleMode').hide();
        $('#bulkMode').show();
    } else {
        $('#singleMode').show();
        $('#bulkMode').hide();
    }
}

function toggleMaxDiscount() {
    if ($('#discountType').val() === 'percent') {
        $('#maxDiscountGroup').show();
    } else {
        $('#maxDiscountGroup').hide();
    }
}

// Init
toggleMaxDiscount();
</script>
@endpush
@endsection
