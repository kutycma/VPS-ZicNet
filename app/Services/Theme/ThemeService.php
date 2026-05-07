<?php

namespace App\Services\Theme;

use App\Models\Theme;
use Illuminate\Support\Str;

class ThemeService
{
    /**
     * Lấy theme đang active (cached 60 phút)
     */
    public function getActiveTheme(): ?Theme
    {
        return cache()->remember('active_theme', 3600, function () {
            return Theme::where('is_active', true)->first();
        });
    }

    /**
     * Lấy CSS output của theme active (cached)
     */
    public function getActiveCss(): string
    {
        return cache()->remember('active_theme_css', 3600, function () {
            $theme = $this->getActiveTheme();
            return $theme ? $theme->generateFullCss() : '';
        });
    }

    /**
     * Kích hoạt 1 theme
     */
    public function activateTheme(int $themeId): Theme
    {
        $theme = Theme::findOrFail($themeId);
        $theme->activate();
        $this->clearCache();
        return $theme;
    }

    /**
     * Lấy tất cả themes
     */
    public function getAllThemes()
    {
        return Theme::orderBy('is_active', 'desc')->orderBy('is_default', 'desc')->orderBy('name')->get();
    }

    /**
     * Tạo theme mới
     */
    public function createTheme(array $data): Theme
    {
        $data['slug'] = Str::slug($data['name']);
        
        if (!isset($data['variables']) || empty($data['variables'])) {
            $data['variables'] = $this->getDefaultVariables();
        }

        return Theme::create($data);
    }

    /**
     * Cập nhật theme
     */
    public function updateTheme(int $id, array $data): Theme
    {
        $theme = Theme::findOrFail($id);
        
        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }
        
        $theme->update($data);
        $this->clearCache();
        
