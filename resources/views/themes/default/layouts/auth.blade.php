<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Auth') - {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: var(--theme-font-family, 'Inter', sans-serif);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--theme-auth-bg-from, #0f172a) 0%, var(--theme-auth-bg-mid, #1e1b4b) 50%, var(--theme-auth-bg-to, #312e81) 100%);
            position: relative;
            overflow: hidden;
        }
        body::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at 30% 40%, var(--theme-auth-glow-1, rgba(99,102,241,0.15)) 0%, transparent 50%),
                        radial-gradient(circle at 70% 60%, var(--theme-auth-glow-2, rgba(139,92,246,0.1)) 0%, transparent 50%);
            animation: float 15s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            33% { transform: translate(20px, -20px) rotate(2deg); }
            66% { transform: translate(-10px, 15px) rotate(-1deg); }
        }
        .auth-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 440px;
            padding: 20px;
        }
        .auth-card {
            background: var(--theme-auth-card-bg, rgba(255, 255, 255, 0.05));
            backdrop-filter: blur(var(--theme-auth-card-blur, 20px));
            -webkit-backdrop-filter: blur(var(--theme-auth-card-blur, 20px));
            border: 1px solid var(--theme-auth-card-border, rgba(255, 255, 255, 0.1));
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
        }
        .auth-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .auth-logo .logo-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--theme-primary, #6366f1), var(--theme-secondary, #8b5cf6));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.4);
        }
        .auth-logo .logo-icon i { font-size: 2rem; color: var(--theme-text-on-primary, #fff); }
        .auth-logo h2 {
            background: linear-gradient(135deg, var(--theme-brand-gradient-from, #c7d2fe), var(--theme-brand-gradient-to, #e0e7ff));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 800;
            font-size: 1.5rem;
        }
        .auth-logo p { color: var(--theme-text-muted, #94a3b8); font-size: 0.9rem; margin-top: 5px; }
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            color: var(--theme-text-light, #cbd5e1);
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 8px;
        }
        .form-control {
            width: 100%;
            padding: 14px 18px;
            background: var(--theme-auth-input-bg, rgba(255, 255, 255, 0.07));
            border: 1px solid var(--theme-auth-input-border, rgba(255, 255, 255, 0.12));
            border-radius: 12px;
            color: var(--theme-text-light, #e2e8f0);
            font-size: 0.95rem;
            font-family: var(--theme-font-family, 'Inter', sans-serif);
            transition: all 0.3s;
            outline: none;
        }
        .form-control:focus {
            border-color: var(--theme-primary, #6366f1);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
            background: rgba(255, 255, 255, 0.1);
        }
        .form-control::placeholder { color: #64748b; }
        .form-control.is-invalid { border-color: var(--theme-danger, #ef4444); }
        .invalid-feedback { color: #f87171; font-size: 0.8rem; margin-top: 5px; display: block; }
        .btn-auth {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--theme-primary, #6366f1), var(--theme-secondary, #8b5cf6));
            color: var(--theme-text-on-primary, #fff);
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            font-family: var(--theme-font-family, 'Inter', sans-serif);
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-auth:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.5);
        }
        .auth-footer {
            text-align: center;
            margin-top: 24px;
            color: var(--theme-text-muted, #94a3b8);
            font-size: 0.9rem;
        }
        .auth-footer a { color: var(--theme-primary-light, #818cf8); text-decoration: none; font-weight: 600; }
        .auth-footer a:hover { color: #a5b4fc; text-decoration: underline; }
        .form-check { display: flex; align-items: center; gap: 8px; margin-bottom: 20px; }
        .form-check input[type="checkbox"] { width: 18px; height: 18px; accent-color: var(--theme-primary, #6366f1); }
        .form-check label { color: var(--theme-text-muted, #94a3b8); font-size: 0.85rem; cursor: pointer; margin: 0; }
        .forgot-link { color: var(--theme-primary-light, #818cf8); font-size: 0.85rem; text-decoration: none; }
        .forgot-link:hover { text-decoration: underline; }
    </style>
    {{-- Active theme CSS, followed by the Liquid Glass skin --}}
    {!! $themeCss ?? '' !!}
    <link rel="stylesheet" href="{{ asset('css/liquid-glass.css') }}">
</head>
<body class="liquid-glass-auth">
    <div class="auth-container">
        @yield('content')
    </div>
</body>
</html>
