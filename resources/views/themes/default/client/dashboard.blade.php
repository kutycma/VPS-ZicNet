@extends('layouts.client')
@section('title', __('client.dashboard'))
@section('page_title', __('client.dashboard'))
@section('content')
<div class="row">

@php
    $noticePinnedActive = \App\Models\Setting::get('notice_pinned_active');
    $noticePinnedText = \App\Models\Setting::get('notice_pinned_text');
    
    $noticePopupActive = \App\Models\Setting::get('notice_popup_active');
    $noticePopupText = \App\Models\Setting::get('notice_popup_text');
    $popupHash = md5($noticePopupText ?? '');
@endphp

@if($noticePinnedActive == '1' && !empty($noticePinnedText))
    <div class="col-12 mb-3">
        <div class="alert alert-info" style="border-radius:10px; background: linear-gradient(to right, #eff6ff, #dbeafe); color: #1e40af; border: 1px solid #bfdbfe;">
            <i class="fas fa-bullhorn mr-2"></i> {!! $noticePinnedText !!}
        </div>
    </div>
@endif

    <div class="col-lg-3 col-md-6">
        <div class="stat-card mb-4" style="--bg1:#6366f1;--bg2:#8b5cf6;"><div class="stat-icon"><i class="fas fa-wallet"></i></div>
            <p class="mb-1" style="font-size:0.85rem;opacity:0.8;">{{ __('client.balance') }}</p>
            <h3 class="mb-0" style="font-weight:700;">{{ number_format($stats['balance'], 0, ',', '.') }}đ</h3>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stat-card mb-4" style="--bg1:#10b981;--bg2:#059669;"><div class="stat-icon"><i class="fas fa-server"></i></div>
            <p class="mb-1" style="font-size:0.85rem;opacity:0.8;">{{ __('client.active_vps') }}</p>
            <h3 class="mb-0" style="font-weight:700;">{{ $stats['active_vps'] }} / {{ $stats['total_vps'] }}</h3>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="stat-card mb-4" style="--bg1:#f59e0b;--bg2:#d97706;"><div class="stat-icon"><i class="fas fa-coins"></i></div>
            <p class="mb-1" style="font-size:0.85rem;opacity:0.8;">{{ __('client.total_spent') }}</p>
            <h3 class="mb-0" style="font-weight:700;">{{ number_format($stats['total_spent'], 0, ',', '.') }}đ</h3>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="text-center py-4">
            <a href="{{ route('client.orders.plans') }}" class="btn btn-primary btn-lg" style="border-radius:12px;padding:15px 30px;">
                <i class="fas fa-shopping-cart mr-2"></i> {{ __('client.buy_new_vps') }}
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card"><div class="card-header"><h3 class="card-title"><i class="fas fa-hdd mr-2"></i>{{ __('client.recent_vps') }}</h3></div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover"><thead><tr><th>{{ __('client.plan') }}</th><th>{{ __('client.ip_address') }}</th><th>{{ __('client.status') }}</th><th></th></tr></thead>
            <tbody>@forelse($recentVps as $vps)
                <tr><td>{{ $vps->plan->name ?? '—' }}</td><td><code>{{ $vps->ip_address ?? '—' }}</code></td><td><span class="badge badge-{{ $vps->status_badge }}">{{ $vps->status }}</span></td><td><a href="{{ route('client.vps.show', $vps) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a></td></tr>
            @empty<tr><td colspan="4" class="text-center py-3 text-muted">{{ __('client.no_vps') }}</td></tr>@endforelse</tbody></table>
        </div></div>
    </div>
    <div class="col-md-6">
        <div class="card"><div class="card-header"><h3 class="card-title"><i class="fas fa-exchange-alt mr-2"></i>{{ __('client.recent_transactions') }}</h3></div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover"><thead><tr><th>{{ __('client.type') }}</th><th>{{ __('client.amount') }}</th><th>{{ __('client.time') }}</th></tr></thead>
            <tbody>@forelse($recentTransactions as $tx)
                <tr><td><span class="badge badge-{{ $tx->type_badge }}">{{ $tx->type_label }}</span></td><td style="color:{{ $tx->amount >= 0 ? '#10b981' : '#ef4444' }};font-weight:600;">{{ $tx->formatted_amount }}</td><td>{{ $tx->created_at->diffForHumans() }}</td></tr>
            @empty<tr><td colspan="3" class="text-center py-3 text-muted">{{ __('client.no_transactions') }}</td></tr>@endforelse</tbody></table>
        </div></div>
    </div>
</div>

@push('scripts')
@if($noticePopupActive == '1' && !empty($noticePopupText))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const popupHash = '{{ $popupHash }}';
        const hideNotice = localStorage.getItem('hide_notice_popup');
        
        if (hideNotice !== popupHash) {
            Swal.fire({
                title: '<i class="fas fa-bell text-warning"></i> {{ __("client.notification") }}',
                html: `{!! addslashes(str_replace("\n", "", nl2br(trim($noticePopupText)))) !!}`,
                showCancelButton: true,
                confirmButtonText: '{{ __("client.understood") }}',
                cancelButtonText: '{{ __("client.dont_show_again") }}',
                confirmButtonColor: '#6366f1',
                cancelButtonColor: '#9ca3af',
                allowOutsideClick: false
            }).then((result) => {
                if (result.dismiss === Swal.DismissReason.cancel) {
                    localStorage.setItem('hide_notice_popup', popupHash);
                }
            });
        }
    });
</script>
@endif
@endpush

@endsection
