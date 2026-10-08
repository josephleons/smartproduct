<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * The "tenant" middleware: every dashboard request needs a user tied to an
 * active company. Superadmins are sent to the platform admin instead.
 */
class EnsureUserBelongsToCompany
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->isSuperAdmin()) {
            return redirect()->route('admin.companies.index');
        }

        abort_unless($user?->company_id, 403);

        if (! $user->company->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => __('Your company account is inactive. Please contact SmartLabel support.'),
            ]);
        }

        return $next($request);
    }
}
