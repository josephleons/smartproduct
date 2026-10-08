<?php

namespace App\Http\Middleware;

use App\Enums\PlanFeature;
use App\Services\Billing\PlanLimits;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Usage: ->middleware('feature:analytics') */
class EnsurePlanHasFeature
{
    public function __construct(private PlanLimits $limits) {}

    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $feature = PlanFeature::from($feature);
        $company = $request->user()?->company;

        if ($company && $this->limits->hasFeature($company, $feature)) {
            return $next($request);
        }

        return redirect()->route('billing.show')->with('error', __(':feature is not included in your plan.', ['feature' => $feature->label()]));
    }
}
