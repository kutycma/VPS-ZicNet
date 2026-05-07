<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Theme;

class ThemeApiController extends Controller
{
    /**
     * GET /api/theme/active
     * Returns the active theme's CSS variables and custom CSS.
     * Public endpoint — no auth required.
     * Any frontend theme (Next.js, Vue, etc.) should call this on init.
     */
    public function active()
    {
        $theme = Theme::where('is_active', true)->first();

        if (!$theme) {
            return response()->json([
                'slug'        => 'default',
                'name'        => 'Default',
                'variables'   => [],
                'custom_css'  => null,
                'css_vars'    => ':root {}',
            ]);
        }

        // Build raw :root { --theme-* } block for themes that want to inject CSS directly
        $cssVars = ":root {\n";
        foreach (($theme->variables ?? []) as $key => $value) {
            $cssVar = str_replace('_', '-', $key);
            $cssVars .= "    --theme-{$cssVar}: {$value};\n";
        }
        $cssVars .= "}";

        return response()->json([
            'slug'        => $theme->slug,
            'name'        => $theme->name,
            'description' => $theme->description,
            'variables'   => $theme->variables ?? [],
            'custom_css'  => $theme->custom_css,
            'css_vars'    => $cssVars,
        ]);
    }
}
