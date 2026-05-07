<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'VPS ZicNet') }} - VPS High-End</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        :root {
            --bg-color: var(--theme-welcome-bg, #0f172a);
            --text-main: var(--theme-text-light, #f8fafc);
            --text-muted: var(--theme-text-muted, #94a3b8);
            --accent-1: var(--theme-primary, #6366f1);
            --accent-2: var(--theme-secondary, #8b5cf6);
            --card-bg: var(--theme-welcome-card-bg, rgba(30, 41, 59, 0.7));
            --card-border: var(--theme-welcome-card-border, rgba(255, 255, 255, 0.1));
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: var(--theme-font-family, 'Inter', sans-serif); background: var(--bg-color); color: var(--text-main); line-height: 1.6; overflow-x: hidden; }
        a { text-decoration: none; color: inherit; }
        
        /* Navbar */
        nav { display: flex; justify-content: space-between; align-items: center; padding: 1.5rem 5%; position: absolute; width: 100%; top: 0; z-index: 10; }
        .logo { font-size: 1.5rem; font-weight: 800; background: linear-gradient(135deg, var(--accent-1), var(--accent-2)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .nav-links { display: flex; align-items: center; gap: 0.5rem; }
        .nav-links a { font-weight: 600; color: var(--text-main); transition: 0.3s; padding: 0.5rem 1rem; border-radius: 8px; }
        .nav-links a:hover { background: rgba(255,255,255,0.1); }
        .btn-primary { background: linear-gradient(135deg, var(--accent-1), var(--accent-2)); color: #fff !important; padding: 0.6rem 1.5rem; border-radius: 8px; font-weight: 600; border: none; cursor: pointer; transition: 0.3s; display: inline-block; }
        .btn-primary:hover { box-shadow: 0 10px 25px rgba(99, 102, 241, 0.4); transform: translateY(-2px); }

        /* Lang Switcher Dropdown */
        .lang-dropdown { position: relative; display: flex; align-items: center; margin-left: 8px; }
        .lang-btn { display: flex; align-items: center; justify-content: center; gap: 8px; font-weight: 600; color: var(--text-main); background: rgba(255,255,255,0.1); padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer; transition: 0.3s; border: 1px solid var(--card-border); }
        .lang-btn:hover { background: rgba(255,255,255,0.2); }
        .lang-menu { position: absolute; right: 0; top: calc(100% + 10px); background: var(--card-bg); border: 1px solid var(--card-border); border-radius: 12px; min-width: 150px; opacity: 0; visibility: hidden; transform: translateY(10px); transition: 0.3s; box-shadow: 0 10px 25px rgba(0,0,0,0.3); backdrop-filter: blur(10px); z-index: 100; overflow: hidden; }
        .lang-dropdown:hover .lang-menu { opacity: 1; visibility: visible; transform: translateY(0); }
        .lang-item { display: flex; align-items: center; gap: 10px; padding: 10px 15px; color: var(--text-main); transition: 0.2s; font-weight: 500; }
        .lang-item:hover { background: rgba(255,255,255,0.1); color: var(--accent-1); }
        .lang-item.active { background: rgba(99,102,241,0.2); color: var(--accent-1); }

        /* Hero */
        .hero { position: relative; padding: 12rem 5% 6rem; text-align: center; }
        .hero::before { content: ''; position: absolute; width: 600px; height: 600px; background: var(--theme-welcome-hero-glow, var(--accent-1)); border-radius: 50%; filter: blur(150px); opacity: 0.2; top: -100px; left: 50%; transform: translateX(-50%); z-index: -1; }
        .hero h1 { font-size: 3.5rem; font-weight: 800; margin-bottom: 1.5rem; line-height: 1.2; text-shadow: 0 4px 10px rgba(0,0,0,0.5); }
        .hero p { font-size: 1.2rem; color: var(--text-muted); max-width: 600px; margin: 0 auto 2.5rem; }

        /* Plans */
        .plans-section { padding: 4rem 5%; max-width: 1200px; margin: 0 auto; }
        .plan-category { font-size: 1.8rem; font-weight: 600; margin-bottom: 2rem; margin-top: 3rem; text-align: center; color: var(--text-main); }
        .plans-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2rem; }
        .plan-card { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: 16px; padding: 2.5rem 2rem; text-align: center; backdrop-filter: blur(10px); transition: 0.3s; position: relative; overflow: hidden; display: flex; flex-direction: column; }
        .plan-card:hover { transform: translateY(-10px); border-color: var(--accent-1); box-shadow: 0 15px 30px rgba(0,0,0,0.4), 0 0 20px rgba(99,102,241,0.2); }
        .plan-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: linear-gradient(90deg, var(--accent-1), var(--accent-2)); opacity: 0; transition: 0.3s; }
        .plan-card:hover::before { opacity: 1; }
        .plan-icon { width: 60px; height: 60px; background: linear-gradient(135deg, rgba(99,102,241,0.2), rgba(139,92,246,0.2)); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: var(--accent-1); margin: 0 auto 1.5rem; }
        .plan-name { font-size: 1.4rem; font-weight: 700; margin-bottom: 1rem; color: #fff; }
        .plan-specs { text-align: left; margin: 1.5rem 0 2.5rem; color: var(--text-muted); font-size: 0.95rem; flex-grow: 1; }
        .plan-specs li { display: flex; align-items: center; margin-bottom: 0.8rem; }
        .plan-specs i { color: var(--accent-1); width: 25px; font-size: 0.9rem; }
        .plan-price { font-size: 2.2rem; font-weight: 800; margin-bottom: 0.2rem; background: linear-gradient(135deg, #fff, #cbd5e1); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .plan-period { font-size: 0.85rem; color: var(--text-muted); margin-bottom: 2rem; }
        
        .footer { padding: 3rem 5%; text-align: center; color: var(--text-muted); border-top: 1px solid var(--card-border); margin-top: 5rem; }
        
        @media (max-width: 768px) {
            .hero h1 { font-size: 2.5rem; }
            .nav-links { display: none; }
        }
    </style>
    {{-- Theme CSS (injected LAST to override all base styles) --}}
    {!! $themeCss ?? '' !!}
</head>
<body>
    <nav>
        <div class="logo"><a href="/"><i class="fas fa-cloud"></i> ZicNet</a></div>
        <div class="nav-links">
            <a href="#plans">{{ __('client.pricing') }}</a>
            @auth
                <a href="{{ route('client.dashboard') }}">{{ __('client.dashboard') }}</a>
                <a href="#" onclick="event.preventDefault(); document.getElementById('logout').submit();">{{ __('client.logout') }} ({{ Auth::user()->name }})</a>
                <form id="logout" action="{{ route('logout') }}" method="POST" style="display:none;">@csrf</form>
            @else
                <a href="{{ route('login') }}">{{ __('client.login') }}</a>
                <a href="{{ route('register') }}" class="btn-primary">{{ __('client.register_now') }}</a>
            @endauth
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
            <div class="lang-dropdown">
                <div class="lang-btn">
                    <span style="font-size: 1.1em;">{{ $locales[$currentLocale]['flag'] }}</span> {{ $locales[$currentLocale]['short'] }} <i class="fas fa-chevron-down" style="font-size: 0.8em; margin-left: 2px;"></i>
                </div>
                <div class="lang-menu">
                    @foreach($locales as $code => $details)
                    <a href="?lang={{ $code }}" class="lang-item {{ $currentLocale === $code ? 'active' : '' }}">
                        <span style="display:inline-block; width: 25px; font-size: 1.1em;">{{ $details['flag'] }}</span> {{ $details['name'] }}
                    </a>
                    @endforeach
                </div>
            </div>
        </div>
    </nav>

    <div class="hero">
        <h1>{!! __('client.hero_title') !!}</h1>
        <p>{{ __('client.hero_subtitle') }}</p>
        <a href="#plans" class="btn-primary" style="font-size: 1.1rem; padding: 1rem 2.5rem; border-radius: 30px;">
            <i class="fas fa-rocket" style="margin-right: 8px;"></i> {{ __('client.explore_now') }}
        </a>
    </div>

    <div class="plans-section" id="plans">
        {{-- Tab Navigation --}}
        @if(isset($groups) && $groups->isNotEmpty())
        <div style="display:flex;justify-content:center;flex-wrap:wrap;gap:12px;margin-bottom:3rem;">
            <a href="{{ route('home') }}#plans"
               class="btn-primary" style="padding:10px 24px;border-radius:30px;font-weight:600;font-size:0.95rem;text-decoration:none;transition:all 0.3s;{{ !$selectedGroup ? 'box-shadow:0 6px 20px rgba(99,102,241,0.4);' : 'background:transparent;border:1px solid var(--card-border);color:var(--text-muted);' }}">
                <i class="fas fa-th-large" style="margin-right:6px;"></i> Tất cả
            </a>
            @foreach($groups as $group)
            <a href="{{ route('home', ['group' => $group->slug]) }}#plans"
               class="btn-primary" style="padding:10px 24px;border-radius:30px;font-weight:600;font-size:0.95rem;text-decoration:none;transition:all 0.3s;{{ ($selectedGroup ?? '') === $group->slug ? 'box-shadow:0 6px 20px rgba(99,102,241,0.4);' : 'background:transparent;border:1px solid var(--card-border);color:var(--text-muted);' }}">
                <i class="{{ $group->icon }}" style="margin-right:6px;"></i> {{ $group->name }}
            </a>
            @endforeach
        </div>
        @endif

        @forelse($plans as $type => $typePlans)
            <div class="plan-category">
                <i class="fas fa-{{ $type === 'vps_vn' ? 'flag' : 'globe' }}" style="color: var(--accent-1); margin-right: 10px;"></i>
                {{ $type === 'vps_vn' ? __('client.vps_vietnam_heading') : __('client.vps_international_heading') }}
            </div>
            <div class="plans-grid">
                @foreach($typePlans as $plan)
                <div class="plan-card">
                    <div class="plan-icon"><i class="fas fa-server"></i></div>
                    <div class="plan-name">{{ $plan->name }}</div>
                    
                    <div class="plan-price">{{ number_format($plan->display_price, 0, ',', '.') }}đ</div>
                    <div class="plan-period">{{ $plan->display_price_cycle }}</div>

                    <ul class="plan-specs" style="list-style: none;">
                        <li><i class="fas fa-microchip"></i> <span><strong>{{ $plan->cpu_cores }}</strong> vCPU Core</span></li>
                        <li><i class="fas fa-memory"></i> <span><strong>{{ $plan->ram_gb }} GB</strong> RAM</span></li>
                        <li><i class="fas fa-hdd"></i> <span><strong>{{ $plan->disk_gb }} GB</strong> SSD NVMe</span></li>
                        <li><i class="fas fa-network-wired"></i> <span>Bandwidth: <strong>{{ $plan->bandwidth_mbps ? $plan->bandwidth_mbps . ' Mbps' : 'Unlimited' }}</strong></span></li>
                    </ul>
                    
                    <a href="{{ route('client.orders.create', $plan) }}" class="btn-primary" style="width: 100%;">
                        <i class="fas fa-shopping-cart" style="margin-right: 5px;"></i> {{ __('client.buy_now') }}
                    </a>
                </div>
                @endforeach
            </div>
        @empty
            <div style="text-align: center; color: var(--text-muted); padding: 3rem;">
                <i class="fas fa-box-open fa-3x" style="margin-bottom: 1rem;"></i>
                <p>{{ __('client.updating_prices') }}</p>
            </div>
        @endforelse
    </div>

    <div class="footer">
        &copy; {{ date('Y') }} VPS ZicNet. {{ __('client.all_rights_reserved') }} <br>
        <small style="opacity: 0.5; margin-top: 10px; display: block;">{{ __('client.auto_vps_system') }}</small>
    </div>

    @include('components.telegram-bubble')
</body>
</html>
