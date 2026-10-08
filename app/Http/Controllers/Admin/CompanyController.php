<?php

namespace App\Http\Controllers\Admin;

use App\Actions\CreateCompany;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCompanyRequest;
use App\Http\Requests\Admin\UpdateCompanyRequest;
use App\Models\Company;
use App\Models\Plan;
use App\Models\Scopes\CompanyScope;
use App\Services\Billing\PlanLimits;
use App\Services\Billing\SubscriptionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Platform admin: companies are onboarded here (no self-registration).
 * Admin code reads across tenants, so the company scope is bypassed explicitly.
 */
class CompanyController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Company::class);

        $withoutTenantScope = fn ($query) => $query->withoutGlobalScope(CompanyScope::class);

        $companies = Company::query()
            ->withCount([
                'branches' => $withoutTenantScope,
                'products' => $withoutTenantScope,
                'users',
            ])
            ->with(['users' => fn ($q) => $q->where('role', 'owner')->orderBy('id'), 'subscription.plan'])
            ->when($request->string('status')->value(), fn ($q, $status) => $q->whereHas('subscription', fn ($s) => $s->withoutGlobalScopes()->where('status', $status)))
            ->when($request->string('q')->trim()->value(), fn ($q, $search) => $q->where(
                fn ($w) => $w->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")
            ))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.companies.index', ['companies' => $companies]);
    }

    public function create(): View
    {
        $this->authorize('create', Company::class);

        return view('admin.companies.create', ['plans' => Plan::active()->ordered()->get()]);
    }

    public function store(StoreCompanyRequest $request, CreateCompany $createCompany): RedirectResponse
    {
        $this->authorize('create', Company::class);

        $company = $createCompany->handle($request->validated());

        return redirect()->route('admin.companies.index')
            ->with('success', __(':company created. The owner can now log in with :email.', [
                'company' => $company->name,
                'email' => $request->validated('owner_email'),
            ]));
    }

    public function edit(Company $company, PlanLimits $limits): View
    {
        $this->authorize('manage', $company);

        return view('admin.companies.edit', [
            'company' => $company->load(['users', 'subscription.plan']),
            'plans' => Plan::ordered()->get(),
            'usage' => $limits->usage($company),
        ]);
    }

    public function update(UpdateCompanyRequest $request, Company $company, SubscriptionManager $subscriptions): RedirectResponse
    {
        $this->authorize('manage', $company);

        $company->update($request->safe()->except('plan_id'));

        if ($company->subscription && $company->subscription->plan_id !== (int) $request->validated('plan_id')) {
            $subscriptions->changePlan($company->subscription, Plan::findOrFail($request->validated('plan_id')));
        }

        return redirect()->route('admin.companies.index')->with('success', __('Company updated.'));
    }
}
