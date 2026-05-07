@extends('layouts.auth')
@section('title', __('client.verify_email_title'))

@section('content')
<div class="auth-card">
    <div class="auth-logo">
        <div class="logo-icon"><i class="fas fa-envelope-open-text"></i></div>
        <h2>{{ __('client.verify_email_title') }}</h2>
        <p>{{ __('client.verify_email_text') }}</p>
    </div>

    @if (session('resent'))
        <div class="alert alert-success" style="background: rgba(16,185,129,0.1); border: 1px solid #10b981; color: #10b981; border-radius: 10px; padding: 15px; margin-bottom: 20px;">
            <i class="fas fa-check-circle mr-2"></i> {{ __('client.verify_sent') }}
        </div>
    @endif

    <div style="color: #cbd5e1; font-size: 0.95rem; line-height: 1.6; text-align: center; margin-bottom: 25px;">
        {{ __('client.verify_check_spam') }}
    </div>

    <form method="POST" action="{{ route('verification.resend') }}">
        @csrf
        <button type="submit" class="btn-auth">
            <i class="fas fa-paper-plane mr-2"></i> {{ __('client.verify_resend') }}
        </button>
    </form>

    <div class="auth-footer mt-4">
        <a href="{{ route('client.dashboard') }}"><i class="fas fa-arrow-left mr-1"></i> {{ __('client.back') }}</a>
    </div>
</div>
@endsection
