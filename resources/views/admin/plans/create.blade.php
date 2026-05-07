@extends('layouts.admin')
@section('title', 'Thêm gói VPS')
@section('page_title', 'Thêm gói VPS mới')
@section('content')
<div class="row"><div class="col-md-8">
<div class="card">
    <form action="{{ route('admin.plans.store') }}" method="POST">@csrf
    <div class="card-body">
        <div class="row">
            <div class="col-md-6"><div class="form-group"><label>Nhà cung cấp</label>
                <select name="provider_id" id="provider_id" class="form-control" required>
                    <option value="">-- Chọn --</option>
                    @foreach($providers as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
                </select>
            </div></div>
            <div class="col-md-6"><div class="form-group"><label>Loại</label>
                <select name="type" class="form-control"><option value="vps_vn">VPS VN</option><option value="vps_nn">VPS NN</option></select>
            </div></div>
        </div>
        <div class="form-group"><label>Tên gói</label><input type="text" name="name" class="form-control" required></div>
        <div class="row">
            <div class="col-md-3"><div class="form-group"><label>CPU (cores)</label><input type="number" name="cpu_cores" class="form-control" min="1" value="1" required></div></div>
            <div class="col-md-3"><div class="form-group"><label>RAM (MB)</label><input type="number" name="ram_mb" class="form-control" min="512" value="1024" required></div></div>
            <div class="col-md-3"><div class="form-group"><label>Disk (GB)</label><input type="number" name="disk_gb" class="form-control" min="10" value="20" required></div></div>
            <div class="col-md-3"><div class="form-group"><label>Bandwidth (Mbps)</label><input type="number" name="bandwidth_mbps" class="form-control" value="100"></div></div>
        </div>
        <div class="row">
            <div class="col-md-4"><div class="form-group"><label>Provider Plan ID</label><input type="text" name="provider_plan_id" class="form-control" placeholder="ID trên API NCC"></div></div>
            <div class="col-md-4"><div class="form-group"><label>Giá vốn (VNĐ)</label><input type="number" name="provider_price" id="provider_price" class="form-control" step="1" min="0" required></div></div>
            <div class="col-md-4"><div class="form-group"><label>Giá bán (VNĐ) <small class="text-muted">0 = tự tính</small></label><input type="number" name="selling_price" id="selling_price" class="form-control" step="1" min="0" value="0"></div></div>
        </div>
        <div class="form-group"><label>Mô tả</label><textarea name="description" class="form-control" rows="2"></textarea></div>
    </div>
    <div class="card-footer"><button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Lưu</button> <a href="{{ route('admin.plans.index') }}" class="btn btn-secondary">Hủy</a></div>
    </form>
</div>
</div></div>
@endsection
@push('scripts')
<script>
$('#provider_price').on('input', function(){ calcPrice(); });
$('#provider_id').on('change', function(){ calcPrice(); });
function calcPrice(){
    var price = $('#provider_price').val();
    var provider = $('#provider_id').val();
    if(price && provider) {
        $.post('{{ route("admin.plans.calculate-price") }}', {provider_id: provider, provider_price: price}, function(r){ $('#selling_price').val(r.selling_price); });
    }
}
</script>
@endpush
