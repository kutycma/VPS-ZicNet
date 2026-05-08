@extends('layouts.auth')
@section('title', __('Reset Password'))

@section('content')
<div class="auth-card">
    <div class="auth-logo">
        <div class="logo-icon"><i class="fas fa-envelope-open-text"></i></div>
        <h2>VPS ZicNet</h2>
        <p>{{ __('Reset Password') }}</p>
    </div>

    @if (session('status'))
        <div class="alert alert-success" style="border-radius:14px; font-size:0.9rem; padding: 12px 16px; background: rgba(48, 209, 88, 0.18); border: 1px solid rgba(48, 209, 88, 0.28); color: #a8ffc9;">
            <i class="fas fa-check-circle mr-1"></i> {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="form-group">
            <label for="email"><i class="fas fa-envelope mr-1"></i> {{ __('Email Address') }}</label>
            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror"
                   name="email" value="{{ old('email') }}" required autocomplete="email" autofocus
                   placeholder="your@email.com">
            @error('email')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>

        <button type="submit" class="btn-auth">
            <i class="fas fa-paper-plane mr-2"></i> {{ __('Send Password Reset Link') }}
        </button>
    </form>

    <div class="auth-footer">
        <a href="{{ route('login') }}"><i class="fas fa-arrow-left mr-1"></i> {{ __('Back to Login') }}</a>
    </div>
</div>
@endsection
