@php
    $telegramUrl = \App\Models\Setting::get('contact_telegram_url');
@endphp

@if($telegramUrl)
<a href="{{ $telegramUrl }}" target="_blank" class="telegram-bubble" title="Chat với hỗ trợ viên qua Telegram">
    <i class="fab fa-telegram-plane"></i>
</a>

<style>
    .telegram-bubble {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #0088cc, #00aaff);
        color: white;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 32px;
        text-decoration: none;
        box-shadow: 0 4px 15px rgba(0, 136, 204, 0.4);
        z-index: 9999;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        animation: pulse-tele 2s infinite;
    }
    .telegram-bubble:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 20px rgba(0, 136, 204, 0.6);
        color: white;
    }
    .telegram-bubble i {
        margin-right: 3px; /* slight optical centering adjustment */
        margin-top: 2px;
    }
    @keyframes pulse-tele {
        0% { box-shadow: 0 0 0 0 rgba(0, 136, 204, 0.5); }
        70% { box-shadow: 0 0 0 15px rgba(0, 136, 204, 0); }
        100% { box-shadow: 0 0 0 0 rgba(0, 136, 204, 0); }
    }
    @media (max-width: 768px) {
        .telegram-bubble {
            bottom: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            font-size: 26px;
        }
    }
</style>
@endif
