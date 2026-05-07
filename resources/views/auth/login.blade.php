@extends('layouts.auth')
@section('title', __('client.login_title'))

@section('content')
<div class="auth-card">
    <div class="auth-logo">
        <div class="logo-icon"><i class="fas fa-cloud"></i></div>
        <h2>VPS ZicNet</h2>
        <p>{{ __('client.login_subtitle') }}</p>
    </div>

    @if(session('error'))
        <div class="alert alert-danger" style="border-radius:10px; font-size:0.9rem; padding: 10px;">
            <i class="fas fa-exclamation-circle mr-1"></i> {{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="form-group">
            <label for="email"><i class="fas fa-envelope mr-1"></i> {{ __('client.email') }}</label>
            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror"
                   name="email" value="{{ old('email') }}" required autocomplete="email" autofocus
                   placeholder="your@email.com">
            @error('email')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label for="password">
                <i class="fas fa-lock mr-1"></i> {{ __('client.password_label') }}
            </label>
            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror"
                   name="password" required autocomplete="current-password"
                   placeholder="••••••••">
            @error('password')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
            @if (Route::has('password.request'))
                <div style="text-align:right;margin-top:8px;">
                    <a href="{{ route('password.request') }}" class="forgot-link" tabindex="-1">{{ __('client.forgot_password') }}</a>
                </div>
            @endif
        </div>

        <div class="form-check">
            <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
            <label for="remember">{{ __('client.remember_me') }}</label>
        </div>

        <button type="submit" class="btn-auth">
            <i class="fas fa-sign-in-alt mr-2"></i> {{ __('client.login_button') }}
        </button>
    </form>

    <div style="text-align: center; margin: 25px 0;">
        <span style="background: rgba(255,255,255,0.1); padding: 5px 15px; border-radius: 20px; font-size: 0.85rem; color: #cbd5e1;">{{ __('client.or') }}</span>
    </div>

    <a href="{{ route('auth.google') }}" class="btn-auth" style="background: white; color: #333; text-decoration: none; display: flex; align-items: center; justify-content: center; border: 1px solid #ddd;">
        <i class="fab fa-google" style="color: #EA4335; font-size: 1.2rem; margin-right: 10px;"></i> {{ __('client.login_google') }}
    </a>

    @if (Route::has('register'))
    <div class="auth-footer">
        {{ __('client.no_account') }} <a href="{{ route('register') }}">{{ __('client.register_now') }}</a>
    </div>
    @endif
</div>
@endsection
