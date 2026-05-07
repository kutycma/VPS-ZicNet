@extends('layouts.client')
@section('title', __('client.waiting_title'))
@section('page_title', __('client.waiting_title'))
@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8 col-md-11">
        <div class="card" style="border-radius:20px; overflow:hidden; border:none; box-shadow: 0 10px 40px rgba(99,102,241,0.12);">
            <div class="card-body p-0">

                {{-- Status Header --}}
                <div id="statusHeader" style="background:linear-gradient(135deg, #f59e0b, #d97706); padding:25px 30px; text-align:center; position:relative; overflow:hidden; transition: background 0.5s;">
                    <div style="position:absolute; top:-30px; right:-30px; width:120px; height:120px; background:rgba(255,255,255,0.1); border-radius:50%;"></div>
                    <div id="statusIcon" style="width:60px;height:60px;border-radius:50%;background:rgba(255,255,255,0.2);display:flex;align-items:center;justify-content:center;margin:0 auto 10px;">
                        <i class="fas fa-clock fa-2x text-white" id="mainIcon"></i>
                    </div>
                    <h4 id="statusTitle" style="color:#fff; font-weight:700; margin-bottom:5px;">{{ __('client.waiting_payment') }}</h4>
                    <p id="statusSubtitle" style="color:rgba(255,255,255,0.9); margin:0; font-size:0.9rem;">
                        {{ __('client.waiting_subtitle') }}
                    </p>
                </div>

                {{-- Countdown Timer --}}
                <div id="countdownBar" style="background:#fef3c7; padding:12px 30px; display:flex; align-items:center; justify-content:center; gap:10px; border-bottom:1px solid #fde68a;">
                    <i class="fas fa-hourglass-half text-warning"></i>
                    <span style="font-weight:600; color:#92400e; font-size:0.95rem;">{{ __('client.expires_in') }} <span id="countdown" style="font-family:monospace; font-size:1.1rem; color:#d97706;">--:--</span></span>
                </div>

                <div style="padding:25px 30px;">
                    {{-- Thông tin chuyển khoản --}}
                    <div id="bankInfoSection">
                        @if($paymentMethod)
                        <div style="background:#f8fafc; border-radius:16px; padding:22px; margin-bottom:20px; border:1px solid #e2e8f0;">
                            <h6 style="font-weight:700; color:#6366f1; margin-bottom:15px;">
                                <i class="fas fa-university mr-2"></i>{{ $paymentMethod->name }}
                            </h6>

                            <div style="display:grid; gap:12px;">
                                @if($paymentMethod->bank_name)
                                <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 14px; background:#fff; border-radius:10px; border:1px solid #f1f5f9;">
                                    <span style="color:#64748b; font-size:0.9rem;"><i class="fas fa-building mr-2"></i>{{ __('client.bank_name') }}</span>
                                    <strong style="color:#1e293b;">{{ $paymentMethod->bank_name }}</strong>
                                </div>
                                @endif

                                @if($paymentMethod->account_number)
                                <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 14px; background:#fff; border-radius:10px; border:1px solid #f1f5f9;">
                                    <span style="color:#64748b; font-size:0.9rem;"><i class="fas fa-hashtag mr-2"></i>{{ __('client.account_number') }}</span>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <strong style="color:#1e293b; font-family:monospace; font-size:1.05rem;">{{ $paymentMethod->account_number }}</strong>
                                        <button type="button" onclick="copyText('{{ $paymentMethod->account_number }}', '{{ __('client.account_number') }}')" class="btn btn-sm btn-outline-primary" style="border-radius:8px; padding:3px 10px;">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                                @endif

                                @if($paymentMethod->account_name)
                                <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 14px; background:#fff; border-radius:10px; border:1px solid #f1f5f9;">
                                    <span style="color:#64748b; font-size:0.9rem;"><i class="fas fa-user mr-2"></i>{{ __('client.account_owner') }}</span>
                                    <strong style="color:#1e293b;">{{ $paymentMethod->account_name }}</strong>
                                </div>
                                @endif

                                {{-- Số tiền --}}
                                <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 14px; background:linear-gradient(135deg, #f0fdf4, #dcfce7); border-radius:10px; border:1px solid #bbf7d0;">
                                    <span style="color:#15803d; font-size:0.9rem;"><i class="fas fa-money-bill-wave mr-2"></i>{{ __('client.transfer_amount') }}</span>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <strong style="color:#15803d; font-size:1.2rem;">{{ number_format((float)$deposit->amount, 0, ',', '.') }}đ</strong>
                                        <button type="button" onclick="copyText('{{ (int)$deposit->amount }}', '{{ __('client.transfer_amount') }}')" class="btn btn-sm btn-outline-success" style="border-radius:8px; padding:3px 10px;">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </div>

                                {{-- Nội dung CK --}}
                                <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 14px; background:linear-gradient(135deg, #fef3c7, #fde68a); border-radius:10px; border:1px solid #fbbf24;">
                                    <span style="color:#92400e; font-size:0.9rem;"><i class="fas fa-pen-alt mr-2"></i>{{ __('client.transfer_content') }}</span>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <strong style="color:#92400e; font-size:1.1rem; font-family:monospace; letter-spacing:1px;">{{ $deposit->transaction_code }}</strong>
                                        <button type="button" onclick="copyText('{{ $deposit->transaction_code }}', '{{ __('client.transfer_content') }}')" class="btn btn-sm btn-outline-warning" style="border-radius:8px; padding:3px 10px; color:#92400e; border-color:#f59e0b;">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Custom instructions --}}
                        @if($paymentMethod->instructions)
                        <div style="background:linear-gradient(135deg, #eff6ff, #dbeafe); border-radius:12px; padding:14px 18px; margin-bottom:20px; border:1px solid #bfdbfe;">
                            <p style="margin:0; color:#1e40af; font-size:0.9rem;">
                                <i class="fas fa-info-circle mr-1"></i> {{ $paymentMethod->instructions }}
                            </p>
                        </div>
                        @endif

                        {{-- QR Code --}}
                        @if($paymentMethod->account_number && $paymentMethod->bank_name)
                        @php
                            $bankBins = [
                                'ACB' => '970416', 'acb' => '970416',
                                'Vietcombank' => '970436', 'VCB' => '970436',
                                'Techcombank' => '970407', 'TCB' => '970407',
                                'MBBank' => '970422', 'MB' => '970422',
                                'BIDV' => '970418',
                                'Vietinbank' => '970415', 'CTG' => '970415',
                                'VPBank' => '970432',
                                'Sacombank' => '970403', 'STB' => '970403',
                                'TPBank' => '970423',
                                'HDBank' => '970437',
                                'SHB' => '970443',
                                'MSB' => '970426',
                                'OCB' => '970448',
                                'Agribank' => '970405',
                            ];
                            $bankBin = '970416';
                            foreach ($bankBins as $key => $bin) {
                                if (stripos($paymentMethod->bank_name, $key) !== false) {
                                    $bankBin = $bin;
                                    break;
                                }
                            }
                            $qrUrl = "https://img.vietqr.io/image/{$bankBin}-{$paymentMethod->account_number}-compact.jpg?amount=" . (int)$deposit->amount . "&addInfo=" . urlencode($deposit->transaction_code) . "&accountName=" . urlencode($paymentMethod->account_name ?? '');
                        @endphp
                        <div style="text-align:center; margin-bottom:20px;">
                            <p style="font-weight:600; color:#1e293b; margin-bottom:12px;">
                                <i class="fas fa-qrcode mr-1 text-primary"></i> {{ __('client.scan_qr') }}
                            </p>
                            <div style="display:inline-block; padding:12px; background:#fff; border-radius:16px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border:2px solid #e2e8f0;">
                                <img src="{{ $qrUrl }}" alt="QR Code" style="max-width:250px; width:100%; border-radius:10px;" loading="lazy">
                            </div>
                            <p style="color:#64748b; font-size:0.85rem; margin-top:10px;">
                                <i class="fas fa-info-circle mr-1"></i> {{ __('client.qr_guide') }}
                            </p>
                        </div>
                        @endif
                        @endif

                        {{-- Warning --}}
                        <div style="background:linear-gradient(135deg, #fef2f2, #fee2e2); border-radius:12px; padding:15px 18px; margin-bottom:20px; border:1px solid #fecaca;">
                            <p style="margin:0; color:#991b1b; font-size:0.9rem; display:flex; align-items:flex-start; gap:8px;">
                                <i class="fas fa-exclamation-triangle mt-1" style="min-width:16px;"></i>
                                <span><strong>{{ __('client.important') }}</strong> {{ __('client.transfer_warning', ['code' => $deposit->transaction_code]) }}</span>
                            </p>
                        </div>

                        {{-- Actions --}}
                        <div style="display:flex; gap:12px; justify-content:center;">
                            <form action="{{ route('client.billing.deposit.cancel', $deposit) }}" method="POST" id="cancelForm">
                                @csrf
                                <button type="button" onclick="confirmCancel()" class="btn btn-outline-secondary" style="border-radius:12px; padding:12px 28px; font-weight:600;">
                                    <i class="fas fa-times mr-1"></i> {{ __('client.cancel_transaction') }}
                                </button>
                            </form>
                            <a href="{{ route('client.billing.index') }}" class="btn btn-outline-primary" style="border-radius:12px; padding:12px 28px; font-weight:600;">
                                <i class="fas fa-arrow-left mr-1"></i> {{ __('client.back_to_billing') }}
                            </a>
                        </div>
                    </div>

                    {{-- Success section --}}
                    <div id="successSection" style="display:none; text-align:center; padding:30px 0;">
                        <div style="width:100px; height:100px; background:linear-gradient(135deg, #10b981, #059669); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 20px; animation: popIn 0.5s ease;">
                            <i class="fas fa-check fa-3x text-white"></i>
                        </div>
                        <h3 style="font-weight:700; color:#1e293b; margin-bottom:10px;">{{ __('client.deposit_success') }} 🎉</h3>
                        <p style="color:#64748b; margin-bottom:5px;">{{ __('client.deposit_amount') }} <strong id="successAmount" style="color:#10b981; font-size:1.2rem;"></strong></p>
                        <p style="color:#64748b; margin-bottom:25px;">{{ __('client.new_balance') }} <strong id="newBalance" style="color:#6366f1; font-size:1.2rem;"></strong></p>
                        <a href="{{ route('client.billing.index') }}" class="btn btn-primary btn-lg" style="border-radius:14px; padding:14px 40px; font-weight:700;">
                            <i class="fas fa-wallet mr-2"></i> {{ __('client.view_balance') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Polling indicator --}}
        <div id="pollingIndicator" style="text-align:center; margin-top:12px;">
            <small style="color:#94a3b8;">
                <i class="fas fa-sync-alt fa-spin mr-1" id="pollingSpinner"></i>
                <span id="pollingText">{{ __('client.auto_checking') }}</span>
            </small>
        </div>
    </div>
