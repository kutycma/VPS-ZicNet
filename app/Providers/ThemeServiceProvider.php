<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Services\Theme\ThemeService;

class ThemeServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(ThemeService::class, function () {
            return new ThemeService();
        });
    }

    public function boot()
    {
        // ─── 1. Prepend active theme's view directory ───────────────────────
        // This makes Laravel look inside resources/views/themes/{slug}/ FIRST
        // before falling back to the default resources/views/ path.
        // Admin views (resources/views/admin/) are NOT inside any theme folder,
        // so they always fall through to the base path and are never overridden.
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('themes')) {
                $activeTheme = \App\Models\Theme::where('is_active', true)->first();
                $slug = $activeTheme ? $activeTheme->slug : 'default';

                $themeViewPath = resource_path("views/themes/{$slug}");

                if (is_dir($themeViewPath)) {
                    // prependLocation puts this path FIRST in the view finder chain
                    $this->app['view']->getFinder()->prependLocation($themeViewPath);
                } else {
                    // Fallback to built-in default theme
                    $defaultPath = resource_path('views/themes/default');
                    if (is_dir($defaultPath)) {
                        $this->app['view']->getFinder()->prependLocation($defaultPath);
                    }
                }
            } else {
                // DB not ready (e.g. fresh install) — still load default theme views
                $defaultPath = resource_path('views/themes/default');
                if (is_dir($defaultPath)) {
                    $this->app['view']->getFinder()->prependLocation($defaultPath);
                }
            }
        } catch (\Exception $e) {
            // Silent fallback: views will load from base resources/views/
        }

        // ─── 2. Share theme CSS only with Blade views (skip API/JSON requests) ─
        View::composer('*', function ($view) {
            static $composed = false;
            if ($composed) return;
            $composed = true;

            // Skip CSS injection for API/JSON requests
            if (request()->expectsJson() || request()->is('api/*')) {
                $view->with('activeTheme', null);
                $view->with('themeCss', '');
                return;
            }

            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('themes')) {
                    $activeTheme = \App\Models\Theme::where('is_active', true)->first();
                    $themeCss = $activeTheme ? $activeTheme->generateFullCss() : '';

                    $view->with('activeTheme', $activeTheme);
                    $view->with('themeCss', $themeCss);
                }
            } catch (\Exception $e) {
                $view->with('activeTheme', null);
                $view->with('themeCss', '');
            }
        });
    }
}
