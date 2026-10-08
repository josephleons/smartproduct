<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cookie;

class LocaleController extends Controller
{
    public function __invoke(string $locale): RedirectResponse
    {
        abort_unless(array_key_exists($locale, config('app.locales')), 404);

        return redirect()->back(fallback: url('/'))
            ->withCookie(Cookie::forever(SetLocale::COOKIE, $locale));
    }
}
