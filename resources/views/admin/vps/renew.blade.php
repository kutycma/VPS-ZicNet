@extends('layouts.admin')
@section('title', 'Gia Hạn VPS (Admin Cấp)')
@section('page_title', 'Gia Hạn VPS #' . ($instance->vps_provider_id ?? $instance->id))
@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card" style="border-radius:16px;">
            <div class="card-header"><h3 class="card-title text-primary"><i class="fas fa-calendar-plus mr-2"></i>Chọn Chu Kỳ Gia Hạn</h3></div>
            <div class="card-body">
                <div class="alert alert-info">
                    <strong>Tài khoản người dùng:</strong> {{ $instance->user->name }} ({{ collect(explode(' ', $instance->user->email))->first() }})<br>
                    <strong>Số dư hiện có:</strong> <span class="badge badge-primary" style="font-size:1rem;">{{ number_format($instance->user->balance ?? 0) }} VNĐ</span>
                </div>

                <div class="mb-4">
                    <div class="table-responsive">
                    <table class="table table-sm table-borderless">
                        <tr><td width="150" class="text-muted">Gói đang dùng:</td><td><strong>{{ $instance->plan->name }}</strong></td></tr>
                        <tr><td class="text-muted">Ngày hết hạn hiện tại:</td><td><span class="text-danger">{{ $instance->expires_at ? $instance->expires_at->format('d/m/Y') : 'Không rõ' }}</span></td></tr>
                    </table>
                    </div>
                </div>

                <form action="{{ route('admin.vps.renew', $instance) }}" method="POST" id="renewForm">
                    @csrf
                    <div class="form-group mb-4">
                        <label for="billing_cycle" class="font-weight-bold">Chu kỳ gia hạn:</label>
                        <select name="billing_cycle" id="billing_cycle" class="form-control form-control-lg" required onchange="updatePrice()">
                            <option value="">-- Chọn chu kỳ --</option>
                            @foreach($billingCycles as $key => $cycle)
                                <option value="{{ $key }}" data-price="{{ $cycle['selling_price'] }}">{{ $cycle['label'] }} - {{ number_format($cycle['selling_price']) }}đ (Giá gốc)</option>
                            @endforeach
                        </select>
                        @error('billing_cycle') <span class="text-danger mt-1 d-block">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group mb-4">
                        <label for="custom_amount" class="font-weight-bold">Số tiền sẽ thu của Khách (VNĐ):</label>
                        <input type="number" name="custom_amount" id="custom_amount" class="form-control form-control-lg" min="0" required value="0">
                        <small class="form-text text-muted">Hệ thống sẽ trừ số tiền này vào tài khoản của khách. Điền 0 nếu muốn gia hạn miễn phí.</small>
                        @error('custom_amount') <span class="text-danger mt-1 d-block">{{ $message }}</span> @enderror
                    </div>

                    <div class="text-center mt-4 pt-3 border-top">
                        <a href="{{ route('admin.vps.show', $instance) }}" class="btn btn-secondary btn-lg mr-2" style="border-radius:10px;"><i class="fas fa-arrow-left mr-2"></i>Hủy</a>
                        <button type="submit" class="btn btn-primary btn-lg" style="border-radius:10px;" id="btnSubmit" onclick="return confirm('Xác nhận gia hạn VPS cho khách hàng này?')"><i class="fas fa-check mr-2"></i>Thực Hiện Gia Hạn</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const userBalance = {{ $instance->user->balance ?? 0 }};
    function updatePrice() {
        const select = document.getElementById('billing_cycle');
        const selectedOption = select.options[select.selectedIndex];
        
        if(!selectedOption.value) {
            document.getElementById('custom_amount').value = 0;
            return;
        }
        
        const price = parseInt(selectedOption.getAttribute('data-price')) || 0;
        document.getElementById('custom_amount').value = price;
    }
</script>
@endpush
@endsection
