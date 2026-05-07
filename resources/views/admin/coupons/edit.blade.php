@extends('layouts.admin')
@section('title', 'Sửa mã giảm giá')
@section('page_title', 'Sửa mã: ' . $coupon->code)
@section('content')
<form action="{{ route('admin.coupons.update', $coupon) }}" method="POST">@csrf @method('PUT')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-edit mr-1"></i> Mã: <code style="font-size:1.1rem;color:#6366f1;">{{ $coupon->code }}</code></h3>
                <div class="card-tools">
                    <span class="badge badge-info">Đã dùng: {{ $coupon->used_count }} lượt</span>
                </div>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label>Mô tả</label>
                    <input type="text" name="description" class="form-control" value="{{ old('description', $coupon->description) }}">
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Loại giảm giá</label>
                            <select name="type" class="form-control" id="discountType" onchange="toggleMaxDiscount()">
                                <option value="fixed" {{ $coupon->type==='fixed'?'selected':'' }}>Giảm tiền cố định</option>
                                <option value="percent" {{ $coupon->type==='percent'?'selected':'' }}>Giảm %</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Giá trị</label>
                            <input type="number" name="value" class="form-control" value="{{ old('value', $coupon->value) }}" step="0.01" min="0.01" required>
                        </div>
                    </div>
                    <div class="col-md-4" id="maxDiscountGroup" style="{{ $coupon->type==='percent'?'':'display:none;' }}">
                        <div class="form-group">
                            <label>Giảm tối đa (VNĐ)</label>
                            <input type="number" name="max_discount" class="form-control" value="{{ old('max_discount', $coupon->max_discount) }}" step="1" min="0">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Đơn hàng tối thiểu (VNĐ)</label>
                    <input type="number" name="min_order_amount" class="form-control" value="{{ old('min_order_amount', $coupon->min_order_amount) }}" step="1" min="0">
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Tổng lượt SD tối đa</label>
                            <input type="number" name="max_uses" class="form-control" value="{{ old('max_uses', $coupon->max_uses) }}" min="1">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Lượt SD tối đa / user</label>
                            <input type="number" name="max_uses_per_user" class="form-control" value="{{ old('max_uses_per_user', $coupon->max_uses_per_user) }}" min="1">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Bắt đầu từ</label>
                            <input type="datetime-local" name="starts_at" class="form-control" value="{{ old('starts_at', $coupon->starts_at ? $coupon->starts_at->format('Y-m-d\TH:i') : '') }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Hết hạn</label>
                            <input type="datetime-local" name="expires_at" class="form-control" value="{{ old('expires_at', $coupon->expires_at ? $coupon->expires_at->format('Y-m-d\TH:i') : '') }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-filter mr-1"></i> Giới hạn áp dụng</h3></div>
            <div class="card-body">
                <div class="form-group">
                    <label>Áp dụng cho gói VPS</label>
                    <select name="plan_id" class="form-control">
                        <option value="">— Tất cả gói —</option>
                        @foreach($plans as $plan)
                            <option value="{{ $plan->id }}" {{ $coupon->plan_id==$plan->id?'selected':'' }}>{{ $plan->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label>Áp dụng cho nhóm VPS</label>
                    <select name="group_id" class="form-control">
                        <option value="">— Tất cả nhóm —</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}" {{ $coupon->group_id==$group->id?'selected':'' }}>{{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-user-shield mr-1"></i> Mã độc quyền cho user</label>
                    <select name="user_id" class="form-control">
                        <option value="">— Tất cả user —</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ $coupon->user_id==$user->id?'selected':'' }}>{{ $user->name }} ({{ $user->email }})</option>
                        @endforeach
                    </select>
                </div>

                <hr>

                <div class="form-group">
                    <label>Trạng thái</label>
                    <select name="status" class="form-control">
                        <option value="active" {{ $coupon->status==='active'?'selected':'' }}>Active</option>
                        <option value="inactive" {{ $coupon->status==='inactive'?'selected':'' }}>Inactive</option>
                    </select>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-save mr-1"></i> Cập nhật</button>
                <a href="{{ route('admin.coupons.index') }}" class="btn btn-secondary btn-block">Quay lại</a>
            </div>
        </div>
    </div>
</div>
</form>

@push('scripts')
<script>
function toggleMaxDiscount() {
    $('#maxDiscountGroup').toggle($('#discountType').val() === 'percent');
}
</script>
@endpush
@endsection
