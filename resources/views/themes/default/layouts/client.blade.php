<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('client.dashboard')) - {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">

    <style>
        body { font-family: var(--theme-font-family, 'Inter', sans-serif); }
        .main-sidebar { background: linear-gradient(180deg, var(--theme-sidebar-bg-from, #0f172a) 0%, var(--theme-sidebar-bg-to, #1e1b4b) 100%) !important; }
        .nav-sidebar .nav-link { color: var(--theme-sidebar-text, #a5b4fc) !important; border-radius: 8px; margin: 2px 8px; transition: all 0.2s; }
        .nav-sidebar .nav-link:hover { background: var(--theme-sidebar-hover-bg, rgba(129, 140, 248, 0.15)) !important; color: var(--theme-sidebar-hover-text, #e0e7ff) !important; }
        .nav-sidebar .nav-link.active { background: linear-gradient(135deg, var(--theme-sidebar-active-from, #6366f1), var(--theme-sidebar-active-to, #8b5cf6)) !important; color: var(--theme-text-on-primary, #fff) !important; box-shadow: 0 4px 15px var(--theme-sidebar-active-shadow, rgba(99, 102, 241, 0.4)); }
        .nav-sidebar .nav-header { color: var(--theme-sidebar-header-color, #6366f1) !important; font-size: 0.7rem; letter-spacing: 1px; }
        .brand-link { border-bottom: 1px solid var(--theme-sidebar-border, rgba(255,255,255,0.08)) !important; padding: 15px 20px !important; }
        .brand-link .brand-text { font-weight: 700; background: linear-gradient(135deg, var(--theme-brand-gradient-from, #818cf8), var(--theme-brand-gradient-to, #c084fc)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .content-wrapper { background: var(--theme-content-bg, #f8fafc) !important; }
        .card { border: var(--theme-card-border, none); border-radius: var(--theme-card-radius, 12px); box-shadow: var(--theme-card-shadow, 0 1px 3px rgba(0,0,0,0.06)); }
        .btn-primary { background: linear-gradient(135deg, var(--theme-primary, #6366f1), var(--theme-secondary, #8b5cf6)); border: none; border-radius: 8px; }
        .btn-primary:hover { background: linear-gradient(135deg, var(--theme-primary-dark, #4f46e5), var(--theme-secondary-dark, #7c3aed)); transform: translateY(-1px); box-shadow: 0 4px 12px var(--theme-sidebar-active-shadow, rgba(99,102,241,0.4)); }
        .btn-success { background: linear-gradient(135deg, var(--theme-success, #10b981), var(--theme-success-dark, #059669)); border: none; border-radius: 8px; }
        .btn-danger { background: linear-gradient(135deg, var(--theme-danger, #ef4444), var(--theme-danger-dark, #dc2626)); border: none; border-radius: 8px; }
        .main-header .navbar { background: var(--theme-header-bg, #fff) !important; box-shadow: none !important; border-bottom: 1px solid var(--theme-header-border, #e2e8f0); }
        .balance-badge { background: linear-gradient(135deg, var(--theme-balance-bg-from, #f59e0b), var(--theme-balance-bg-to, #d97706)); color: #fff; padding: 6px 16px; border-radius: 20px; font-weight: 600; font-size: 0.9rem; }
        .table th { font-weight: 600; color: var(--theme-text-body, #475569); font-size: 0.8rem; text-transform: uppercase; }
        .badge { font-weight: 500; padding: 5px 10px; border-radius: 6px; }
        .main-footer { border-top: 1px solid var(--theme-footer-border, #e2e8f0); }
        .main-footer a { color: var(--theme-footer-link-color, #6366f1); }
        .stat-card { background: linear-gradient(135deg, var(--bg1), var(--bg2)); color: #fff; border-radius: 16px; padding: 24px; position: relative; overflow: hidden; }
        .stat-card::after { content: ''; position: absolute; right: -20px; top: -20px; width: 100px; height: 100px; background: rgba(255,255,255,0.1); border-radius: 50%; }
        .stat-card .stat-icon { font-size: 2.5rem; opacity: 0.3; position: absolute; right: 20px; bottom: 15px; }
        .user-avatar-nav { width:30px; height:30px; border-radius:50%; object-fit:cover; border:2px solid var(--theme-primary, #6366f1); }
        .user-avatar-dropdown { width:40px; height:40px; border-radius:50%; object-fit:cover; flex-shrink:0; }
        .user-avatar-sidebar { width:56px; height:56px; border-radius:50%; object-fit:cover; border:3px solid var(--theme-primary, #6366f1); margin-bottom:8px; }
        .user-panel-text { transition: opacity 0.3s; }
        .sidebar-collapse .user-avatar-sidebar { width:32px; height:32px; border-width:2px; margin-bottom:0; }
        .sidebar-collapse .user-panel-text { display:none; }
        .sidebar-collapse .user-panel-sidebar { padding:10px 5px !important; }
        .sidebar-collapse .main-sidebar:hover .user-avatar-sidebar { width:56px; height:56px; border-width:3px; margin-bottom:8px; }
        .sidebar-collapse .main-sidebar:hover .user-panel-text { display:block; }
        .sidebar-collapse .main-sidebar:hover .user-panel-sidebar { padding:15px 10px !important; }
        @media (max-width: 576px) {
            .user-avatar-nav { width:28px; height:28px; }
        }
    </style>
    @stack('styles')
    {{-- Active theme CSS, followed by the Liquid Glass skin --}}
    {!! $themeCss ?? '' !!}
    <link rel="stylesheet" href="{{ asset('css/liquid-glass.css') }}?v={{ filemtime(public_path('css/liquid-glass.css')) }}">
</head>
<body class="hold-transition sidebar-mini layout-fixed liquid-glass-app liquid-glass-client">
<div class="wrapper">
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a></li>
        </ul>
        <ul class="navbar-nav ml-auto">
            {{-- Language Switcher --}}
            <li class="nav-item dropdown d-flex align-items-center mr-2">
                @php
                    $locales = [
                        'vi' => ['name' => 'Tiếng Việt', 'flag' => '🇻🇳', 'short' => 'VI'],
                        'en' => ['name' => 'English', 'flag' => '🇬🇧', 'short' => 'EN'],
                        'zh' => ['name' => '中文', 'flag' => '🇨🇳', 'short' => '中']
                    ];
                    $currentLocale = app()->getLocale();
                    if (!array_key_exists($currentLocale, $locales)) {
                        $currentLocale = 'vi';
                    }
                @endphp
                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" data-toggle="dropdown" style="background: rgba(99,102,241,0.1); border-radius: 8px; padding: 4px 10px; font-weight: 600; color: var(--theme-primary, #6366f1); gap: 5px;">
                    <span style="font-size: 1.1em;">{{ $locales[$currentLocale]['flag'] }}</span>
                    <span>{{ $locales[$currentLocale]['short'] }}</span>
                </a>
                <div class="dropdown-menu dropdown-menu-right" style="border-radius: 12px; border: none; box-shadow: 0 5px 15px rgba(0,0,0,0.1); padding: 8px;">
                    @foreach($locales as $code => $details)
                    <a href="?lang={{ $code }}" class="dropdown-item" style="border-radius: 6px; padding: 8px 15px; font-weight: 500; color: #475569; {{ $currentLocale === $code ? 'background: rgba(99,102,241,0.1); color: var(--theme-primary, #6366f1);' : '' }}">
                        <span style="display:inline-block; width: 25px; font-size: 1.1em;">{{ $details['flag'] }}</span> {{ $details['name'] }}
                    </a>
                    @endforeach
                </div>
            </li>

            @if(Auth::check())
            <li class="nav-item d-flex align-items-center mr-1">
                <a href="{{ route('client.billing.index') }}" class="btn btn-sm mr-2 d-flex align-items-center" style="background:linear-gradient(135deg, var(--theme-balance-bg-from, #f59e0b), var(--theme-balance-bg-to, #d97706));color:#fff;border-radius:20px;padding:4px 12px;font-weight:600;font-size:0.85rem;box-shadow: 0 2px 5px rgba(245, 158, 11, 0.4);white-space:nowrap;">
                    <i class="fas fa-plus-circle"></i><span class="d-none d-sm-inline ml-1">{{ __('client.top_up') }}</span>
                </a>
                <span class="balance-badge d-flex align-items-center" style="padding:4px 12px;white-space:nowrap;">
                    <i class="fas fa-wallet mr-1"></i> {{ number_format(Auth::user()->balance ?? 0, 0, ',', '.') }}đ
                </span>
            </li>
            @endif
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" data-toggle="dropdown" style="padding-right:0; gap:8px;">
                    @auth
                    <img src="{{ Auth::user()->getAvatarUrl(64) }}" alt="avatar" class="user-avatar-nav">
                    @else
                    <i class="fas fa-user-circle" style="font-size:1.4rem;"></i>
                    @endauth
                    <span class="d-none d-sm-inline" style="font-weight:600; color:var(--theme-text-heading, #1e293b);">{{ Auth::check() ? Auth::user()->name : __('client.guest') }}</span>
                </a>
                <div class="dropdown-menu dropdown-menu-right" style="border-radius:12px; border:none; box-shadow: 0 8px 25px rgba(0,0,0,0.12); padding:8px; min-width:220px;">
                    @auth
                    <div style="padding:12px 16px; display:flex; align-items:center; gap:12px; border-bottom:1px solid #f1f5f9; margin-bottom:6px;">
                        <img src="{{ Auth::user()->getAvatarUrl(64) }}" alt="avatar" class="user-avatar-dropdown">
                        <div>
                            <div style="font-weight:700; color:#1e293b; font-size:0.95rem;">{{ Auth::user()->name }}</div>
                            <div style="font-size:0.78rem; color:#94a3b8;">{{ Auth::user()->email }}</div>
                        </div>
                    </div>
                    <a class="dropdown-item" href="{{ route('client.profile') }}" style="border-radius:8px; padding:8px 14px;"><i class="fas fa-user mr-2" style="width:18px; color:#6366f1;"></i> {{ __('client.profile') }}</a>
                    <a class="dropdown-item" href="{{ route('client.billing.index') }}" style="border-radius:8px; padding:8px 14px;"><i class="fas fa-wallet mr-2" style="width:18px; color:#f59e0b;"></i> {{ __('client.top_up') }}</a>
                    <div class="dropdown-divider" style="margin:6px 0;"></div>
                    <a class="dropdown-item" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" style="border-radius:8px; padding:8px 14px; color:#ef4444;">
                        <i class="fas fa-sign-out-alt mr-2" style="width:18px;"></i> {{ __('client.logout') }}
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">@csrf</form>
                    @else
                    <a class="dropdown-item" href="{{ route('login') }}" style="border-radius:8px; padding:8px 14px;"><i class="fas fa-sign-in-alt mr-2" style="width:18px;"></i> {{ __('client.login') }}</a>
                    <a class="dropdown-item" href="{{ route('register') }}" style="border-radius:8px; padding:8px 14px;"><i class="fas fa-user-plus mr-2" style="width:18px;"></i> {{ __('client.register') }}</a>
                    @endauth
                </div>
            </li>
        </ul>
    </nav>

    <aside class="main-sidebar elevation-4">
        <a href="{{ route('client.dashboard') }}" class="brand-link">
            <i class="fas fa-cloud brand-image" style="font-size:1.5rem; color:var(--theme-primary-light, #818cf8); margin: 0 10px;"></i>
            <span class="brand-text">VPS ZicNet</span>
        </a>
        <div class="sidebar">
            @auth
            <div class="user-panel-sidebar" style="padding:15px 10px; text-align:center; border-bottom:1px solid rgba(0,0,0,0.06);">
                <img src="{{ Auth::user()->getAvatarUrl(128) }}" alt="avatar" class="user-avatar-sidebar">
                <div class="user-panel-text">
                    <div style="color:#1e293b; font-weight:700; font-size:1rem;">{{ Auth::user()->name }}</div>
                    <div style="color:#6366f1; font-weight:600; font-size:0.8rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ Auth::user()->email }}</div>
                </div>
            </div>
            @endauth
            <nav class="mt-3">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                    @auth
                    <li class="nav-item">
                        <a href="{{ route('client.dashboard') }}" class="nav-link {{ request()->routeIs('client.dashboard') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-tachometer-alt"></i><p>{{ __('client.dashboard') }}</p>
                        </a>
                    </li>
                    @endauth

                    <li class="nav-header">{{ __('client.services') }}</li>
                    <li class="nav-item">
                        <a href="{{ route('client.orders.plans') }}" class="nav-link {{ request()->routeIs('client.orders.plans') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-shopping-cart"></i><p>{{ __('client.buy_vps') }}</p>
                        </a>
                    </li>

                    @auth
                    <li class="nav-item">
                        <a href="{{ route('client.vps.index') }}" class="nav-link {{ request()->routeIs('client.vps.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-hdd"></i><p>{{ __('client.my_vps') }}</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('client.orders.history') }}" class="nav-link {{ request()->routeIs('client.orders.history') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-receipt"></i><p>{{ __('client.order_history') }}</p>
                        </a>
                    </li>

                    <li class="nav-header">{{ __('client.account') }}</li>
                    <li class="nav-item">
                        <a href="{{ route('client.profile') }}" class="nav-link {{ request()->routeIs('client.profile') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-user-cog"></i><p>{{ __('client.profile') }}</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('client.billing.index') }}" class="nav-link {{ request()->routeIs('client.billing.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-wallet"></i><p>{{ __('client.billing') }}</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('client.tickets.index') }}" class="nav-link {{ request()->routeIs('client.tickets.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-life-ring"></i><p>{{ __('client.support') }}</p>
                        </a>
                    </li>

                    @if(Auth::user()->isAdmin())
                    <li class="nav-header">{{ __('client.admin_section') }}</li>
                    <li class="nav-item">
                        <a href="{{ route('admin.dashboard') }}" class="nav-link" style="background:linear-gradient(135deg, var(--theme-danger, #ef4444), var(--theme-danger-dark, #dc2626)) !important;color:#fff !important;margin-top:5px;">
                            <i class="nav-icon fas fa-shield-alt"></i><p>{{ __('client.admin_panel') }}</p>
                        </a>
                    </li>
                    @endif
                    @endauth
                </ul>
            </nav>
        </div>
    </aside>

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0" style="font-weight:700; color:var(--theme-text-heading, #1e293b);">@yield('page_title', __('client.dashboard'))</h1>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                @if(auth()->check() && !auth()->user()->hasVerifiedEmail())
                    <div class="alert alert-warning" style="border-radius:10px;">
                        <i class="fas fa-exclamation-triangle mr-2"></i> {{ __('client.email_not_verified') }}
                        <form class="d-inline" method="POST" action="{{ route('verification.resend') }}">
                            @csrf
                            <button type="submit" class="btn btn-link p-0 m-0 align-baseline text-dark font-weight-bold" style="text-decoration: underline;">{{ __('client.click_resend') }}</button>.
                        </form>
                    </div>
                @endif
                
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" style="border-radius:10px; border:none; background:linear-gradient(135deg, var(--theme-success, #10b981), var(--theme-success-dark, #059669)); color:#fff;">
                        <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert"><span style="color:#fff">&times;</span></button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" style="border-radius:10px; border:none;">
                        <i class="fas fa-exclamation-circle mr-2"></i> {{ session('error') }}
                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    </div>
                @endif
                @yield('content')
            </div>
        </section>
    </div>

    <footer class="main-footer">
        <strong>&copy; {{ date('Y') }} <a href="https://vps.zicnet.vn">VPS ZicNet</a>.</strong>
    </footer>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="{{ asset('js/liquid-glass-focus.js') }}?v={{ filemtime(public_path('js/liquid-glass-focus.js')) }}"></script>
<script>
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
    
    @if(session('resent'))
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            icon: 'success',
            title: '{{ __("client.verify_sent") }}',
            text: '{{ __("client.verify_sent_text") }}',
            confirmButtonColor: 'var(--theme-primary, #6366f1)',
            confirmButtonText: '{{ __("client.understood") }}'
        });
    });
    @endif
</script>
@stack('scripts')

@include('components.telegram-bubble')
</body>
</html>
