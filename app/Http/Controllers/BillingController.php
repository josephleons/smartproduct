<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PlanFeature;
use App\Http\Requests\PaymentRequest;
use App\Models\Invoice;
use App\Services\Billing\BillingSettings;
use App\Services\Billing\PlanLimits;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Company owner: plan, usage, bills, and "I have paid" submissions. */
class BillingController extends Controller
{
    public function show(Request $request, PlanLimits $limits, BillingSettings $settings): View
    {
        $company = $request->user()->company;
        $this->authorize('update', $company);

        return view('settings.billing', [
            'subscription' => $company->subscription?->load('plan'),
            'usage' => $limits->usage($company),
            'features' => PlanFeature::cases(),
            'invoices' => Invoice::query()->with('payments')->latest('period_start')->paginate(12),
            'methods' => PaymentMethod::cases(),
            'instructions' => $settings->get('payment_instructions'),
        ]);
    }

    public function submitPayment(PaymentRequest $request, Invoice $invoice, BillingSettings $settings): RedirectResponse
    {
        $this->authorize('update', $request->user()->company);

        if (! $invoice->isOpen()) {
            return back()->with('error', __('This bill is already settled.'));
        }

        if ($invoice->payments()->where('status', PaymentStatus::Pending)->exists()) {
            return back()->with('error', __('A payment for this bill is already waiting for confirmation.'));
        }

        $invoice->payments()->create([
            ...$request->validated(),
            'company_id' => $invoice->company_id,
            'gateway' => $settings->get('gateway'),
            'status' => PaymentStatus::Pending,
            'submitted_by' => $request->user()->id,
        ]);

        return back()->with('success', __('Thank you! We will confirm your payment shortly.'));
    }
}
