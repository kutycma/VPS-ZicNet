@extends('layouts.client')
@section('title', __('client.billing'))
@section('page_title', __('client.billing'))
@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card" style="border-radius:16px;">
            <div class="card-body text-center">
                <p class="text-muted mb-1">{{ __('client.current_balance') }}</p>
                <h2 style="font-weight:800;color:#10b981;">{{ number_format((float)$user->balance, 0, ',', '.') }}đ</h2>
                <hr>
                <a href="{{ route('client.billing.deposit') }}" class="btn btn-primary btn-lg btn-block" style="border-radius:14px; padding:14px; font-weight:700; font-size:1rem; box-shadow: 0 4px 15px rgba(99,102,241,0.3);">
                    <i class="fas fa-plus-circle mr-2"></i> {{ __('client.auto_deposit') }}
                </a>
                <p class="text-muted mt-2 mb-0" style="font-size:0.85rem;">
                    <i class="fas fa-bolt mr-1 text-warning"></i> {{ __('client.auto_confirm_1min') }}
                </p>
            </div>
        </div>

        {{-- Recent Deposits --}}
        @if($recentDeposits->count() > 0)
        <div class="card" style="border-radius:16px;">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-history mr-2"></i>{{ __('client.recent_deposits') }}</h3></div>
            <div class="card-body p-0">
                @foreach($recentDeposits as $dep)
                <div style="padding:12px 18px; border-bottom:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <code style="font-weight:600; color:#6366f1;">{{ $dep->transaction_code }}</code><br>
                        <small class="text-muted">{{ $dep->created_at->format('d/m H:i') }}</small>
                        @if($dep->paymentMethod)
                            <br><small class="text-muted"><i class="fas fa-university mr-1"></i>{{ $dep->paymentMethod->name }}</small>
                        @endif
                    </div>
                    <div style="text-align:right;">
                        <strong>{{ number_format((float)$dep->amount, 0, ',', '.') }}đ</strong><br>
                        <span class="badge badge-{{ $dep->status_badge }}" style="font-size:0.75rem;">{{ $dep->status_label }}</span>
                        @if($dep->status === 'pending' && !$dep->expires_at->isPast())
                            <br><a href="{{ route('client.billing.deposit.waiting', $dep) }}" class="text-primary" style="font-size:0.8rem;"><i class="fas fa-arrow-right"></i> {{ __('client.continue') }}</a>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
    <div class="col-md-8">
        <div class="card" style="border-radius:16px;">
            <div class="card-header"><h3 class="card-title">{{ __('client.transaction_history') }}</h3></div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover">
                    <thead><tr><th>{{ __('client.type') }}</th><th>{{ __('client.amount') }}</th><th>{{ __('client.before') }}</th><th>{{ __('client.after') }}</th><th>{{ __('client.description') }}</th><th>{{ __('client.time') }}</th></tr></thead>
                    <tbody>@forelse($transactions as $tx)
                        <tr>
                            <td><span class="badge badge-{{ $tx->type_badge }}">{{ $tx->type_label }}</span></td>
                            <td style="color:{{ $tx->amount >= 0 ? '#10b981' : '#ef4444' }};font-weight:600;">{{ $tx->formatted_amount }}</td>
                            <td>{{ number_format((float)$tx->balance_before, 0, ',', '.') }}đ</td>
                            <td>{{ number_format((float)$tx->balance_after, 0, ',', '.') }}đ</td>
                            <td>{{ $tx->description }}</td>
                            <td>{{ $tx->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-3 text-muted">{{ __('client.no_transactions') }}</td></tr>
                    @endforelse</tbody>
                </table>
                <div class="px-3">{{ $transactions->links() }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
