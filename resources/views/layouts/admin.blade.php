<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') - {{ config('app.name') }}</title>

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- AdminLTE 3 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">

    <style>
        body { font-family: var(--theme-font-family, 'Inter', sans-serif); }
        .main-sidebar { background: linear-gradient(180deg, var(--theme-sidebar-bg-from, #1e293b) 0%, var(--theme-sidebar-bg-to, #0f172a) 100%) !important; }
        .nav-sidebar .nav-link { color: var(--theme-sidebar-text, #475569) !important; border-radius: 8px; margin: 2px 8px; }
        .nav-sidebar .nav-link:hover { background: var(--theme-sidebar-hover-bg, rgba(99, 102, 241, 0.15)) !important; color: var(--theme-sidebar-hover-text, #e2e8f0) !important; }
        .nav-sidebar .nav-link.active { background: linear-gradient(135deg, var(--theme-sidebar-active-from, #6366f1), var(--theme-sidebar-active-to, #8b5cf6)) !important; color: var(--theme-text-on-primary, #fff) !important; box-shadow: 0 4px 15px var(--theme-sidebar-active-shadow, rgba(99, 102, 241, 0.4)); }
        .nav-sidebar .nav-header { color: var(--theme-sidebar-header-color, #64748b) !important; font-size: 0.7rem; letter-spacing: 1px; }
        .brand-link { border-bottom: 1px solid var(--theme-sidebar-border, rgba(255,255,255,0.1)) !important; padding: 15px 20px !important; }
        .brand-link .brand-text { font-weight: 700; background: linear-gradient(135deg, var(--theme-brand-gradient-from, #6366f1), var(--theme-brand-gradient-to, #a78bfa)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .content-wrapper { background: var(--theme-content-bg, #f1f5f9) !important; }
        .card { border: var(--theme-card-border, none); border-radius: var(--theme-card-radius, 12px); box-shadow: var(--theme-card-shadow, 0 1px 3px rgba(0,0,0,0.08)); transition: all 0.2s; }
        .card:hover { box-shadow: var(--theme-card-hover-shadow, 0 4px 15px rgba(0,0,0,0.1)); }
        .card-header { border-bottom: 1px solid #f1f5f9; }
        .small-box { border-radius: var(--theme-card-radius, 12px); overflow: hidden; }
        .small-box .inner h3 { font-weight: 700; }
        .btn-primary { background: linear-gradient(135deg, var(--theme-primary, #6366f1), var(--theme-secondary, #8b5cf6)); border: none; }
        .btn-primary:hover { background: linear-gradient(135deg, var(--theme-primary-dark, #4f46e5), var(--theme-secondary-dark, #7c3aed)); transform: translateY(-1px); box-shadow: 0 4px 12px var(--theme-sidebar-active-shadow, rgba(99,102,241,0.4)); }
        .table th { font-weight: 600; color: var(--theme-text-body, #475569); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .badge { font-weight: 500; padding: 5px 10px; border-radius: 6px; }
        .main-header { border-bottom: 1px solid var(--theme-header-border, #e2e8f0); box-shadow: none !important; }
        .main-header .navbar { background: var(--theme-header-bg, #fff) !important; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        .stat-card { background: linear-gradient(135deg, var(--bg1), var(--bg2)); color: #fff; border-radius: 16px; padding: 24px; position: relative; overflow: hidden; }
        .stat-card::after { content: ''; position: absolute; right: -20px; top: -20px; width: 100px; height: 100px; background: rgba(255,255,255,0.1); border-radius: 50%; }
        .stat-card .stat-icon { font-size: 2.5rem; opacity: 0.3; position: absolute; right: 20px; bottom: 15px; }
        .main-footer { border-top: 1px solid var(--theme-footer-border, #e2e8f0); }
        .main-footer a { color: var(--theme-footer-link-color, #6366f1); }
        .btn-success { background: linear-gradient(135deg, var(--theme-success, #10b981), var(--theme-success-dark, #059669)); border: none; border-radius: 8px; }
        .btn-danger { background: linear-gradient(135deg, var(--theme-danger, #ef4444), var(--theme-danger-dark, #dc2626)); border: none; border-radius: 8px; }
    </style>
    @stack('styles')
    {{-- Active theme CSS, followed by the Liquid Glass skin --}}
    {!! $themeCss ?? '' !!}
    <link rel="stylesheet" href="{{ asset('css/liquid-glass.css') }}">
</head>
<body class="hold-transition sidebar-mini layout-fixed liquid-glass-app liquid-glass-admin">
<div class="wrapper">
    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a></li>
        </ul>
        <ul class="navbar-nav ml-auto">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" data-toggle="dropdown" style="gap:8px;">
                    <img src="{{ Auth::user()->getAvatarUrl(64) }}" alt="avatar" style="width:30px; height:30px; border-radius:50%; object-fit:cover; border:2px solid #6366f1;">
                    <span>{{ Auth::user()->name }}</span>
                </a>
                <div class="dropdown-menu dropdown-menu-right" style="border-radius:12px; border:none; box-shadow: 0 8px 25px rgba(0,0,0,0.12); padding:8px; min-width:220px;">
                    <div style="padding:10px 14px; display:flex; align-items:center; gap:10px; border-bottom:1px solid #f1f5f9; margin-bottom:6px;">
                        <img src="{{ Auth::user()->getAvatarUrl(64) }}" alt="avatar" style="width:40px; height:40px; border-radius:50%; object-fit:cover;">
                        <div>
                            <div style="font-weight:700; color:#1e293b; font-size:0.9rem;">{{ Auth::user()->name }}</div>
                            <div style="font-size:0.75rem; color:#94a3b8;">Admin</div>
                        </div>
                    </div>
                    <a class="dropdown-item" href="{{ route('admin.settings.index') }}" style="border-radius:8px; padding:8px 14px;"><i class="fas fa-cog mr-2" style="width:18px; color:#6366f1;"></i> Cài đặt</a>
                    <div class="dropdown-divider" style="margin:6px 0;"></div>
                    <a class="dropdown-item" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" style="border-radius:8px; padding:8px 14px; color:#ef4444;">
                        <i class="fas fa-sign-out-alt mr-2" style="width:18px;"></i> Đăng xuất
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">@csrf</form>
                </div>
            </li>
        </ul>
    </nav>

    <!-- Sidebar -->
    <aside class="main-sidebar elevation-4">
        <a href="{{ route('admin.dashboard') }}" class="brand-link">
            <i class="fas fa-cloud brand-image" style="font-size:1.5rem; color:var(--theme-primary, #6366f1); margin: 0 10px;"></i>
            <span class="brand-text font-weight-light">VPS ZicNet</span>
        </a>

        <div class="sidebar">
            <nav class="mt-3">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                    <li class="nav-item">
                        <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-tachometer-alt"></i><p>Dashboard</p>
                        </a>
                    </li>

                    <li class="nav-header">QUẢN LÝ VPS</li>
                    <li class="nav-item">
                        <a href="{{ route('admin.providers.index') }}" class="nav-link {{ request()->routeIs('admin.providers.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-server"></i><p>Nhà cung cấp</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.plan-groups.index') }}" class="nav-link {{ request()->routeIs('admin.plan-groups.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-layer-group"></i><p>Nhóm VPS (Tabs)</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.plans.index') }}" class="nav-link {{ request()->routeIs('admin.plans.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-cubes"></i><p>Gói VPS</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.vps.index') }}" class="nav-link {{ request()->routeIs('admin.vps.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-hdd"></i><p>Quản lý VPS</p>
                        </a>
                    </li>

                    <li class="nav-header">TÀI CHÍNH</li>
                    <li class="nav-item">
                        <a href="{{ route('admin.transactions.index') }}" class="nav-link {{ request()->routeIs('admin.transactions.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-exchange-alt"></i><p>Giao dịch</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.orders.index') }}" class="nav-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-shopping-cart"></i><p>Đơn hàng</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.coupons.index') }}" class="nav-link {{ request()->routeIs('admin.coupons.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-ticket-alt"></i><p>Mã giảm giá</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.payment.index') }}" class="nav-link {{ request()->routeIs('admin.payment.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-credit-card"></i><p>Thanh toán</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.deposits.index') }}" class="nav-link {{ request()->routeIs('admin.deposits.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-money-bill-wave"></i>
                            <p>Quản lý Nạp tiền
                                @php $pendingDeposits = \App\Models\DepositRequest::where('status','pending')->where('expires_at','>=',now())->count(); @endphp
                                @if($pendingDeposits > 0)
                                <span class="badge badge-warning right">{{ $pendingDeposits }}</span>
                                @endif
                            </p>
                        </a>
                    </li>

                    <li class="nav-header">HỖ TRỢ</li>
                    <li class="nav-item">
                        <a href="{{ route('admin.tickets.index') }}" class="nav-link {{ request()->routeIs('admin.tickets.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-life-ring"></i><p>Tickets
                                @php $openTickets = \App\Models\Ticket::where('status','open')->count(); @endphp
                                @if($openTickets > 0)
                                <span class="badge badge-danger right">{{ $openTickets }}</span>
                                @endif
                            </p>
                        </a>
                    </li>

                    <li class="nav-header">HỆ THỐNG</li>
                    <li class="nav-item">
                        <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-users"></i><p>Người dùng</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.themes.index') }}" class="nav-link {{ request()->routeIs('admin.themes.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-palette"></i><p>Giao diện</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.settings.index') }}" class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-cogs"></i><p>Cài đặt</p>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>

    <!-- Content -->
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0" style="font-weight:700; color:var(--theme-text-heading, #1e293b);">@yield('page_title', 'Dashboard')</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            @yield('breadcrumb')
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius:10px; border:none; background:linear-gradient(135deg, var(--theme-success, #10b981), var(--theme-success-dark, #059669)); color:#fff;">
                        <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert"><span style="color:#fff">&times;</span></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius:10px; border:none;">
                        <i class="fas fa-exclamation-circle mr-2"></i> {{ session('error') }}
                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius:10px; border:none;">
                        <ul class="mb-0 pl-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    </div>
                @endif

                @yield('content')
            </div>
        </section>
    </div>

    <footer class="main-footer">
        <strong>&copy; {{ date('Y') }} <a href="https://vps.zicnet.vn">VPS ZicNet</a>.</strong> All rights reserved.
    </footer>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap 4 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
</script>
@stack('scripts')
</body>
</html>
