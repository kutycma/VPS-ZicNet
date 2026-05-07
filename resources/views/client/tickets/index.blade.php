@extends('layouts.client')
@section('title', __('client.customer_support'))
@section('page_title', __('client.ticket_system'))
@section('content')
<div class="mb-3">
    <a href="{{ route('client.tickets.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> {{ __('client.new_request') }}</a>
</div>
<div class="card"><div class="card-body table-responsive p-0">
    <table class="table table-hover align-middle">
        <thead>
            <tr>
                <th>{{ __('client.ticket_id') }}</th>
                <th>{{ __('client.subject') }}</th>
                <th>{{ __('client.department') }}</th>
                <th>{{ __('client.priority') }}</th>
                <th>{{ __('client.status') }}</th>
                <th>{{ __('client.last_updated') }}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @forelse($tickets as $ticket)
            <tr>
                <td>#{{ $ticket->id }}</td>
                <td><strong>{{ $ticket->subject }}</strong></td>
                <td>{{ $ticket->department === 'technical' ? __('client.technical') : __('client.sales') }}</td>
                <td>
                    @if($ticket->priority === 'high') <span class="badge badge-danger">{{ __('client.priority_high') }}</span>
                    @elseif($ticket->priority === 'medium') <span class="badge badge-warning">{{ __('client.priority_medium') }}</span>
                    @else <span class="badge badge-secondary">{{ __('client.priority_low') }}</span> @endif
                </td>
                <td><span class="badge badge-{{ $ticket->status_color }}">{{ $ticket->status_label }}</span></td>
                <td>{{ $ticket->updated_at->diffForHumans() }}</td>
                <td class="text-right">
                    <a href="{{ route('client.tickets.show', $ticket) }}" class="btn btn-sm btn-info"><i class="fas fa-eye"></i> {{ __('client.view') }}</a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center py-4 text-muted">
                    <i class="fas fa-life-ring fa-2x mb-2 d-block"></i>
                    {{ __('client.no_tickets') }}
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
    <div class="px-3 pb-3 mt-3">{{ $tickets->links() }}</div>
</div></div>
@endsection
