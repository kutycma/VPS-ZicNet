@extends('layouts.client')
@section('title', __('client.ticket_system') . ' #' . $ticket->id)
@section('page_title', __('client.support') . ': ' . $ticket->subject)
@section('content')
<div class="row">
    <div class="col-md-3">
        <div class="card card-primary card-outline">
            <div class="card-body box-profile">
                <h3 class="profile-username text-center">{{ __('client.ticket_id') }} #{{ $ticket->id }}</h3>
                <p class="text-muted text-center"><span class="badge badge-{{ $ticket->status_color }}">{{ $ticket->status_label }}</span></p>
                <ul class="list-group list-group-unbordered mb-3">
                    <li class="list-group-item">
                        <b>{{ __('client.department') }}</b> <a class="float-right">{{ $ticket->department === 'technical' ? __('client.technical') : __('client.sales') }}</a>
                    </li>
                    <li class="list-group-item">
                        <b>{{ __('client.priority') }}</b> <a class="float-right">{{ strtoupper($ticket->priority) }}</a>
                    </li>
                    <li class="list-group-item">
                        <b>{{ __('client.created_at') }}</b> <a class="float-right">{{ $ticket->created_at->format('d/m/Y H:i') }}</a>
                    </li>
                </ul>
                <a href="{{ route('client.tickets.index') }}" class="btn btn-default btn-block"><b><i class="fas fa-arrow-left"></i> {{ __('client.back') }}</b></a>
            </div>
        </div>
    </div>

    <div class="col-md-9">
        <div class="card direct-chat direct-chat-primary">
            <div class="card-header">
                <h3 class="card-title">{{ __('client.message') }}</h3>
            </div>
            
            <div class="card-body">
                <div class="direct-chat-messages" style="height: 500px;">
                    @foreach($ticket->messages as $msg)
                        @if($msg->user_id === Auth::id())
                            <div class="direct-chat-msg right">
                                <div class="direct-chat-infos clearfix">
                                    <span class="direct-chat-name float-right">{{ Auth::user()->name }}</span>
                                    <span class="direct-chat-timestamp float-left">{{ $msg->created_at->format('d/m/Y H:i:s') }}</span>
                                </div>
                                <img class="direct-chat-img" src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=0D8ABC&color=fff" alt="User Image">
                                <div class="direct-chat-text">
                                    {!! nl2br(e($msg->message)) !!}
                                </div>
                            </div>
                        @else
                            <div class="direct-chat-msg">
                                <div class="direct-chat-infos clearfix">
                                    <span class="direct-chat-name float-left">{{ $msg->user->name ?? 'Admin' }} <small class="badge badge-danger">Admin</small></span>
                                    <span class="direct-chat-timestamp float-right">{{ $msg->created_at->format('d/m/Y H:i:s') }}</span>
                                </div>
                                <img class="direct-chat-img" src="https://ui-avatars.com/api/?name=NV&background=dc3545&color=fff" alt="Admin Image">
                                <div class="direct-chat-text">
                                    {!! nl2br(e($msg->message)) !!}
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
            
            @if($ticket->status !== 'closed')
            <div class="card-footer">
                <form action="{{ route('client.tickets.reply', $ticket) }}" method="POST">
                    @csrf
                    <div class="input-group mb-2">
                        <textarea name="message" class="form-control" rows="3" placeholder="{{ __('client.reply') }}..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-paper-plane"></i> {{ __('client.send') }}</button>
                </form>
            </div>
            @else
            <div class="card-footer text-center text-muted bg-light">
                <i class="fas fa-lock"></i> {{ __('client.close') }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