</div>

@push('styles')
<style>
@keyframes popIn {0%{transform:scale(0);opacity:0;}60%{transform:scale(1.15);}100%{transform:scale(1);opacity:1;}}
@keyframes pulse {0%,100%{opacity:1;}50%{opacity:0.5;}}
#mainIcon.pulse-anim { animation: pulse 2s infinite; }
</style>
@endpush

@push('scripts')
<script>
const statusUrl = '{{ route("client.billing.deposit.status", $deposit) }}';
const expiresAt = new Date('{{ $deposit->expires_at->toIso8601String() }}').getTime();
let pollInterval = null;
let isCompleted = false;

// i18n strings
const lang = {
    expired: @json(__('client.request_expired')),
    expiredSub: @json(__('client.expired_subtitle')),
    successTitle: @json(__('client.payment_success_title')),
    successDesc: @json(__('client.payment_success_desc')),
    confirmed: @json(__('client.payment_confirmed')),
    swalTitle: @json(__('client.success_swal_title')),
    swalBtn: @json(__('client.success_swal_btn')),
    cancelTitle: @json(__('client.cancel_confirm_title')),
    cancelText: @json(__('client.cancel_confirm_text')),
    cancelYes: @json(__('client.cancel_yes')),
    cancelNo: @json(__('client.cancel_no')),
    copied: @json(__('client.copied_label')),
};

