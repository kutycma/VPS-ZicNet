@extends('layouts.admin')
@section('title', 'Sửa gói VPS')
@section('page_title', 'Sửa gói: ' . $plan->name)
@section('content')
<form action="{{ route('admin.plans.update', $plan) }}" method="POST">@csrf @method('PUT')
<div class="row">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-edit mr-2"></i>Thông tin gói</h3></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3"><div class="form-group"><label>Loại</label><select name="type" class="form-control"><option value="vps_vn" {{ $plan->type=='vps_vn'?'selected':'' }}>VPS VN</option><option value="vps_nn" {{ $plan->type=='vps_nn'?'selected':'' }}>VPS NN</option></select></div></div>
                    <div class="col-md-3"><div class="form-group"><label>Trạng thái</label><select name="status" class="form-control"><option value="active" {{ $plan->status=='active'?'selected':'' }}>Active</option><option value="inactive" {{ $plan->status=='inactive'?'selected':'' }}>Inactive</option></select></div></div>
                    <div class="col-md-3"><div class="form-group"><label>Nhóm (Tab)</label>
                        <select name="group_id" class="form-control">
                            <option value="">— Không chọn —</option>
                            @foreach(\App\Models\VpsPlanGroup::orderBy('sort_order')->get() as $group)
                                <option value="{{ $group->id }}" {{ $plan->group_id == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div></div>
                    <div class="col-md-3"><div class="form-group"><label>Thứ tự</label><input type="number" name="sort_order" class="form-control" value="{{ $plan->sort_order }}"></div></div>
                </div>
                <div class="form-group"><label>Tên gói</label><input type="text" name="name" class="form-control" value="{{ $plan->name }}" required></div>
                <div class="row">
                    <div class="col-md-3"><div class="form-group"><label>CPU</label><input type="number" name="cpu_cores" class="form-control" value="{{ $plan->cpu_cores }}" required></div></div>
                    <div class="col-md-3"><div class="form-group"><label>RAM (MB)</label><input type="number" name="ram_mb" class="form-control" value="{{ $plan->ram_mb }}" required></div></div>
                    <div class="col-md-3"><div class="form-group"><label>Disk (GB)</label><input type="number" name="disk_gb" class="form-control" value="{{ $plan->disk_gb }}" required></div></div>
                    <div class="col-md-3"><div class="form-group"><label>Bandwidth</label><input type="number" name="bandwidth_mbps" class="form-control" value="{{ $plan->bandwidth_mbps }}"></div></div>
                </div>
                <div class="row">
                    <div class="col-md-6"><div class="form-group"><label>Provider Plan ID</label><input type="text" name="provider_plan_id" class="form-control" value="{{ $plan->provider_plan_id }}"></div></div>
                    <div class="col-md-6"><div class="form-group"><label>Giá gốc tháng (hiển thị)</label><input type="number" name="provider_price" class="form-control" value="{{ $plan->provider_price }}" required></div></div>
                </div>
                <div class="form-group">
                    <label>Nhóm (Tabs)</label>
                    <div class="d-flex flex-wrap" style="gap:15px;">
                        @php $planGroups = $plan->groups->pluck('id')->toArray(); @endphp
                        @foreach($groups as $group)
                        <div class="custom-control custom-checkbox">
                            <input class="custom-control-input" type="checkbox" name="group_ids[]" id="group_{{ $group->id }}" value="{{ $group->id }}" {{ in_array($group->id, old('group_ids', $planGroups)) ? 'checked' : '' }}>
                            <label for="group_{{ $group->id }}" class="custom-control-label">{{ $group->name }}</label>
                        </div>
                        @endforeach
                        @if($groups->isEmpty())
                            <span class="text-muted"><i class="fas fa-info-circle"></i> Chưa có nhóm nào. Vui lòng tạo nhóm trước.</span>
                        @endif
                    </div>
                    <small class="text-muted d-block mt-1">Một gói có thể hiển thị ở nhiều nhóm (tab) khác nhau ngoài trang chủ.</small>
                </div>
                <div class="form-group"><label>Mô tả</label><textarea name="description" class="form-control" rows="2">{{ $plan->description }}</textarea></div>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-tags mr-2 text-success"></i>Bảng giá theo chu kỳ</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead style="background:#f8fafc;">
                        <tr>
                            <th style="width:30%;">Chu kỳ</th>
                            <th style="width:35%;">Giá gốc (NCC)</th>
                            <th style="width:35%;">Giá bán</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $pricingData = $plan->sorted_pricing; @endphp
                        @foreach(\App\Models\VpsPlan::BILLING_CYCLES as $key => $meta)
                        @php
                            $cycle = $pricingData[$key] ?? null;
                            $provPrice = $cycle['provider_price'] ?? '';
                            $sellPrice = $cycle['selling_price'] ?? '';
                        @endphp
                        <tr>
                            <td class="align-middle font-weight-bold" style="white-space:nowrap;">
                                {{ $meta['label'] }}
                            </td>
                            <td>
                                <input type="number" 
                                    name="pricing[{{ $key }}][provider_price]" 
                                    class="form-control form-control-sm" 
                                    value="{{ $provPrice }}" 
                                    placeholder="—" 
                                    min="0" step="1000">
                            </td>
                            <td>
                                <input type="number" 
                                    name="pricing[{{ $key }}][selling_price]" 
                                    class="form-control form-control-sm {{ $sellPrice ? '' : 'border-warning' }}" 
                                    value="{{ $sellPrice }}" 
                                    placeholder="Tự tính" 
                                    min="0" step="1000"
                                    style="{{ $sellPrice ? 'font-weight:600;color:#10b981;' : '' }}">
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="px-3 py-2">
                    <small class="text-muted">
                        <i class="fas fa-info-circle mr-1"></i>
                        Để trống giá bán = tự tính theo markup NCC. Nhập giá để tuỳ chỉnh riêng cho từng chu kỳ.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save mr-2"></i>Cập nhật gói VPS</button>
        <a href="{{ route('admin.plans.index') }}" class="btn btn-secondary btn-lg ml-2">Quay lại</a>
    </div>
</div>
</form>
@endsection
