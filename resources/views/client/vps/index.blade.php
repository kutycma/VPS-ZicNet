@extends('layouts.client')
@section('title', __('client.my_vps'))
@section('page_title', __('client.my_vps'))
@section('content')
<div class="mb-4">
    <style> .hide-scroll::-webkit-scrollbar { display: none; } </style>
    <ul class="nav nav-pills flex-wrap" style="gap: 10px; padding-bottom:10px;">
        <li class="nav-item">
            <a class="nav-link text-nowrap font-weight-bold" href="{{ route('client.vps.index', ['tab' => 'active']) }}"
               style="border-radius:20px; padding:8px 20px; transition:all 0.3s; {{ $tab === 'active' ? 'background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;box-shadow:0 4px 10px rgba(99,102,241,0.3);' : 'background:#fff;color:#475569;border:1px solid #e2e8f0;' }}">
                {{ __('client.active') }} <span class="badge {{ $tab === 'active' ? 'badge-light text-primary' : 'badge-success' }} ml-1" style="border-radius:10px;">{{ $counts['active'] ?? 0 }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-nowrap font-weight-bold" href="{{ route('client.vps.index', ['tab' => 'expiring_soon']) }}"
               style="border-radius:20px; padding:8px 20px; transition:all 0.3s; {{ $tab === 'expiring_soon' ? 'background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;box-shadow:0 4px 10px rgba(245,158,11,0.3);' : 'background:#fff;color:#475569;border:1px solid #e2e8f0;' }}">
                {{ __('client.expiring_soon') }} <span class="badge {{ $tab === 'expiring_soon' ? 'badge-light text-warning' : 'badge-warning' }} ml-1" style="border-radius:10px;">{{ $counts['expiring_soon'] ?? 0 }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-nowrap font-weight-bold" href="{{ route('client.vps.index', ['tab' => 'expired']) }}"
               style="border-radius:20px; padding:8px 20px; transition:all 0.3s; {{ $tab === 'expired' ? 'background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff;box-shadow:0 4px 10px rgba(239,68,68,0.3);' : 'background:#fff;color:#475569;border:1px solid #e2e8f0;' }}">
                {{ __('client.expired') }} <span class="badge {{ $tab === 'expired' ? 'badge-light text-danger' : 'badge-danger' }} ml-1" style="border-radius:10px;">{{ $counts['expired'] ?? 0 }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-nowrap font-weight-bold" href="{{ route('client.vps.index', ['tab' => 'deleted']) }}"
               style="border-radius:20px; padding:8px 20px; transition:all 0.3s; {{ $tab === 'deleted' ? 'background:linear-gradient(135deg,#64748b,#475569);color:#fff;box-shadow:0 4px 10px rgba(100,116,139,0.3);' : 'background:#fff;color:#475569;border:1px solid #e2e8f0;' }}">
                {{ __('client.deleted') }} <span class="badge {{ $tab === 'deleted' ? 'badge-light text-secondary' : 'badge-secondary' }} ml-1" style="border-radius:10px;">{{ $counts['deleted'] ?? 0 }}</span>
            </a>
        </li>
    </ul>
</div>
<div class="card d-none d-md-block"><div class="card-body table-responsive p-0">
    <table class="table table-hover">
        <thead><tr><th class="d-none d-md-table-cell">ID</th><th class="d-none d-md-table-cell">{{ __('client.plan') }}</th><th>{{ __('client.ip_address') }}</th><th class="d-none d-md-table-cell">{{ __('client.os') }}</th><th>{{ __('client.status') }}</th><th>{{ __('client.expiry') }}</th><th class="d-none d-md-table-cell">{{ __('client.auto_renew') }}</th><th></th></tr></thead>
        <tbody>@forelse($instances as $vps)
            <tr class="{{ ($vps->status === 'expired' || $vps->isExpired()) ? 'table-danger' : '' }}">
                <td class="d-none d-md-table-cell">#{{ $vps->vps_provider_id ?? $vps->id }}</td>
                <td class="d-none d-md-table-cell"><strong>{{ $vps->plan->name ?? '—' }}</strong></td>
                <td><code>{{ $vps->ip_address ?? '—' }}</code></td>
                <td class="d-none d-md-table-cell">{{ $vps->os ?? '—' }}</td>
                <td><span class="badge badge-{{ $vps->status_badge }}">{{ $vps->status }}</span></td>
                <td>
                    @if($vps->expires_at)
                        @if($vps->status === 'expired' || $vps->isExpired())
                            <span class="text-danger font-weight-bold"><i class="fas fa-exclamation-triangle"></i> {{ __('client.expired') }}</span><br>
                            <small class="text-muted"><del>{{ $vps->expires_at->format('d/m/Y') }}</del></small>
                        @else
                            <strong>{{ $vps->expires_at->format('d/m/Y') }}</strong>
                            @if(isset($vps->provider_data['day-left'])) <br><small class="text-success font-weight-bold">{{ $vps->provider_data['day-left'] }}</small> @endif
                        @endif
                    @else —
                    @endif
                </td>
                <td class="d-none d-md-table-cell">{{ $vps->auto_renew ? '✅' : '❌' }}</td>
                <td><a href="{{ route('client.vps.show', $vps) }}" class="btn btn-sm btn-primary"><i class="fas fa-cog"></i> {{ __('client.manage') }}</a></td>
            </tr>
        @empty<tr><td colspan="8" class="text-center py-4 text-muted"><i class="fas fa-server fa-2x mb-2 d-block"></i>{{ __('client.no_vps_yet') }}<br><a href="{{ route('client.orders.plans') }}" class="btn btn-primary mt-2">{{ __('client.buy_vps_now') }}</a></td></tr>@endforelse</tbody>
    </table>
    <div class="px-3 py-2">{{ $instances->links() }}</div>
</div></div>

<!-- MOBILE VIEW: CARDS -->
<div class="d-block d-md-none">
    @forelse($instances as $vps)
        <div class="card mb-3 {{ ($vps->status === 'expired' || $vps->isExpired()) ? 'border-danger' : 'border-0 shadow-sm' }}" style="border-radius:12px;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0 font-weight-bold"><i class="fas fa-server text-indigo mr-1"></i> <code>{{ $vps->ip_address ?? '—' }}</code></h5>
                    <span class="badge badge-{{ $vps->status_badge }} px-2 py-1" style="font-size:0.8rem;">{{ strtoupper($vps->status) }}</span>
                </div>
                <div class="mb-3 text-muted" style="font-size: 0.9rem;">
                    <div class="mb-1"><strong>{{ __('client.package_label') }}</strong> {{ $vps->plan->name ?? '—' }} (#{{ $vps->vps_provider_id ?? $vps->id }})</div>
                    <div class="mb-1"><strong>{{ __('client.os_label') }}</strong> {{ $vps->os ?? '—' }}</div>
                    <div><strong>{{ __('client.expiry_label') }}</strong> 
                        @if($vps->expires_at)
                            @if($vps->status === 'expired' || $vps->isExpired())
                                <span class="text-danger font-weight-bold">{{ __('client.expired') }} (<del>{{ $vps->expires_at->format('d/m/Y') }}</del>)</span>
                            @else
                                <span class="text-dark font-weight-bold">{{ $vps->expires_at->format('d/m/Y') }}</span>
                                @if(isset($vps->provider_data['day-left'])) <span class="text-success font-weight-bold ml-1">({{ $vps->provider_data['day-left'] }})</span> @endif
                            @endif
                        @else —
                        @endif
                    </div>
                </div>
                <a href="{{ route('client.vps.show', $vps) }}" class="btn btn-primary btn-block font-weight-bold" style="border-radius:8px; background:linear-gradient(135deg,#6366f1,#8b5cf6); border:none;"><i class="fas fa-cog mr-1"></i> {{ __('client.manage_vps') }}</a>
            </div>
        </div>
    @empty
        <div class="text-center py-5 text-muted bg-white shadow-sm" style="border-radius:12px;">
            <i class="fas fa-hdd fa-3x mb-3 text-indigo opacity-50"></i>
            <h5>{{ __('client.no_vps_yet') }}</h5>
            <p class="mb-3">{{ __('client.no_vps_in_tab') }}</p>
            <a href="{{ route('client.orders.plans') }}" class="btn btn-primary" style="border-radius:20px; padding:8px 24px;">{{ __('client.buy_vps_now') }}</a>
        </div>
    @endforelse
    <div class="d-flex justify-content-center mt-3">{{ $instances->links() }}</div>
</div>
@endsection
