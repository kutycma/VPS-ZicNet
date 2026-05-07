<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    /**
     * Supported locales
     */
    public const SUPPORTED_LOCALES = ['vi', 'en', 'zh'];

    public function handle(Request $request, Closure $next)
    {
        $locale = session('locale', config('app.locale', 'vi'));

        if ($request->has('lang')) {
            $locale = $request->get('lang');
            session(['locale' => $locale]);
        }

        if (in_array($locale, self::SUPPORTED_LOCALES)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
