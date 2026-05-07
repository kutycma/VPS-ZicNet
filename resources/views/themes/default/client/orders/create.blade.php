@extends('layouts.client')
@section('title', __('client.order_vps'))
@section('page_title', __('client.order_vps') . ': ' . $plan->name)
@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card" style="border-radius:16px;">
            <form action="{{ route('client.orders.store') }}" method="POST" id="orderForm">@csrf
            <input type="hidden" name="plan_id" value="{{ $plan->id }}">
            <div class="card-body">

                {{-- Validation Errors --}}
                @if($errors->any())
                    <div class="alert alert-danger" style="border-radius:10px;">
                        <i class="fas fa-exclamation-triangle mr-1"></i> <strong>{{ __('client.error') }}:</strong>
                        <ul class="mb-0 mt-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Billing Cycle --}}
                <div class="form-group">
                    <label><i class="fas fa-clock mr-1"></i> {{ __('client.select_billing_cycle') }}</label>
                    <select name="billing_cycle" class="form-control" id="billingCycle" required>
                        @foreach($billingCycles as $key => $cycle)
                            <option value="{{ $key }}"
                                    data-price="{{ $cycle['selling_price'] ?? 0 }}">
                                {{ $cycle['label'] ?? $key }} — {{ number_format($cycle['selling_price'] ?? 0, 0, ',', '.') }}đ
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- OS --}}
                <div class="form-group">
                    <label><i class="fas fa-desktop mr-1"></i> {{ __('client.select_os') }}</label>
                    <select name="os" class="form-control" id="osSelect">
                        @foreach($operatingSystems as $os)
                            @if(is_array($os) && isset($os['id']))
                                <option value="{{ $os['id'] }}">{{ $os['name'] ?? 'OS #'.$os['id'] }}</option>
                            @endif
                        @endforeach
                        @if(empty($operatingSystems))
                            <option value="">—</option>
                        @endif
                    </select>
                </div>

                {{-- State (VPS NN only) --}}
                @if($plan->type === 'vps_nn' && !empty($states))
                <div class="form-group">
                    <label><i class="fas fa-map-marker-alt mr-1"></i> Region</label>
                    <select name="state" class="form-control">
                        @foreach($states as $key => $state)
                            @if(is_array($state))
                                <option value="{{ $state['key'] ?? $state['id'] ?? $key }}">{{ $state['name'] ?? $key }}</option>
                            @else
                                <option value="{{ $key }}">{{ $state }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                @endif

                {{-- Addons --}}
                @if(!empty($addons))
                <div class="form-group">
                    <label><i class="fas fa-puzzle-piece mr-1"></i> Addons</label>
                    <div class="p-3" style="background:#f8fafc;border-radius:10px;">
                        @foreach($addons as $addon)
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>{{ $addon['name'] }}</span>
                            <div class="d-flex align-items-center">
                                <small class="text-muted mr-2">+{{ number_format($addon['price'], 0, ',', '.') }}đ{{ __('client.per_month') }}</small>
                                <input type="hidden" name="addon_type[{{ $addon['product_id'] }}]" value="{{ $addon['name'] }}">
                                <input type="number" name="addon[{{ $addon['product_id'] }}]" class="form-control form-control-sm addon-input"
                                       value="0" min="0" max="99" style="width:70px;" data-price="{{ $addon['price'] }}">
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Quantity --}}
                <div class="form-group">
                    <label><i class="fas fa-layer-group mr-1"></i> VPS x</label>
                    <input type="number" name="quantity" class="form-control" value="1" min="1" max="50" id="qty">
                </div>

                {{-- Coupon Code --}}
                <div class="form-group">
                    <label><i class="fas fa-ticket-alt mr-1"></i> {{ __('client.coupon_code') ?? 'Mã giảm giá' }}</label>
                    <div class="input-group">
                        <input type="text" name="coupon_code" class="form-control" id="couponInput" placeholder="Nhập mã giảm giá (nếu có)" style="text-transform:uppercase;">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-outline-primary" id="applyCouponBtn" onclick="applyCoupon()">
                                <i class="fas fa-check mr-1"></i> Áp dụng
                            </button>
                        </div>
                    </div>
                    <div id="couponMessage" class="mt-1" style="display:none;"></div>
                    <input type="hidden" name="coupon_discount" id="couponDiscountValue" value="0">
                </div>
            </div>

            <div class="card-footer d-flex justify-content-between align-items-center" style="border-radius:0 0 16px 16px;">
                <div>
                    <span class="text-muted">{{ __('client.total') }}: </span>
                    <span id="originalPrice" style="text-decoration:line-through;color:#94a3b8;display:none;"></span>
                    <strong id="totalPrice" style="font-size:1.5rem;color:#6366f1;">0đ</strong>
                    <span id="discountBadge" class="badge badge-success ml-2" style="display:none;"></span>
                </div>
                <div>
                    <a href="{{ route('client.orders.plans') }}" class="btn btn-secondary mr-2">{{ __('client.back') }}</a>
                    <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-check mr-1"></i> {{ __('client.confirm_order') }}</button>
                </div>
            </div>
            </form>
        </div>
    </div>

    {{-- Sidebar: Plan Info --}}
    <div class="col-md-4">
        <div class="card" style="border-radius:16px;">
            <div class="card-body text-center">
                <div style="background:linear-gradient(135deg,#6366f1,#8b5cf6);width:70px;height:70px;border-radius:18px;display:flex;align-items:center;justify-content:center;margin:0 auto 15px;">
                    <i class="fas fa-server fa-2x text-white"></i>
                </div>
                <h5 style="font-weight:700;">{{ $plan->name }}</h5>
                <hr>
                <div class="text-left">
                    <p><i class="fas fa-microchip mr-2 text-muted"></i><strong>{{ $plan->cpu_cores }}</strong> vCPU</p>
                    <p><i class="fas fa-memory mr-2 text-muted"></i><strong>{{ $plan->ram_gb }}</strong> GB RAM</p>
                    <p><i class="fas fa-hdd mr-2 text-muted"></i><strong>{{ $plan->disk_gb }}</strong> GB SSD</p>
                    <p><i class="fas fa-network-wired mr-2 text-muted"></i>{{ $plan->bandwidth_mbps ?: 'Unlimited' }}</p>
                </div>
                <hr>
                <p class="mb-1 text-muted">{{ __('client.your_balance') }}</p>
                <h4 style="color:#10b981;font-weight:700;">{{ number_format($user->balance, 0, ',', '.') }}đ</h4>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
