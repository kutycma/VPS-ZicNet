@extends('layouts.auth')
@section('title', __('client.register_title'))

@section('content')
<div class="auth-card">
    <div class="auth-logo">
        <div class="logo-icon"><i class="fas fa-cloud"></i></div>
        <h2>VPS ZicNet</h2>
        <p>{{ __('client.register_subtitle') }}</p>
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf
        <div class="form-group">
            <label for="name"><i class="fas fa-user mr-1"></i> {{ __('client.full_name') }}</label>
            <input id="name" type="text" class="form-control @error('name') is-invalid @enderror"
                   name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>
            @error('name')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label for="email"><i class="fas fa-envelope mr-1"></i> {{ __('client.email') }}</label>
            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror"
                   name="email" value="{{ old('email') }}" required autocomplete="email"
                   placeholder="your@email.com">
            @error('email')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label for="password"><i class="fas fa-lock mr-1"></i> {{ __('client.password_label') }}</label>
            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror"
                   name="password" required autocomplete="new-password">
            @error('password')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label for="password-confirm"><i class="fas fa-check-double mr-1"></i> {{ __('client.confirm_password') }}</label>
            <input id="password-confirm" type="password" class="form-control"
                   name="password_confirmation" required autocomplete="new-password">
        </div>

        <button type="submit" class="btn-auth">
            <i class="fas fa-user-plus mr-2"></i> {{ __('client.register_button') }}
        </button>
    </form>

    <div style="text-align: center; margin: 25px 0;">
        <span style="background: rgba(255,255,255,0.1); padding: 5px 15px; border-radius: 20px; font-size: 0.85rem; color: #cbd5e1;">{{ __('client.or') }}</span>
    </div>

    <a href="{{ route('auth.google') }}" class="btn-auth" style="background: white; color: #333; text-decoration: none; display: flex; align-items: center; justify-content: center; border: 1px solid #ddd;">
        <i class="fab fa-google" style="color: #EA4335; font-size: 1.2rem; margin-right: 10px;"></i> {{ __('client.login_google') }}
    </a>

    <div class="auth-footer">
        {{ __('client.has_account') }} <a href="{{ route('login') }}">{{ __('client.login_now') }}</a>
    </div>
</div>
@endsection
