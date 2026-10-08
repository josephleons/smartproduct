<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\Billing\PlanLimits;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;

abstract class Controller
{
    use AuthorizesRequests;

    /** Redirect with a friendly message when the company's plan limit for $resource is used up. */
    protected function planLimitResponse(Company $company, string $resource, string $redirectRoute): ?RedirectResponse
    {
        $limits = app(PlanLimits::class);

        if (! $limits->reached($company, $resource)) {
            return null;
        }

        return redirect()->route($redirectRoute)->with('error', $limits->message($resource, $limits->limit($company, $resource)));
    }
}
