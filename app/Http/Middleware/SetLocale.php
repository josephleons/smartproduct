<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the language chosen with the SW / EN switcher (a long-lived cookie),
 * so sellers and customers scanning a QR keep their choice without logging in.
 */
class SetLocale
{
    public const COOKIE = 'locale';

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->cookie(self::COOKIE);

        if (is_string($locale) && array_key_exists($locale, config('app.locales'))) {
            app()->setLocale($locale);
        }

        Carbon::setLocale(app()->getLocale());

        return $next($request);
    }
}