var currentDiscount = 0;
var couponValid = false;

function calcTotal() {
    var cyclePrice = parseFloat($('#billingCycle option:selected').data('price')) || 0;
    var qty = parseInt($('#qty').val()) || 1;

    // Addons
    var addonTotal = 0;
    $('.addon-input').each(function() {
        var addonQty = parseInt($(this).val()) || 0;
        var addonPrice = parseFloat($(this).data('price')) || 0;
        addonTotal += addonQty * addonPrice;
    });

    var subtotal = (cyclePrice + addonTotal) * qty;
    var finalTotal = subtotal - currentDiscount;
    if (finalTotal < 0) finalTotal = 0;

    if (couponValid && currentDiscount > 0) {
        $('#originalPrice').text(subtotal.toLocaleString('de-DE') + 'đ').show();
        $('#discountBadge').text('-' + currentDiscount.toLocaleString('de-DE') + 'đ').show();
    } else {
        $('#originalPrice').hide();
        $('#discountBadge').hide();
    }

    $('#totalPrice').text(finalTotal.toLocaleString('de-DE') + 'đ');
}

function applyCoupon() {
    var code = $('#couponInput').val().trim().toUpperCase();
    if (!code) {
        showCouponMsg('warning', 'Vui lòng nhập mã giảm giá');
        return;
    }

    var cyclePrice = parseFloat($('#billingCycle option:selected').data('price')) || 0;
    var qty = parseInt($('#qty').val()) || 1;
    var addonTotal = 0;
    $('.addon-input').each(function() {
        addonTotal += (parseInt($(this).val()) || 0) * (parseFloat($(this).data('price')) || 0);
    });
    var subtotal = (cyclePrice + addonTotal) * qty;

    $('#applyCouponBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Kiểm tra...');

    $.ajax({
        url: '{{ route("client.orders.apply-coupon") }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            code: code,
            plan_id: {{ $plan->id }},
            amount: subtotal
        },
        success: function(res) {
            if (res.valid) {
                couponValid = true;
                currentDiscount = parseFloat(res.discount) || 0;
                $('#couponDiscountValue').val(currentDiscount);
                showCouponMsg('success', '<i class="fas fa-check-circle mr-1"></i> ' + res.message);
                $('#couponInput').addClass('is-valid').removeClass('is-invalid');
            } else {
                resetCoupon();
                showCouponMsg('danger', '<i class="fas fa-times-circle mr-1"></i> ' + res.message);
                $('#couponInput').addClass('is-invalid').removeClass('is-valid');
            }
            calcTotal();
        },
        error: function() {
            resetCoupon();
            showCouponMsg('danger', 'Lỗi kiểm tra mã giảm giá');
            calcTotal();
        },
        complete: function() {
            $('#applyCouponBtn').prop('disabled', false).html('<i class="fas fa-check mr-1"></i> Áp dụng');
        }
    });
}

function resetCoupon() {
    couponValid = false;
    currentDiscount = 0;
    $('#couponDiscountValue').val(0);
}

function showCouponMsg(type, msg) {
    $('#couponMessage').html('<small class="text-' + type + '">' + msg + '</small>').show();
}

$('#billingCycle, #qty').on('change input', function() {
    if (couponValid) {
        // Re-validate coupon with new amount
        applyCoupon();
    } else {
        calcTotal();
    }
});
$('.addon-input').on('change input', function() {
    if (couponValid) {
        applyCoupon();
    } else {
        calcTotal();
    }
});

// Init
calcTotal();
</script>
@endpush
