@extends('layouts.client')
@section('title', __('client.deposit_title'))
@section('page_title', __('client.deposit_title'))
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8 col-md-10">
        <div class="card" style="border-radius:20px; overflow:hidden; border:none; box-shadow: 0 10px 40px rgba(99,102,241,0.12);">
            <div class="card-body p-0">
                {{-- Header gradient --}}
                <div style="background:linear-gradient(135deg, #6366f1, #8b5cf6, #a78bfa); padding: 35px 30px; text-align:center; position:relative; overflow:hidden;">
                    <div style="position:absolute; top:-30px; right:-30px; width:120px; height:120px; background:rgba(255,255,255,0.1); border-radius:50%;"></div>
                    <div style="position:absolute; bottom:-20px; left:-20px; width:80px; height:80px; background:rgba(255,255,255,0.08); border-radius:50%;"></div>
                    <div style="width:70px;height:70px;border-radius:20px;background:rgba(255,255,255,0.2);display:flex;align-items:center;justify-content:center;margin:0 auto 15px; backdrop-filter:blur(10px);">
                        <i class="fas fa-wallet fa-2x text-white"></i>
                    </div>
                    <h3 style="color:#fff; font-weight:700; margin-bottom:5px;">{{ __('client.auto_deposit') }}</h3>
                    <p style="color:rgba(255,255,255,0.8); margin:0; font-size:0.95rem;">{{ __('client.deposit_subtitle') }}</p>
                </div>

                {{-- Balance info --}}
                <div style="background:linear-gradient(135deg, #f0fdf4, #ecfdf5); padding:12px 30px; display:flex; align-items:center; justify-content:space-between; border-bottom:1px solid #e2e8f0;">
                    <span style="color:#64748b; font-size:0.85rem;">{{ __('client.current_balance') }}</span>
                    <div style="font-size:1.2rem; font-weight:700; color:#10b981;">
                        <i class="fas fa-coins mr-1"></i>{{ number_format((float)Auth::user()->balance, 0, ',', '.') }}đ
                    </div>
                </div>

                {{-- Form --}}
                <form action="{{ route('client.billing.deposit.store') }}" method="POST" id="depositForm">
                    @csrf
                    <div style="padding: 25px 30px;">

                        {{-- Step 1: Chọn phương thức --}}
                        <div style="margin-bottom:25px;">
                            <label style="font-weight:600; color:#1e293b; font-size:1rem; margin-bottom:12px; display:block;">
                                <span style="display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; background:linear-gradient(135deg,#6366f1,#8b5cf6); color:#fff; border-radius:50%; font-size:0.8rem; margin-right:8px;">1</span>
                                {{ __('client.step_select_method') }}
                            </label>

                            @if($paymentMethods->isEmpty())
                            <div class="text-center py-4 text-muted" style="background:#f8fafc; border-radius:14px;">
                                <i class="fas fa-exclamation-triangle fa-2x mb-2 d-block text-warning"></i>
                                <p class="mb-0">{{ __('client.no_payment_methods') }}</p>
                            </div>
                            @else
                            <div style="display:grid; gap:12px;" id="methodGrid">
                                @foreach($paymentMethods as $pm)
                                <label class="method-card" data-method-id="{{ $pm->id }}" style="display:flex; align-items:center; gap:15px; padding:16px 18px; background:#f8fafc; border:2px solid #e2e8f0; border-radius:14px; cursor:pointer; transition: all 0.2s; margin:0;">
                                    <input type="radio" name="payment_method_id" value="{{ $pm->id }}" style="display:none;" {{ $loop->first ? 'checked' : '' }}>
                                    <div class="method-radio" style="min-width:24px; height:24px; border-radius:50%; border:2px solid #cbd5e1; display:flex; align-items:center; justify-content:center; transition: all 0.2s;">
                                        <div style="width:12px; height:12px; border-radius:50%; background:transparent; transition: all 0.2s;"></div>
                                    </div>
                                    <div style="flex:1;">
                                        <div style="font-weight:700; color:#1e293b; font-size:1rem;">{{ $pm->name }}</div>
                                        <div style="display:flex; gap:15px; flex-wrap:wrap; margin-top:4px;">
                                            @if($pm->bank_name)<small style="color:#64748b;"><i class="fas fa-university mr-1"></i>{{ $pm->bank_name }}</small>@endif
                                            @if($pm->account_number)<small style="color:#6366f1; font-weight:500;"><i class="fas fa-hashtag mr-1"></i>{{ $pm->account_number }}</small>@endif
                                        </div>
                                        @if($pm->instructions)
                                        <div style="margin-top:6px; color:#64748b; font-size:0.82rem;">
                                            <i class="fas fa-info-circle mr-1"></i>{{ $pm->instructions }}
                                        </div>
                                        @endif
                                    </div>
                                    @if($pm->hasApiConfig())
                                    <span style="background:linear-gradient(135deg, #10b981, #059669); color:#fff; padding:3px 10px; border-radius:20px; font-size:0.72rem; font-weight:600; white-space:nowrap;">
                                        <i class="fas fa-bolt mr-1"></i>{{ __('client.auto_tag') }}
                                    </span>
                                    @endif
                                </label>
                                @endforeach
                            </div>
                            @error('payment_method_id')
                                <div class="text-danger mt-2" style="font-size:0.9rem;"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</div>
                            @enderror
                            @endif
                        </div>

                        {{-- Step 2: Nhập số tiền --}}
                        <div style="margin-bottom:25px;">
                            <label style="font-weight:600; color:#1e293b; font-size:1rem; margin-bottom:12px; display:block;">
                                <span style="display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; background:linear-gradient(135deg,#6366f1,#8b5cf6); color:#fff; border-radius:50%; font-size:0.8rem; margin-right:8px;">2</span>
                                {{ __('client.step_enter_amount') }}
                            </label>

                            <div style="position:relative; margin-bottom:15px;">
                                <input type="text" name="amount_display" id="amountDisplay"
                                    class="form-control form-control-lg @error('amount') is-invalid @enderror"
                                    placeholder="{{ __('client.enter_amount') }}"
                                    style="font-size:1.4rem; font-weight:700; padding:16px 80px 16px 20px; border-radius:14px; border:2px solid #e2e8f0; text-align:center; transition: all 0.3s; background:#f8fafc;"
                                    onfocus="this.style.borderColor='#6366f1'; this.style.boxShadow='0 0 0 4px rgba(99,102,241,0.1)'"
                                    onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none'"
                                    autocomplete="off" inputmode="numeric">
                                <input type="hidden" name="amount" id="amountReal">
                                <span style="position:absolute; right:20px; top:50%; transform:translateY(-50%); font-weight:600; color:#94a3b8; font-size:1.1rem;">{{ __('client.currency') }}</span>
                            </div>
                            @error('amount')
                                <div class="text-danger mb-3" style="margin-top:-10px; font-size:0.9rem;"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</div>
                            @enderror

                            {{-- Quick amount buttons --}}
                            <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:8px;">
                                @foreach([20000, 50000, 100000, 200000, 500000, 1000000] as $amt)
                                <button type="button" class="btn-amount" onclick="setAmount({{ $amt }})" style="background:#f1f5f9; border:2px solid #e2e8f0; border-radius:10px; padding:11px 8px; font-weight:600; color:#475569; cursor:pointer; transition: all 0.2s; font-size:0.9rem;">
                                    {{ number_format($amt, 0, ',', '.') }}đ
                                </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- Info --}}
                        <div style="background:linear-gradient(135deg, #eff6ff, #dbeafe); border-radius:12px; padding:15px; margin-bottom:20px; border:1px solid #bfdbfe;">
                            <div style="display:flex; align-items:flex-start; gap:10px;">
                                <i class="fas fa-shield-alt" style="color:#3b82f6; margin-top:2px;"></i>
                                <div style="font-size:0.85rem; color:#2563eb; line-height:1.7;">
                                    <strong>{{ __('client.deposit_safe') }}</strong> {{ __('client.deposit_safe_desc') }}
                                </div>
                            </div>
                        </div>

                        <button type="submit" id="submitBtn" class="btn btn-primary btn-lg btn-block" disabled
                            style="border-radius:14px; padding:15px; font-weight:700; font-size:1.05rem; letter-spacing:0.5px; box-shadow: 0 6px 20px rgba(99,102,241,0.35);">
                            <i class="fas fa-arrow-right mr-2"></i> {{ __('client.continue') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let selectedMethod = {{ $paymentMethods->count() > 0 ? $paymentMethods->first()->id : 'null' }};
let enteredAmount = 0;
const langSelectMethod = @json(__('client.select_method_error'));
const langAmountInvalid = @json(__('client.amount_invalid'));
const langAmountMin = @json(__('client.amount_min_error'));
const langProcessing = @json(__('client.processing'));

document.querySelectorAll('.method-card').forEach(card => {
    card.addEventListener('click', function() {
        document.querySelectorAll('.method-card').forEach(c => {
            c.style.borderColor = '#e2e8f0';
            c.style.background = '#f8fafc';
            c.querySelector('.method-radio').style.borderColor = '#cbd5e1';
            c.querySelector('.method-radio div').style.background = 'transparent';
        });
        this.style.borderColor = '#6366f1';
        this.style.background = 'linear-gradient(135deg, #eef2ff, #e8e4fd)';
        this.querySelector('.method-radio').style.borderColor = '#6366f1';
        this.querySelector('.method-radio div').style.background = '#6366f1';
        this.querySelector('input[type="radio"]').checked = true;
        selectedMethod = this.dataset.methodId;
        checkReady();
    });
});

if (selectedMethod) {
    const card = document.querySelector(`.method-card[data-method-id="${selectedMethod}"]`);
    if (card) card.click();
}

function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function setAmount(amount) {
    document.getElementById('amountDisplay').value = formatNumber(amount);
    document.getElementById('amountReal').value = amount;
    enteredAmount = amount;
    document.querySelectorAll('.btn-amount').forEach(btn => {
        btn.style.background = '#f1f5f9';
        btn.style.borderColor = '#e2e8f0';
        btn.style.color = '#475569';
    });
    event.target.closest('.btn-amount').style.background = 'linear-gradient(135deg, #eff6ff, #dbeafe)';
    event.target.closest('.btn-amount').style.borderColor = '#6366f1';
    event.target.closest('.btn-amount').style.color = '#4f46e5';
    checkReady();
}

document.getElementById('amountDisplay').addEventListener('input', function() {
    let value = this.value.replace(/[^\d]/g, '');
    if (value) {
        enteredAmount = parseInt(value);
        this.value = formatNumber(enteredAmount);
        document.getElementById('amountReal').value = enteredAmount;
    } else {
        enteredAmount = 0;
        this.value = '';
        document.getElementById('amountReal').value = '';
    }
    document.querySelectorAll('.btn-amount').forEach(btn => {
        btn.style.background = '#f1f5f9';
        btn.style.borderColor = '#e2e8f0';
        btn.style.color = '#475569';
    });
    checkReady();
});

function checkReady() {
    document.getElementById('submitBtn').disabled = !(selectedMethod && enteredAmount >= 10000);
}

document.getElementById('depositForm').addEventListener('submit', function(e) {
    if (!selectedMethod) {
        e.preventDefault();
        Swal.fire({icon: 'warning', title: langAmountInvalid, text: langSelectMethod, confirmButtonColor: '#6366f1'});
        return;
    }
    if (!enteredAmount || enteredAmount < 10000) {
        e.preventDefault();
        Swal.fire({icon: 'warning', title: langAmountInvalid, text: langAmountMin, confirmButtonColor: '#6366f1'});
        return;
    }
    document.getElementById('submitBtn').disabled = true;
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> ' + langProcessing;
});
</script>

<style>
.btn-amount:hover {
    background: linear-gradient(135deg, #eff6ff, #dbeafe) !important;
    border-color: #6366f1 !important;
    color: #4f46e5 !important;
    transform: translateY(-1px);
    box-shadow: 0 3px 10px rgba(99,102,241,0.12);
}
.method-card:hover {
    border-color: #a5b4fc !important;
    background: #f1f5f9 !important;
}
</style>
@endpush
@endsection
