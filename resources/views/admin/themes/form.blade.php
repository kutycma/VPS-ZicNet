@extends('layouts.admin')
@section('title', $theme ? 'Sửa Theme: ' . $theme->name : 'Tạo Theme mới')
@section('page_title', $theme ? 'Chỉnh sửa Theme' : 'Tạo Theme mới')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.themes.index') }}">Themes</a></li>
    <li class="breadcrumb-item active">{{ $theme ? 'Sửa' : 'Tạo mới' }}</li>
@endsection

@push('styles')
<style>
    .color-group { margin-bottom: 30px; }
    .color-group h5 {
        font-weight: 700;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 15px;
        padding-bottom: 8px;
        border-bottom: 2px solid #e2e8f0;
    }
    .color-group h5 i { margin-right: 8px; opacity: 0.7; }
    .color-input-row {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
        padding: 8px 12px;
        border-radius: 10px;
        background: #f8fafc;
        transition: background 0.2s;
    }
    .color-input-row:hover { background: #f1f5f9; }
    .color-input-row label {
        flex: 1;
        font-size: 0.82rem;
        color: #475569;
        margin-bottom: 0;
        font-weight: 500;
    }
    .color-input-row input[type="color"] {
        width: 36px;
        height: 36px;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        cursor: pointer;
        padding: 2px;
        background: #fff;
        margin-right: 8px;
    }
    .color-input-row input[type="text"] {
        width: 220px;
        font-size: 0.82rem;
        font-family: 'Courier New', monospace;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        padding: 6px 10px;
    }
    .preview-panel {
        position: sticky;
        top: 80px;
    }
    .preview-mini {
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        border: 1px solid #e2e8f0;
        background: #f1f5f9;
        min-height: 350px;
    }
    .preview-sidebar {
        width: 100%;
        height: 60px;
        border-radius: 12px 12px 0 0;
        display: flex;
        align-items: center;
        padding: 0 16px;
    }
    .preview-sidebar .dot {
        width: 18px; height: 18px;
        border-radius: 6px;
        margin-right: 6px;
        border: 1px solid rgba(255,255,255,0.2);
    }
    .preview-content {
        padding: 16px;
    }
    .preview-card-mini {
        border-radius: 12px;
        padding: 14px;
        margin-bottom: 10px;
    }
    .preview-btn-mini {
        display: inline-block;
        padding: 6px 18px;
        border-radius: 10px;
        color: #fff;
        font-size: 0.75rem;
        font-weight: 600;
    }
</style>
@endpush

@section('content')
<form action="{{ $theme ? route('admin.themes.update', $theme) : route('admin.themes.store') }}" method="POST">
    @csrf
    @if($theme) @method('PUT') @endif

    <div class="row">
        {{-- Left: Form --}}
        <div class="col-lg-8">
            {{-- Basic Info --}}
            <div class="card mb-4">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Thông tin cơ bản</h3></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Tên Theme <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $theme->name ?? '') }}" required placeholder="VD: Liquid Glass">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Mô tả</label>
                                <input type="text" name="description" class="form-control" value="{{ old('description', $theme->description ?? '') }}" placeholder="Mô tả ngắn về theme">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Color Variables --}}
            @php
                $currentVars = $theme ? ($theme->variables ?? []) : $defaultVariables;
                
                $sections = [
                    'Branding & Màu chính' => [
                        'icon' => 'fas fa-palette',
                        'vars' => ['primary', 'primary_dark', 'primary_light', 'secondary', 'secondary_dark', 'accent', 'accent_dark', 'success', 'success_dark', 'danger', 'danger_dark', 'warning']
                    ],
                    'Sidebar' => [
                        'icon' => 'fas fa-columns',
                        'vars' => ['sidebar_bg_from', 'sidebar_bg_to', 'sidebar_text', 'sidebar_hover_bg', 'sidebar_hover_text', 'sidebar_active_from', 'sidebar_active_to', 'sidebar_active_shadow', 'sidebar_header_color', 'sidebar_border']
                    ],
                    'Nội dung & Card' => [
                        'icon' => 'fas fa-square',
                        'vars' => ['content_bg', 'card_bg', 'card_border', 'card_shadow', 'card_hover_shadow', 'card_radius']
                    ],
                    'Header' => [
                        'icon' => 'fas fa-window-maximize',
                        'vars' => ['header_bg', 'header_border']
                    ],
                    'Trang đăng nhập (Auth)' => [
                        'icon' => 'fas fa-lock',
                        'vars' => ['auth_bg_from', 'auth_bg_mid', 'auth_bg_to', 'auth_card_bg', 'auth_card_border', 'auth_card_blur', 'auth_glow_1', 'auth_glow_2', 'auth_input_bg', 'auth_input_border']
                    ],
                    'Trang chủ (Welcome)' => [
                        'icon' => 'fas fa-home',
                        'vars' => ['welcome_bg', 'welcome_hero_glow', 'welcome_card_bg', 'welcome_card_border']
                    ],
                    'Typography' => [
                        'icon' => 'fas fa-font',
                        'vars' => ['text_heading', 'text_body', 'text_muted', 'text_light', 'text_on_primary', 'font_family']
                    ],
                    'Thương hiệu & Khác' => [
                        'icon' => 'fas fa-star',
                        'vars' => ['brand_gradient_from', 'brand_gradient_to', 'balance_bg_from', 'balance_bg_to', 'footer_border', 'footer_link_color']
                    ],
                ];

                $varLabels = [
                    'primary' => 'Màu chính (Primary)',
                    'primary_dark' => 'Primary Dark',
                    'primary_light' => 'Primary Light',
                    'secondary' => 'Màu phụ (Secondary)',
                    'secondary_dark' => 'Secondary Dark',
                    'accent' => 'Accent',
                    'accent_dark' => 'Accent Dark',
                    'success' => 'Success (Xanh lá)',
                    'success_dark' => 'Success Dark',
                    'danger' => 'Danger (Đỏ)',
                    'danger_dark' => 'Danger Dark',
                    'warning' => 'Warning (Vàng)',
                    'sidebar_bg_from' => 'Gradient Start',
                    'sidebar_bg_to' => 'Gradient End',
                    'sidebar_text' => 'Màu chữ menu',
                    'sidebar_hover_bg' => 'Hover Background',
                    'sidebar_hover_text' => 'Hover Text',
                    'sidebar_active_from' => 'Active Gradient Start',
                    'sidebar_active_to' => 'Active Gradient End',
                    'sidebar_active_shadow' => 'Active Shadow',
                    'sidebar_header_color' => 'Header Label',
                    'sidebar_border' => 'Border Color',
                    'content_bg' => 'Background Nội dung',
                    'card_bg' => 'Background Card',
                    'card_border' => 'Border Card',
                    'card_shadow' => 'Shadow Card',
                    'card_hover_shadow' => 'Hover Shadow Card',
                    'card_radius' => 'Border Radius Card',
                    'header_bg' => 'Background Header',
                    'header_border' => 'Border Header',
                    'auth_bg_from' => 'BG Gradient Start',
                    'auth_bg_mid' => 'BG Gradient Mid',
                    'auth_bg_to' => 'BG Gradient End',
                    'auth_card_bg' => 'Card Background',
                    'auth_card_border' => 'Card Border',
                    'auth_card_blur' => 'Card Blur (px)',
                    'auth_glow_1' => 'Glow Color 1',
                    'auth_glow_2' => 'Glow Color 2',
                    'auth_input_bg' => 'Input Background',
                    'auth_input_border' => 'Input Border',
                    'welcome_bg' => 'Background chính',
                    'welcome_hero_glow' => 'Hero Glow Color',
                    'welcome_card_bg' => 'Card Background',
                    'welcome_card_border' => 'Card Border',
                    'text_heading' => 'Heading Color',
                    'text_body' => 'Body Text Color',
                    'text_muted' => 'Muted Text Color',
                    'text_light' => 'Light Text Color',
                    'text_on_primary' => 'Text on Primary BG',
                    'font_family' => 'Font Family',
                    'brand_gradient_from' => 'Brand Gradient Start',
                    'brand_gradient_to' => 'Brand Gradient End',
                    'balance_bg_from' => 'Balance Badge Start',
                    'balance_bg_to' => 'Balance Badge End',
                    'footer_border' => 'Footer Border',
                    'footer_link_color' => 'Footer Link Color',
                ];
            @endphp

            @foreach($sections as $sectionName => $section)
            <div class="card mb-3">
                <div class="card-header" data-toggle="collapse" data-target="#section-{{ Str::slug($sectionName) }}" style="cursor: pointer;">
                    <h3 class="card-title"><i class="{{ $section['icon'] }} mr-2"></i>{{ $sectionName }}</h3>
                    <div class="card-tools"><i class="fas fa-chevron-down"></i></div>
                </div>
                <div class="collapse show" id="section-{{ Str::slug($sectionName) }}">
                    <div class="card-body py-2">
                        @foreach($section['vars'] as $varKey)
                            @php $value = $currentVars[$varKey] ?? ($defaultVariables[$varKey] ?? ''); @endphp
                            <div class="color-input-row">
                                <label>{{ $varLabels[$varKey] ?? $varKey }}</label>
                                @php $isColor = preg_match('/^#[0-9a-fA-F]{3,8}$/', $value); @endphp
                                @if($isColor)
                                    <input type="color" value="{{ $value }}" 
                                           onchange="document.getElementById('input_{{ $varKey }}').value = this.value; updatePreview();">
                                @endif
                                <input type="text" name="var_{{ $varKey }}" id="input_{{ $varKey }}" value="{{ $value }}" 
                                       class="form-control" onchange="updatePreview();" 
                                       @if($isColor) oninput="this.previousElementSibling.value = this.value" @endif>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endforeach

            {{-- Custom CSS --}}
            <div class="card mb-4">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-code mr-2"></i>CSS Tuỳ Chỉnh</h3></div>
                <div class="card-body">
                    <textarea name="custom_css" class="form-control" rows="12" 
                              style="font-family: 'Courier New', monospace; font-size: 0.85rem; background: #1e293b; color: #e2e8f0; border-radius: 12px; padding: 16px;"
                              placeholder="/* Thêm CSS tuỳ chỉnh cho theme này */&#10;.card { /* ... */ }">{{ old('custom_css', $theme->custom_css ?? '') }}</textarea>
                    <small class="text-muted mt-2 d-block">Bạn có thể dùng các biến CSS: <code>var(--theme-primary)</code>, <code>var(--theme-secondary)</code>, v.v.</small>
                </div>
            </div>

            {{-- Submit --}}
            <div class="mb-4">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save mr-2"></i> {{ $theme ? 'Cập nhật Theme' : 'Tạo Theme' }}
                </button>
                <a href="{{ route('admin.themes.index') }}" class="btn btn-outline-secondary btn-lg ml-2">
                    <i class="fas fa-arrow-left mr-1"></i> Quay lại
                </a>
            </div>
        </div>

        {{-- Right: Live Preview --}}
        <div class="col-lg-4">
            <div class="preview-panel">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-eye mr-2"></i>Xem trước</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="preview-mini" id="previewPanel">
                            {{-- Sidebar Preview --}}
                            <div class="preview-sidebar" id="previewSidebar">
                                <span class="dot" id="dotPrimary"></span>
                                <span class="dot" id="dotSecondary"></span>
                                <span class="dot" id="dotAccent"></span>
                                <span class="dot" id="dotSuccess"></span>
                                <span class="dot" id="dotDanger"></span>
                            </div>
                            {{-- Content Preview --}}
                            <div class="preview-content" id="previewContent">
                                <div class="preview-card-mini" id="previewCard1">
                                    <div style="height:8px; border-radius:4px; width:60%; margin-bottom:8px;" id="previewHeading"></div>
                                    <div style="height:6px; border-radius:3px; width:90%; margin-bottom:6px;" id="previewText1"></div>
                                    <div style="height:6px; border-radius:3px; width:75%; margin-bottom:12px;" id="previewText2"></div>
                                    <span class="preview-btn-mini" id="previewBtn">Button</span>
                                </div>
                                <div class="preview-card-mini" id="previewCard2">
                                    <div style="height:6px; border-radius:3px; width:50%; margin-bottom:6px;" id="previewText3"></div>
                                    <div style="height:6px; border-radius:3px; width:80%;" id="previewText4"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Variable Reference --}}
                <div class="card mt-3">
                    <div class="card-header"><h3 class="card-title"><i class="fas fa-lightbulb mr-2"></i>Mẹo</h3></div>
                    <div class="card-body" style="font-size:0.82rem;">
                        <ul class="pl-3 mb-0" style="color:#64748b;">
                            <li class="mb-1">Sử dụng <code>rgba()</code> cho hiệu ứng trong suốt</li>
                            <li class="mb-1">Giá trị <code>blur</code> cao hơn = glass effect mạnh hơn</li>
                            <li class="mb-1">Custom CSS sẽ ghi đè lên biến mặc định</li>
                            <li>Nhấn Lưu để áp dụng ngay nếu theme đang active</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
