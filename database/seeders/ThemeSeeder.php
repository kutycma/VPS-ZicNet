<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Theme;
use App\Services\Theme\ThemeService;

class ThemeSeeder extends Seeder
{
    public function run()
    {
        $themeService = new ThemeService();

        // 1. Default Theme (Indigo/Violet - giữ nguyên giao diện hiện tại)
        $defaultExists = Theme::where('slug', 'default-indigo')->exists();
        Theme::updateOrCreate(
            ['slug' => 'default-indigo'],
            array_merge([
                'name' => 'Default Indigo',
                'description' => 'Giao diện mặc định với tone màu Indigo/Violet sang trọng. Phong cách hiện đại, chuyên nghiệp.',
                'is_default' => true,
                'variables' => $themeService->getDefaultVariables(),
                'custom_css' => null,
            ], $defaultExists ? [] : ['is_active' => true])
        );

        // 2. Liquid Glass Theme (iOS 26 style)
        $liquidGlassCustomCss = <<<'CSS'
/* ===== LIQUID GLASS - iOS 26 Style ===== */
/* This CSS is injected AFTER layout styles, so it overrides everything */

/* ---- SIDEBAR: Complete override for glass effect ---- */
.main-sidebar,
.main-sidebar.elevation-4 {
    background: linear-gradient(180deg, rgba(245, 245, 247, 0.85) 0%, rgba(235, 235, 240, 0.80) 100%) !important;
    backdrop-filter: blur(50px) saturate(200%) !important;
    -webkit-backdrop-filter: blur(50px) saturate(200%) !important;
    box-shadow: 1px 0 20px rgba(0,0,0,0.06) !important;
}

.nav-sidebar .nav-link {
    color: #3a3a3c !important;
    border-radius: 12px !important;
    margin: 3px 10px !important;
    transition: all 0.25s cubic-bezier(0.25, 0.46, 0.45, 0.94) !important;
    font-weight: 500 !important;
}
.nav-sidebar .nav-link:hover {
    background: rgba(0, 122, 255, 0.08) !important;
    color: #007AFF !important;
}
.nav-sidebar .nav-link.active {
    background: rgba(0, 122, 255, 0.12) !important;
    backdrop-filter: blur(20px) !important;
    border: 1px solid rgba(0, 122, 255, 0.15) !important;
    color: #007AFF !important;
    font-weight: 600 !important;
    box-shadow: 0 2px 12px rgba(0, 122, 255, 0.1), inset 0 1px 0 rgba(255,255,255,0.6) !important;
}
.nav-sidebar .nav-link .nav-icon {
    transition: transform 0.2s ease !important;
    color: inherit !important;
}
.nav-sidebar .nav-link:hover .nav-icon {
    transform: scale(1.1);
}
.nav-sidebar .nav-header {
    color: #8e8e93 !important;
    font-weight: 600 !important;
    text-transform: uppercase !important;
}

/* Brand link */
.brand-link {
    border-bottom: 1px solid rgba(0, 0, 0, 0.06) !important;
    background: transparent !important;
}
.brand-link .brand-text {
    font-weight: 700 !important;
    background: linear-gradient(135deg, #007AFF, #AF52DE) !important;
    -webkit-background-clip: text !important;
    -webkit-text-fill-color: transparent !important;
}
.brand-link .brand-image,
.brand-link i.brand-image {
    color: #007AFF !important;
}

/* ---- HEADER: Frosted bar ---- */
.main-header.navbar,
.main-header .navbar {
    background: rgba(249, 249, 251, 0.75) !important;
    backdrop-filter: blur(40px) saturate(180%) !important;
    -webkit-backdrop-filter: blur(40px) saturate(180%) !important;
    border-bottom: 1px solid rgba(0, 0, 0, 0.06) !important;
    box-shadow: none !important;
}

/* ---- CONTENT WRAPPER: Mesh gradient ---- */
.content-wrapper {
    background: linear-gradient(135deg, #e8ecf4 0%, #d4d8e4 25%, #e2d8ef 50%, #d8e4ec 75%, #e8ecf4 100%) !important;
    background-attachment: fixed !important;
}

/* ---- CARDS: Liquid Glass panels ---- */
.card {
    background: rgba(255, 255, 255, 0.55) !important;
    backdrop-filter: blur(40px) saturate(180%) !important;
    -webkit-backdrop-filter: blur(40px) saturate(180%) !important;
    border: 1px solid rgba(255, 255, 255, 0.6) !important;
    border-radius: 20px !important;
    box-shadow: 0 2px 20px rgba(0, 0, 0, 0.06), inset 0 1px 0 rgba(255, 255, 255, 0.8) !important;
    transition: all 0.35s cubic-bezier(0.25, 0.46, 0.45, 0.94) !important;
    overflow: hidden;
}
.card:hover {
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.9) !important;
    transform: translateY(-2px);
}
.card-header {
    background: rgba(255, 255, 255, 0.3) !important;
    border-bottom: 1px solid rgba(0, 0, 0, 0.04) !important;
}

/* ---- BUTTONS ---- */
.btn-primary {
    background: linear-gradient(135deg, #007AFF, #AF52DE) !important;
    border: none !important;
    border-radius: 14px !important;
    box-shadow: 0 2px 10px rgba(0, 122, 255, 0.25), inset 0 1px 0 rgba(255,255,255,0.2) !important;
    transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94) !important;
}
.btn-primary:hover {
    background: linear-gradient(135deg, #0056CC, #8944AB) !important;
    box-shadow: 0 6px 20px rgba(0, 122, 255, 0.35), inset 0 1px 0 rgba(255,255,255,0.3) !important;
    transform: translateY(-2px) scale(1.02) !important;
}
.btn-success {
    background: linear-gradient(135deg, #30D158, #28A745) !important;
    border: none !important;
    border-radius: 14px !important;
    box-shadow: 0 2px 10px rgba(48, 209, 88, 0.25), inset 0 1px 0 rgba(255,255,255,0.2) !important;
}
.btn-danger {
    background: linear-gradient(135deg, #FF453A, #D63030) !important;
    border: none !important;
    border-radius: 14px !important;
    box-shadow: 0 2px 10px rgba(255, 69, 58, 0.25), inset 0 1px 0 rgba(255,255,255,0.2) !important;
}

/* ---- STAT CARDS ---- */
.stat-card {
    backdrop-filter: blur(30px) saturate(150%) !important;
    -webkit-backdrop-filter: blur(30px) saturate(150%) !important;
    border: 1px solid rgba(255, 255, 255, 0.25) !important;
    border-radius: 22px !important;
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08), inset 0 1px 0 rgba(255,255,255,0.3) !important;
}

/* ---- DROPDOWN ---- */
.dropdown-menu {
    background: rgba(255, 255, 255, 0.82) !important;
    backdrop-filter: blur(40px) saturate(180%) !important;
    -webkit-backdrop-filter: blur(40px) saturate(180%) !important;
    border: 1px solid rgba(255, 255, 255, 0.5) !important;
    border-radius: 16px !important;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.12) !important;
    padding: 6px !important;
}
.dropdown-item {
    border-radius: 10px !important;
    transition: background 0.2s !important;
}
.dropdown-item:hover {
    background: rgba(0, 122, 255, 0.08) !important;
}

/* ---- BALANCE BADGE ---- */
.balance-badge {
    backdrop-filter: blur(20px) !important;
    border: 1px solid rgba(255, 255, 255, 0.2) !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08), inset 0 1px 0 rgba(255,255,255,0.3) !important;
}

/* ---- ALERTS ---- */
.alert {
    backdrop-filter: blur(20px) saturate(150%) !important;
    -webkit-backdrop-filter: blur(20px) saturate(150%) !important;
    border-radius: 16px !important;
    border: 1px solid rgba(255, 255, 255, 0.2) !important;
}

/* ---- TABLES ---- */
.table thead th {
    border-bottom: 1px solid rgba(0, 0, 0, 0.06) !important;
    background: rgba(255, 255, 255, 0.3) !important;
    color: #3a3a3c !important;
}
.table tbody tr {
    transition: background 0.2s !important;
}
.table tbody tr:hover {
    background: rgba(0, 122, 255, 0.04) !important;
}

/* ---- BADGES ---- */
.badge {
    border-radius: 10px !important;
    font-weight: 500 !important;
}

/* ---- FORM CONTROLS (inside content area) ---- */
.content-wrapper .form-control {
    border-radius: 12px !important;
    border: 1px solid rgba(0, 0, 0, 0.08) !important;
    background: rgba(255, 255, 255, 0.6) !important;
    backdrop-filter: blur(10px) !important;
    transition: all 0.3s !important;
}
.content-wrapper .form-control:focus {
    border-color: #007AFF !important;
    box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.12) !important;
    background: rgba(255, 255, 255, 0.8) !important;
}

/* ---- SCROLLBAR ---- */
::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.15); border-radius: 3px; }
::-webkit-scrollbar-track { background: transparent; }