function updateCountdown() {
    if (isCompleted) return;
    const now = Date.now();
    const remaining = Math.max(0, Math.floor((expiresAt - now) / 1000));
    if (remaining <= 0) {
        document.getElementById('countdown').textContent = lang.expired;
        document.getElementById('countdownBar').style.background = '#fee2e2';
        document.getElementById('statusHeader').style.background = 'linear-gradient(135deg, #ef4444, #dc2626)';
        document.getElementById('statusTitle').textContent = lang.expired;
        document.getElementById('statusSubtitle').textContent = lang.expiredSub;
        document.getElementById('mainIcon').className = 'fas fa-times-circle fa-2x text-white';
        clearInterval(pollInterval);
        return;
    }
    const minutes = Math.floor(remaining / 60);
    const seconds = remaining % 60;
    document.getElementById('countdown').textContent = String(minutes).padStart(2,'0') + ':' + String(seconds).padStart(2,'0');
    if (remaining < 300) document.getElementById('countdown').style.color = '#dc2626';
}

function checkStatus() {
    if (isCompleted) return;
    fetch(statusUrl, {headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json'}})
    .then(res => res.json())
    .then(data => {
        if (data.status === 'completed') {
            isCompleted = true;
            clearInterval(pollInterval);
            showSuccess(data.formatted_amount, data.new_balance);
        } else if (data.status === 'expired' || data.status === 'cancelled') {
            clearInterval(pollInterval);
            window.location.href = '{{ route("client.billing.deposit") }}';
        }
    }).catch(err => console.log('Status check err:', err));
}

function showSuccess(amount, balance) {
    document.getElementById('statusHeader').style.background = 'linear-gradient(135deg, #10b981, #059669)';
    document.getElementById('statusTitle').textContent = lang.successTitle;
    document.getElementById('statusSubtitle').textContent = lang.successDesc;
    document.getElementById('mainIcon').className = 'fas fa-check-circle fa-2x text-white';
    document.getElementById('countdownBar').style.display = 'none';
    document.getElementById('bankInfoSection').style.display = 'none';
    document.getElementById('successSection').style.display = 'block';
    document.getElementById('successAmount').textContent = amount;
    document.getElementById('newBalance').textContent = balance;
    document.getElementById('pollingSpinner').className = 'fas fa-check-circle text-success mr-1';
    document.getElementById('pollingText').textContent = lang.confirmed;
    Swal.fire({icon:'success', title: lang.swalTitle, html:`<strong>${amount}</strong><br><strong>${balance}</strong>`, confirmButtonColor:'#10b981', confirmButtonText: lang.swalBtn});
}

function confirmCancel() {
    Swal.fire({title: lang.cancelTitle, text: lang.cancelText, icon:'warning', showCancelButton:true, confirmButtonColor:'#ef4444', cancelButtonColor:'#94a3b8', confirmButtonText: lang.cancelYes, cancelButtonText: lang.cancelNo}).then(r=>{if(r.isConfirmed)document.getElementById('cancelForm').submit();});
}

function copyText(text, label) {
    navigator.clipboard.writeText(text).then(()=>{Swal.fire({toast:true,position:'top-end',icon:'success',title: lang.copied + ' ' + label,showConfirmButton:false,timer:1500});});
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('mainIcon').classList.add('pulse-anim');
    updateCountdown();
    setInterval(updateCountdown, 1000);
    pollInterval = setInterval(checkStatus, 5000);
    setTimeout(checkStatus, 2000);
});
</script>
@endpush
@endsection
