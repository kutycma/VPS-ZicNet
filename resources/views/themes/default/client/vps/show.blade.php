@extends('layouts.client')
@section('title', __('client.vps_management'))
@section('page_title', 'VPS #' . ($instance->vps_provider_id ?? $instance->id))
@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="card" style="border-radius:16px;">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-info-circle mr-2 text-primary"></i>{{ __('client.vps_info') }}</h3></div>
            <div class="card-body">
                @php
                    $pendingOrders = $instance->orders->where('status', 'pending');
                @endphp
                @if($pendingOrders->isNotEmpty())
                    <div class="alert alert-warning mb-3 py-2" style="border-radius: 10px;">
                        <i class="fas fa-spinner fa-spin mr-2"></i> <strong>{{ __('client.loading') }}</strong>
                    </div>
                @endif
                <div class="table-responsive">
                <table class="table table-borderless">
                    <tr><td class="text-muted" width="130">{{ __('client.plan') }}</td><td><strong>{{ $instance->plan->name ?? '—' }}</strong></td></tr>
                    <tr><td class="text-muted">{{ __('client.ip_address') }}</td><td><code style="font-size:1.1rem;">{{ $providerInfo['ip'] ?? ($instance->ip_address ?? '—') }}</code></td></tr>
                    <tr><td class="text-muted">{{ __('client.username') }}</td><td><code>{{ $providerInfo['username'] ?? ($instance->username ?? '—') }}</code></td></tr>
                    <tr><td class="text-muted">{{ __('client.password') }}</td><td>
                        <code id="vpsPass">{{ $providerInfo['password'] ?? ($instance->password ?? '—') }}</code>
                        <button class="btn btn-sm btn-outline-secondary ml-2" onclick="navigator.clipboard.writeText($('#vpsPass').text());Swal.fire({toast:true,position:'top-end',icon:'success',title:'Copied',showConfirmButton:false,timer:1500})"><i class="fas fa-copy"></i></button>
                    </td></tr>
                    @php
                        $realStatus = $providerInfo['vps-status'] ?? $instance->status;
                        if($realStatus === 'on') $realStatus = 'active';
                        if($realStatus === 'off') $realStatus = 'stopped';
                        if($realStatus === 'delete_vps') $realStatus = 'deleted';
                        
                        $badge = 'secondary';
                        if($realStatus === 'active') $badge = 'success';
                        if($realStatus === 'stopped') $badge = 'warning';
                        if($realStatus === 'deleted') $badge = 'danger';
                    @endphp
                    <tr><td class="text-muted">{{ __('client.status') }}</td><td><span class="badge badge-{{ $badge }}" style="font-size:0.9rem;">{{ strtoupper($realStatus) }}</span></td></tr>
                    
                    @php
                        $dayLeftRaw = $providerInfo['day-left'] ?? ($instance->provider_data['day-left'] ?? '');
                        $expDate = $providerInfo['next_due_date'] ?? ($instance->expires_at ? $instance->expires_at->format('d/m/Y') : '—');
                    @endphp
                    <tr><td class="text-muted">{{ __('client.expiry') }}</td><td>{{ $expDate }} @if($dayLeftRaw) <span class="text-info ml-2">({{ $dayLeftRaw }})</span> @endif</td></tr>
                    <tr><td class="text-muted">{{ __('client.auto_renew') }}</td><td>{{ $instance->auto_renew ? '✅' : '❌' }}</td></tr>
                </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card" style="border-radius:16px;">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-gamepad mr-2 text-success"></i>{{ __('client.actions') }}</h3></div>
            <div class="card-body text-center py-4">
                <form action="{{ route('client.vps.action', $instance) }}" method="POST" class="d-inline">@csrf
                    <input type="hidden" name="action" value="on">
                    <button class="btn btn-success m-2" style="border-radius:12px;min-width:120px;"><i class="fas fa-play mr-2"></i>{{ __('client.start') }}</button>
                </form>
                <form action="{{ route('client.vps.action', $instance) }}" method="POST" class="d-inline">@csrf
                    <input type="hidden" name="action" value="off">
                    <button class="btn btn-warning m-2" style="border-radius:12px;min-width:120px;"><i class="fas fa-stop mr-2"></i>{{ __('client.stop') }}</button>
                </form>
                <form action="{{ route('client.vps.action', $instance) }}" method="POST" class="d-inline">@csrf
                    <input type="hidden" name="action" value="restart">
                    <button class="btn btn-info m-2" style="border-radius:12px;min-width:120px;"><i class="fas fa-redo mr-2"></i>{{ __('client.restart') }}</button>
                </form>
                
                <hr>

                <a href="{{ route('client.vps.renew', $instance) }}" class="btn btn-outline-primary m-2" style="border-radius:12px;min-width:160px;">
                    <i class="fas fa-calendar-plus mr-2"></i>{{ __('client.renew') }}
                </a>

                <a href="{{ route('client.vps.upgrade', $instance) }}" class="btn btn-outline-warning m-2" style="border-radius:12px;min-width:160px;">
                    <i class="fas fa-arrow-circle-up mr-2"></i>{{ __('client.upgrade') }}
                </a>

                <a href="{{ route('client.vps.rebuild', $instance) }}" class="btn btn-outline-danger m-2" style="border-radius:12px;min-width:160px;">
                    <i class="fas fa-compact-disc mr-2"></i>{{ __('client.rebuild') }}
                </a>

                <form action="{{ route('client.vps.action', $instance) }}" method="POST" class="d-inline">@csrf
                    <input type="hidden" name="action" value="{{ $instance->auto_renew ? 'off-auto-renew' : 'on-auto-renew' }}">
                    <button class="btn btn-outline-primary m-2" style="border-radius:12px;min-width:160px;">
                        <i class="fas fa-sync-alt mr-2"></i>{{ $instance->auto_renew ? __('client.auto_renew') . ' OFF' : __('client.auto_renew') . ' ON' }}
                    </button>
                </form>

                <form action="{{ route('client.vps.action', $instance) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('client.confirm') }}?')">@csrf
                    <input type="hidden" name="action" value="cancel">
                    <button class="btn btn-outline-danger m-2" style="border-radius:12px;min-width:160px;"><i class="fas fa-trash mr-2"></i>{{ __('client.delete') }} VPS</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
