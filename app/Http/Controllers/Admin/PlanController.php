<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PlanFeature;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PlanRequest;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

/** Superadmin: subscription plans, their monthly price and scope of usage. */
class PlanController extends Controller
{
    public function index(): View
    {
        return view('admin.plans.index', [
            'plans' => Plan::ordered()->withCount('subscriptions')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.plans.form', ['plan' => new Plan(['is_active' => true, 'features' => []]), 'features' => PlanFeature::cases()]);
    }

    public function store(PlanRequest $request): RedirectResponse
    {
        Plan::create($request->validated() + ['slug' => $this->uniqueSlug($request->validated('name'))]);

        return redirect()->route('admin.plans.index')->with('success', __('Plan created.'));
    }

    public function edit(Plan $plan): View
    {
        return view('admin.plans.form', ['plan' => $plan, 'features' => PlanFeature::cases()]);
    }

    public function update(PlanRequest $request, Plan $plan): RedirectResponse
    {
        $plan->update($request->validated());

        return redirect()->route('admin.plans.index')->with('success', __('Plan updated. New prices apply from the next bill.'));
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'plan';
        $slug = $base;
        $i = 2;

        while (Plan::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
