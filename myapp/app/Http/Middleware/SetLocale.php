<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reads the X-Locale header sent by the Next.js frontend
 * and sets the application locale for that request.
 *
 * Next.js sends: X-Locale: ar
 * This ensures validation messages and mail templates use the right language.
 */
class SetLocale
{
    private const SUPPORTED_LOCALES = ['en', 'ar'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->header('X-Locale', config('app.locale', 'en'));

        if (in_array($locale, self::SUPPORTED_LOCALES, true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