/* ---- FOOTER ---- */
.main-footer {
    background: rgba(255, 255, 255, 0.45) !important;
    backdrop-filter: blur(20px) !important;
    -webkit-backdrop-filter: blur(20px) !important;
    border-top: 1px solid rgba(0, 0, 0, 0.06) !important;
}

/* ---- SMALL BOX (AdminLTE) ---- */
.small-box {
    border-radius: 20px !important;
    backdrop-filter: blur(20px) saturate(150%) !important;
    border: 1px solid rgba(255, 255, 255, 0.2) !important;
}

/* ---- PAGE TITLE ---- */
.content-header h1 {
    color: #1c1c1e !important;
}

/* ---- PAGINATION ---- */
.page-item .page-link {
    border-radius: 10px !important;
    border: 1px solid rgba(0, 0, 0, 0.06) !important;
    margin: 0 2px !important;
}
.page-item.active .page-link {
    background: linear-gradient(135deg, #007AFF, #AF52DE) !important;
    border: none !important;
}

/* ---- DATATABLES ---- */
.dataTables_wrapper .dataTables_filter input {
    border-radius: 12px !important;
    border: 1px solid rgba(0, 0, 0, 0.08) !important;
    background: rgba(255, 255, 255, 0.6) !important;
}

/* ---- WELCOME PAGE OVERRIDES ---- */
.plan-card {
    backdrop-filter: blur(30px) saturate(150%) !important;
    -webkit-backdrop-filter: blur(30px) saturate(150%) !important;
    border: 1px solid rgba(255, 255, 255, 0.12) !important;
    border-radius: 20px !important;
}
.plan-card:hover {
    border-color: #007AFF !important;
    box-shadow: 0 15px 40px rgba(0,0,0,0.3), 0 0 30px rgba(0, 122, 255, 0.15) !important;
}
CSS;

        Theme::updateOrCreate(
            ['slug' => 'liquid-glass'],
            [
                'name' => 'Liquid Glass',
                'description' => 'Phong cách Liquid Glass lấy cảm hứng từ iOS 26. Bề mặt trong suốt, hiệu ứng kính mờ, ánh sáng phản chiếu mềm mại. Cảm giác cao cấp và hiện đại.',
                'is_active' => false,
                'is_default' => false,
                'variables' => $themeService->getLiquidGlassVariables(),
                'custom_css' => $liquidGlassCustomCss,
            ]
        );
    }
}