        return $theme->fresh();
    }

    /**
     * Xoá theme (không cho xoá theme default)
     */
    public function deleteTheme(int $id): bool
    {
        $theme = Theme::findOrFail($id);
        
        if ($theme->is_default) {
            throw new \Exception('Không thể xoá theme mặc định.');
        }
        
        if ($theme->is_active) {
            // Activate default theme trước khi xoá
            $default = Theme::where('is_default', true)->first();
            if ($default) {
                $default->activate();
            }
        }
        
        $theme->delete();
        $this->clearCache();
        
        return true;
    }

    /**
     * Clear theme cache
     */
    public function clearCache(): void
    {
        cache()->forget('active_theme');
        cache()->forget('active_theme_css');
    }

    /**
     * Variables mặc định (giữ nguyên giao diện hiện tại)
     */
    public function getDefaultVariables(): array
    {
        return [
            // Brand / Primary Colors
            'primary' => '#6366f1',
            'primary_dark' => '#4f46e5',
            'primary_light' => '#818cf8',
            'secondary' => '#8b5cf6',
            'secondary_dark' => '#7c3aed',
            'accent' => '#f59e0b',
            'accent_dark' => '#d97706',
            'success' => '#10b981',
            'success_dark' => '#059669',
            'danger' => '#ef4444',
            'danger_dark' => '#dc2626',
            'warning' => '#f59e0b',

            // Sidebar
            'sidebar_bg_from' => '#0f172a',
            'sidebar_bg_to' => '#1e1b4b',
            'sidebar_text' => '#a5b4fc',
            'sidebar_hover_bg' => 'rgba(129, 140, 248, 0.15)',
            'sidebar_hover_text' => '#e0e7ff',
            'sidebar_active_from' => '#6366f1',
            'sidebar_active_to' => '#8b5cf6',
            'sidebar_active_shadow' => 'rgba(99, 102, 241, 0.4)',
            'sidebar_header_color' => '#6366f1',
            'sidebar_border' => 'rgba(255,255,255,0.08)',

            // Content Area
            'content_bg' => '#f8fafc',
            'card_bg' => '#ffffff',
            'card_border' => 'none',
            'card_shadow' => '0 1px 3px rgba(0,0,0,0.06)',
            'card_hover_shadow' => '0 4px 15px rgba(0,0,0,0.1)',
            'card_radius' => '12px',

            // Header
            'header_bg' => '#ffffff',
            'header_border' => '#e2e8f0',

            // Auth Pages
            'auth_bg_from' => '#0f172a',
            'auth_bg_mid' => '#1e1b4b',
            'auth_bg_to' => '#312e81',
            'auth_card_bg' => 'rgba(255, 255, 255, 0.05)',
            'auth_card_border' => 'rgba(255, 255, 255, 0.1)',
            'auth_card_blur' => '20px',
            'auth_glow_1' => 'rgba(99,102,241,0.15)',
            'auth_glow_2' => 'rgba(139,92,246,0.1)',
            'auth_input_bg' => 'rgba(255, 255, 255, 0.07)',
            'auth_input_border' => 'rgba(255, 255, 255, 0.12)',

            // Welcome / Landing
            'welcome_bg' => '#0f172a',
            'welcome_hero_glow' => '#6366f1',
            'welcome_card_bg' => 'rgba(30, 41, 59, 0.7)',
            'welcome_card_border' => 'rgba(255, 255, 255, 0.1)',

            // Typography
            'text_heading' => '#1e293b',
            'text_body' => '#475569',
            'text_muted' => '#94a3b8',
            'text_light' => '#e2e8f0',
            'text_on_primary' => '#ffffff',
            'font_family' => "'Inter', sans-serif",

            // Brand Text
            'brand_gradient_from' => '#818cf8',
            'brand_gradient_to' => '#c084fc',

            // Balance Badge
            'balance_bg_from' => '#f59e0b',
            'balance_bg_to' => '#d97706',

            // Footer
            'footer_border' => '#e2e8f0',
            'footer_link_color' => '#6366f1',
        ];
    }

    /**
     * Variables cho theme Liquid Glass (iOS 26 style)
     */
    public function getLiquidGlassVariables(): array
    {
        return [
            // Brand / Primary Colors - Soft pastel tones
            'primary' => '#007AFF',
            'primary_dark' => '#0056CC',
            'primary_light' => '#5AC8FA',
            'secondary' => '#AF52DE',
            'secondary_dark' => '#8944AB',
            'accent' => '#FF9F0A',
            'accent_dark' => '#E08600',
            'success' => '#30D158',
            'success_dark' => '#28A745',
            'danger' => '#FF453A',
            'danger_dark' => '#D63030',
            'warning' => '#FFD60A',

            // Sidebar - Frosted translucent glass
            'sidebar_bg_from' => 'rgba(245, 245, 247, 0.72)',
            'sidebar_bg_to' => 'rgba(235, 235, 240, 0.68)',
            'sidebar_text' => '#3a3a3c',
            'sidebar_hover_bg' => 'rgba(0, 122, 255, 0.08)',
            'sidebar_hover_text' => '#007AFF',
            'sidebar_active_from' => 'rgba(0, 122, 255, 0.18)',
            'sidebar_active_to' => 'rgba(88, 86, 214, 0.15)',
            'sidebar_active_shadow' => 'rgba(0, 122, 255, 0.12)',
            'sidebar_header_color' => '#8e8e93',
            'sidebar_border' => 'rgba(0, 0, 0, 0.06)',

            // Content Area - Light frosted
            'content_bg' => 'linear-gradient(135deg, #e8ecf4 0%, #d4d8e4 25%, #e2d8ef 50%, #d8e4ec 75%, #e8ecf4 100%)',
            'card_bg' => 'rgba(255, 255, 255, 0.55)',
            'card_border' => '1px solid rgba(255, 255, 255, 0.6)',
            'card_shadow' => '0 2px 20px rgba(0, 0, 0, 0.06), inset 0 1px 0 rgba(255, 255, 255, 0.8)',
            'card_hover_shadow' => '0 8px 32px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.9)',
            'card_radius' => '20px',

            // Header - Translucent bar
            'header_bg' => 'rgba(249, 249, 251, 0.72)',
            'header_border' => 'rgba(0, 0, 0, 0.06)',

            // Auth Pages - Deep glass with vivid mesh gradient
            'auth_bg_from' => '#1a1a2e',
            'auth_bg_mid' => '#16213e',
            'auth_bg_to' => '#0f3460',
            'auth_card_bg' => 'rgba(255, 255, 255, 0.08)',
            'auth_card_border' => 'rgba(255, 255, 255, 0.18)',
            'auth_card_blur' => '40px',
            'auth_glow_1' => 'rgba(0, 122, 255, 0.2)',
            'auth_glow_2' => 'rgba(175, 82, 222, 0.15)',
            'auth_input_bg' => 'rgba(255, 255, 255, 0.06)',
            'auth_input_border' => 'rgba(255, 255, 255, 0.15)',

            // Welcome / Landing - Immersive glass
            'welcome_bg' => '#000000',
            'welcome_hero_glow' => '#007AFF',
            'welcome_card_bg' => 'rgba(255, 255, 255, 0.06)',
            'welcome_card_border' => 'rgba(255, 255, 255, 0.12)',

            // Typography
            'text_heading' => '#1c1c1e',
            'text_body' => '#3a3a3c',
            'text_muted' => '#8e8e93',
            'text_light' => '#f2f2f7',
            'text_on_primary' => '#ffffff',
            'font_family' => "'Inter', -apple-system, BlinkMacSystemFont, sans-serif",

            // Brand Text
            'brand_gradient_from' => '#007AFF',
            'brand_gradient_to' => '#AF52DE',

            // Balance Badge
            'balance_bg_from' => '#FF9F0A',
            'balance_bg_to' => '#FF6B00',

            // Footer
            'footer_border' => 'rgba(0, 0, 0, 0.06)',
            'footer_link_color' => '#007AFF',
        ];
    }
}
