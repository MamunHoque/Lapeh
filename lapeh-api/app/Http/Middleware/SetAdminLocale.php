<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetAdminLocale
{
    /**
     * Locale: ?lang override → session → user's stored locale → English.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->query('lang');
        if (! in_array($locale, ['en', 'ar'], true)) {
            $locale = session('admin_locale', $request->user()?->locale ?? 'en');
        }
        session(['admin_locale' => $locale]);
        app()->setLocale($locale);

        return $next($request);
    }
}
