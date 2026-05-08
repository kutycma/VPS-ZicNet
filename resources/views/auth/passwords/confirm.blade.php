@extends('layouts.auth')
@section('title', __('Confirm Password'))

@section('content')
<div class="auth-card">
    <div class="auth-logo">
        <div class="logo-icon"><i class="fas fa-shield-alt"></i></div>
        <h2>VPS ZicNet</h2>
        <p>{{ __('Please confirm your password before continuing.') }}</p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div class="form-group">
            <label for="password"><i class="fas fa-lock mr-1"></i> {{ __('Password') }}</label>
            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror"
                   name="password" required autocomplete="current-password"
                   placeholder="••••••••">
            @error('password')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>

        <button type="submit" class="btn-auth">
            <i class="fas fa-check mr-2"></i> {{ __('Confirm Password') }}
        </button>
    </form>

    @if (Route::has('password.request'))
    <div class="auth-footer">
        <a href="{{ route('password.request') }}">{{ __('Forgot Your Password?') }}</a>
    </div>
    @endif
</div>
@endsection
