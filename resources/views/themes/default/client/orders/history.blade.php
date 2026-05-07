@extends('layouts.client')
@section('title', __('client.order_history'))
@section('page_title', __('client.order_history'))
@section('content')
<div class="card">
    <div class="card-header border-bottom d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">{{ __('client.order_history') }}</h3>
    </div>
    <div class="card-body p-0">
    <div class="table-responsive">
    <table class="table table-hover mb-0"><thead><tr><th>#</th><th>{{ __('client.plan') }}</th><th>{{ __('client.type') }}</th><th>{{ __('client.amount') }}</th><th>{{ __('client.status') }}</th><th>{{ __('client.time') }}</th></tr></thead>
    <tbody>@forelse($orders as $o)
        <tr><td>{{ $o->id }}</td><td>{{ $o->vpsPlan->name ?? '—' }}</td><td><span class="badge badge-info">{{ $o->type }}</span></td><td>{{ number_format($o->amount, 0, ',', '.') }}đ</td><td><span class="badge badge-{{ $o->status_badge }}">{{ $o->status }}</span></td><td>{{ $o->created_at->format('d/m/Y H:i') }}</td></tr>
    @empty<tr><td colspan="6" class="text-center py-4 text-muted">{{ __('client.no_orders') }}</td></tr>@endforelse</tbody></table>
    <div class="px-3">{{ $orders->links() }}</div>
</div></div>
@endsection
