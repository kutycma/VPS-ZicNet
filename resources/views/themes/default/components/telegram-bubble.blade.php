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
        background: linear-gradient(135deg, rgba(0, 122, 255, 0.92), rgba(90, 200, 250, 0.86), rgba(175, 82, 222, 0.82));
        color: white;
        border-radius: 50%;
        border: 1px solid rgba(255, 255, 255, 0.45);
        display: flex;
        justify-content: center;
        align-items: center;
        font-size: 32px;
        text-decoration: none;
        box-shadow: 0 18px 38px rgba(0, 122, 255, 0.28), inset 0 1px 0 rgba(255, 255, 255, 0.35);
        backdrop-filter: blur(18px) saturate(170%);
        -webkit-backdrop-filter: blur(18px) saturate(170%);
        z-index: 9999;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        animation: pulse-tele 2s infinite;
    }
    .telegram-bubble:hover {
        transform: translateY(-5px);
        box-shadow: 0 22px 46px rgba(0, 122, 255, 0.34), inset 0 1px 0 rgba(255, 255, 255, 0.45);
        color: white;
    }
    .telegram-bubble i {
        margin-right: 3px; /* slight optical centering adjustment */
        margin-top: 2px;
    }
    @keyframes pulse-tele {
        0% { box-shadow: 0 18px 38px rgba(0, 122, 255, 0.28), 0 0 0 0 rgba(90, 200, 250, 0.32), inset 0 1px 0 rgba(255, 255, 255, 0.35); }
        70% { box-shadow: 0 18px 38px rgba(0, 122, 255, 0.28), 0 0 0 14px rgba(90, 200, 250, 0), inset 0 1px 0 rgba(255, 255, 255, 0.35); }
        100% { box-shadow: 0 18px 38px rgba(0, 122, 255, 0.28), 0 0 0 0 rgba(90, 200, 250, 0), inset 0 1px 0 rgba(255, 255, 255, 0.35); }
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
