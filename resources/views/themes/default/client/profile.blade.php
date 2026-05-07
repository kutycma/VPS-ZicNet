@extends('layouts.client')
@section('title', __('client.profile'))
@section('page_title', __('client.profile'))
@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="card" style="border-radius:16px;">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-user mr-2"></i>{{ __('client.personal_info') }}</h3></div>
            <form action="{{ route('client.profile.update') }}" method="POST">@csrf @method('PUT')
            <div class="card-body">
                <div class="form-group"><label>{{ __('client.full_name') }}</label><input type="text" name="name" class="form-control" value="{{ $user->name }}" required></div>
                <div class="form-group">
                    <label>{{ __('client.email') }}</label>
                    <div class="input-group">
                        <input type="email" name="email" class="form-control" value="{{ $user->email }}" required>
                        <div class="input-group-append">
                            @if($user->hasVerifiedEmail())
                                <span class="input-group-text bg-success text-white border-0" title="{{ __('client.verified') }}"><i class="fas fa-check-circle mr-1"></i> {{ __('client.verified') }}</span>
                            @else
                                <span class="input-group-text bg-warning text-dark border-0" title="{{ __('client.not_verified') }}"><i class="fas fa-exclamation-triangle mr-1"></i> {{ __('client.not_verified') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="form-group"><label>{{ __('client.phone') }}</label><input type="text" name="phone" class="form-control" value="{{ $user->phone }}"></div>
            </div>
            <div class="card-footer"><button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>{{ __('client.update') }}</button></div>
            </form>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card" style="border-radius:16px;">
            <div class="card-header"><h3 class="card-title"><i class="fas fa-lock mr-2"></i>{{ __('client.change_password') }}</h3></div>
            <form action="{{ route('client.profile.password') }}" method="POST">@csrf
            <div class="card-body">
                <div class="form-group"><label>{{ __('client.current_password') }}</label><input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" required>@error('current_password')<span class="invalid-feedback">{{ $message }}</span>@enderror</div>
                <div class="form-group"><label>{{ __('client.new_password') }}</label><input type="password" name="password" class="form-control" required minlength="8"></div>
                <div class="form-group"><label>{{ __('client.confirm_password') }}</label><input type="password" name="password_confirmation" class="form-control" required></div>
            </div>
            <div class="card-footer"><button type="submit" class="btn btn-warning"><i class="fas fa-key mr-1"></i>{{ __('client.change_password') }}</button></div>
            </form>
        </div>
    </div>
</div>
<div class="row mt-3">
    <div class="col-md-12">
        <div class="card" style="border-radius:16px;">
            <div class="card-header"><h3 class="card-title"><i class="fab fa-telegram text-primary mr-2"></i>{{ __('client.telegram_link') }}</h3></div>
            <div class="card-body">
                @if($user->telegram_id)
                    <div class="alert alert-success" style="border-radius:10px;">
                        <i class="fas fa-check-circle mr-2"></i> {{ __('client.telegram_linked', ['id' => $user->telegram_id]) }}
                    </div>
                    <form action="{{ route('client.profile.unlink-telegram') }}" method="POST" onsubmit="return confirm('{{ __('client.confirm_unlink_telegram') }}');">
                        @csrf
                        <button type="submit" class="btn btn-danger"><i class="fas fa-unlink mr-1"></i>{{ __('client.unlink_telegram') }}</button>
                    </form>
                @else
                    @php
                        $telegramBotUsername = \App\Models\Setting::get('telegram_bot_username', 'mua4g_bot');
                    @endphp
                    <div class="alert alert-warning" style="border-radius:10px;">
                        <i class="fas fa-exclamation-triangle mr-2"></i> {{ __('client.telegram_not_linked') }}
                    </div>
                    <p>{{ __('client.telegram_guide') }}</p>
                    
                    <strong>{{ __('client.telegram_method_1') }}</strong>
                    <div class="mb-3 mt-2">
                        <a href="https://t.me/{{ $telegramBotUsername }}?start={{ $user->telegram_verify_token }}" target="_blank" class="btn btn-primary" style="border-radius:20px;">
                            <i class="fab fa-telegram-plane mr-1"></i> {{ __('client.open_bot') }} {{ '@' }}{{ $telegramBotUsername }}
                        </a>
                    </div>
                    
                    <strong>{{ __('client.telegram_method_2') }}</strong>
                    <p>{{ __('client.telegram_manual_guide', ['bot' => '<a href="https://t.me/'.$telegramBotUsername.'" target="_blank"><strong>@'.$telegramBotUsername.'</strong></a>']) }}</p>
                    <div class="input-group mb-3" style="max-width: 400px;">
                        <input type="text" class="form-control font-weight-bold text-primary" value="/start {{ $user->telegram_verify_token }}" readonly id="telegramCode">
                        <div class="input-group-append">
                            <button class="btn btn-outline-primary" type="button" onclick="copyTelegram()">{{ __('client.copy_command') }}</button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
    function copyTelegram() {
        var copyText = document.getElementById("telegramCode");
        copyText.select();
        copyText.setSelectionRange(0, 99999);
        document.execCommand("copy");
        alert("{{ __('client.copied') }}" + copyText.value);
    }
</script>
@endsection
