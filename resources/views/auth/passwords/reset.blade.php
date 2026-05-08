@extends('layouts.auth')
@section('title', __('Reset Password'))

@section('content')
<div class="auth-card">
    <div class="auth-logo">
        <div class="logo-icon"><i class="fas fa-key"></i></div>
        <h2>VPS ZicNet</h2>
        <p>{{ __('Reset Password') }}</p>
    </div>

    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="form-group">
            <label for="email"><i class="fas fa-envelope mr-1"></i> {{ __('Email Address') }}</label>
            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror"
                   name="email" value="{{ $email ?? old('email') }}" required autocomplete="email" autofocus
                   placeholder="your@email.com">
            @error('email')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label for="password"><i class="fas fa-lock mr-1"></i> {{ __('Password') }}</label>
            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror"
                   name="password" required autocomplete="new-password"
                   placeholder="••••••••">
            @error('password')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label for="password-confirm"><i class="fas fa-check-double mr-1"></i> {{ __('Confirm Password') }}</label>
            <input id="password-confirm" type="password" class="form-control"
                   name="password_confirmation" required autocomplete="new-password"
                   placeholder="••••••••">
        </div>

        <button type="submit" class="btn-auth">
            <i class="fas fa-sync-alt mr-2"></i> {{ __('Reset Password') }}
        </button>
    </form>

    <div class="auth-footer">
        <a href="{{ route('login') }}"><i class="fas fa-arrow-left mr-1"></i> {{ __('Back to Login') }}</a>
    </div>
</div>
@endsection
