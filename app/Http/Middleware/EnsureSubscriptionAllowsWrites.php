<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Read-only mode for unpaid companies (grace period over): they can still look
 * around and pay their bill, but cannot change data or print labels.
 * Customer QR pages are not affected.
 */
class EnsureSubscriptionAllowsWrites
{
    /** Routes a read-only company may still use to settle its bill. */
    private const ALWAYS_ALLOWED = ['billing.*', 'logout'];

    private const BLOCKED_READS = ['labels.preview', 'labels.pdf'];

    public function handle(Request $request, Closure $next): Response
    {
        $subscription = $request->user()?->company?->subscription;

        if ($subscription?->allowsWrites() || $request->routeIs(...self::ALWAYS_ALLOWED)) {
            return $next($request);
        }

        if ($request->isMethodSafe() && ! $request->routeIs(...self::BLOCKED_READS)) {
            return $next($request);
        }

        $message = __('Your subscription is unpaid, so your account is read-only. Pay your bill to add products and print labels again.');

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 402);
        }

        return redirect()->route('billing.show')->with('error', $message);
    }
}