function getVal(key) {
    const el = document.getElementById('input_' + key);
    return el ? el.value : '';
}

function updatePreview() {
    const sidebar = document.getElementById('previewSidebar');
    const content = document.getElementById('previewContent');
    const card1 = document.getElementById('previewCard1');
    const card2 = document.getElementById('previewCard2');
    
    // Sidebar gradient
    sidebar.style.background = `linear-gradient(135deg, ${getVal('sidebar_bg_from')}, ${getVal('sidebar_bg_to')})`;
    
    // Color dots
    document.getElementById('dotPrimary').style.background = getVal('primary');
    document.getElementById('dotSecondary').style.background = getVal('secondary');
    document.getElementById('dotAccent').style.background = getVal('accent');
    document.getElementById('dotSuccess').style.background = getVal('success');
    document.getElementById('dotDanger').style.background = getVal('danger');
    
    // Content bg
    const contentBg = getVal('content_bg');
    content.style.background = contentBg;
    
    // Cards
    card1.style.background = getVal('card_bg');
    card1.style.borderRadius = getVal('card_radius');
    card1.style.boxShadow = getVal('card_shadow');
    card1.style.border = getVal('card_border');
    card2.style.background = getVal('card_bg');
    card2.style.borderRadius = getVal('card_radius');
    card2.style.boxShadow = getVal('card_shadow');
    card2.style.border = getVal('card_border');
    
    // Heading & Text
    document.getElementById('previewHeading').style.background = getVal('text_heading');
    document.getElementById('previewText1').style.background = getVal('text_muted');
    document.getElementById('previewText2').style.background = getVal('text_muted');
    document.getElementById('previewText3').style.background = getVal('text_muted');
    document.getElementById('previewText4').style.background = getVal('text_muted');
    
    // Button
    const btn = document.getElementById('previewBtn');
    btn.style.background = `linear-gradient(135deg, ${getVal('primary')}, ${getVal('secondary')})`;
}

// Init preview on load
document.addEventListener('DOMContentLoaded', updatePreview);
</script>
@endpush
