<?php

use App\Http\Middleware\EnsurePlanHasFeature;
use App\Http\Middleware\EnsureSubscriptionAllowsWrites;
use App\Http\Middleware\EnsureUserBelongsToCompany;
use App\Http\Middleware\EnsureUserIsSuperAdmin;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenant' => EnsureUserBelongsToCompany::class,
            'superadmin' => EnsureUserIsSuperAdmin::class,
            'subscription' => EnsureSubscriptionAllowsWrites::class,
            'feature' => EnsurePlanHasFeature::class,
        ]);
        $middleware->web(append: [SetLocale::class]);

        // Behind the host's HTTPS reverse proxy (nginx): trust its X-Forwarded-* headers so
        // Laravel sees https, the real host and the client IP (used for throttling and the
        // hashed IP on scans). Safe with "*" because docker-compose publishes the app on
        // 127.0.0.1 only, so the proxy is the only thing that can reach it.
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR |
                Request::HEADER_X_FORWARDED_HOST |
                Request::HEADER_X_FORWARDED_PORT |
                Request::HEADER_X_FORWARDED_PROTO,
        );

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
