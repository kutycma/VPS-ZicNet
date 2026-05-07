@extends('layouts.client')
@section('title', __('client.renew') . ' VPS')
@section('page_title', __('client.renew') . ' VPS #' . ($instance->vps_provider_id ?? $instance->id))
@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card" style="border-radius:16px;">
            <div class="card-header"><h3 class="card-title text-primary"><i class="fas fa-calendar-plus mr-2"></i>{{ __('client.select_billing_cycle') }}</h3></div>
            <div class="card-body">
                <div class="alert alert-info">
                    <strong>{{ __('client.your_balance') }}:</strong> <span class="badge badge-primary" style="font-size:1rem;">{{ number_format(Auth::user()->balance ?? 0) }} VNĐ</span>
                </div>

                <div class="mb-4">
                    <div class="table-responsive">
                    <table class="table table-sm table-borderless">
                        <tr><td width="150" class="text-muted">Gói đang dùng:</td><td><strong>{{ $plan->name }}</strong></td></tr>
                        <tr><td class="text-muted">Ngày hết hạn hiện tại:</td><td><span class="text-danger">{{ $instance->expires_at ? $instance->expires_at->format('d/m/Y') : 'Không rõ' }}</span></td></tr>
                    </table>
                    </div>
                </div>

                <form action="{{ route('client.vps.renew.confirm', $instance) }}" method="POST" id="renewForm">
                    @csrf
                    <div class="form-group mb-4">
                        <label for="billing_cycle" class="font-weight-bold">Chu kỳ gia hạn:</label>
                        <select name="billing_cycle" id="billing_cycle" class="form-control form-control-lg" required onchange="updatePrice()">
                            <option value="">-- Chọn chu kỳ --</option>
                            @foreach($billingCycles as $key => $cycle)
                                <option value="{{ $key }}" data-price="{{ $cycle['selling_price'] }}">{{ $cycle['label'] }} - {{ number_format($cycle['selling_price']) }}đ</option>
                            @endforeach
                        </select>
                        @error('billing_cycle') <span class="text-danger mt-1 d-block">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group p-3 bg-light rounded text-center">
                        <h4 class="mb-0">Tổng thanh toán: <strong id="totalPrice" class="text-success">0 VNĐ</strong></h4>
                    </div>

                    <div class="text-center mt-4 pt-3 border-top">
                        <a href="{{ route('client.vps.show', $instance) }}" class="btn btn-secondary btn-lg mr-2" style="border-radius:10px;"><i class="fas fa-arrow-left mr-2"></i>{{ __('client.cancel') }}</a>
                        <button type="submit" class="btn btn-primary btn-lg" style="border-radius:10px;" id="btnSubmit" onclick="return confirm('Xác nhận thanh toán gia hạn VPS?')"><i class="fas fa-check mr-2"></i>{{ __('client.confirm_order') }}</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const userBalance = {{ Auth::user()->balance ?? 0 }};
    function updatePrice() {
        const select = document.getElementById('billing_cycle');
        const selectedOption = select.options[select.selectedIndex];
        if(!selectedOption.value) {
            document.getElementById('totalPrice').innerText = '0 VNĐ';
            document.getElementById('btnSubmit').disabled = true;
            return;
        }
        
        const price = parseInt(selectedOption.getAttribute('data-price')) || 0;
        document.getElementById('totalPrice').innerText = price.toLocaleString('vi-VN') + ' VNĐ';
        
        if (price > userBalance) {
            document.getElementById('totalPrice').innerText += " (Không đủ số dư)";
            document.getElementById('totalPrice').className = "text-danger";
            document.getElementById('btnSubmit').disabled = true;
        } else {
            document.getElementById('totalPrice').className = "text-success";
            document.getElementById('btnSubmit').disabled = false;
        }
    }
    // Init state
    document.getElementById('btnSubmit').disabled = true;
</script>
@endpush
@endsection
