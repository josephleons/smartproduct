<?php

namespace App\Services\Billing;

use App\Enums\InvoiceStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Scopes\CompanyScope;
use App\Models\Subscription;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Monthly subscription lifecycle. Access comes from one date, paid_until:
 *   today <= paid_until                    -> Trial / Active (full access)
 *   paid_until < today <= + grace_days     -> PastDue (full access, warning)
 *   later                                  -> ReadOnly (view only)
 * Runs from jobs and the admin area, so tenant scopes are bypassed explicitly.
 */
class SubscriptionManager
{
    public function __construct(private BillingSettings $settings) {}

    public function start(Company $company, Plan $plan): Subscription
    {
        $today = CarbonImmutable::today();
        $trialDays = (int) $this->settings->get('trial_days');

        $subscription = Subscription::withoutGlobalScope(CompanyScope::class)->create([
            'company_id' => $company->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Trial,
            'paid_until' => $trialDays > 0 ? $today->addDays($trialDays - 1) : $today->subDay(),
            'trial_ends_at' => $trialDays > 0 ? $today->addDays($trialDays - 1) : null,
        ]);

        $this->issueDueInvoice($subscription);

        // A free (zero) bill is settled inside issueDueInvoice, so reload before recalculating.
        return $this->refreshStatus($subscription->refresh());
    }

    public function changePlan(Subscription $subscription, Plan $plan): void
    {
        // Takes effect from the next bill; an already issued bill keeps its price.
        $subscription->update(['plan_id' => $plan->id]);
    }

    public function refreshStatus(Subscription $subscription, ?CarbonImmutable $today = null): Subscription
    {
        $today ??= CarbonImmutable::today();
        $paidUntil = CarbonImmutable::parse($subscription->paid_until);
        $graceEnds = $paidUntil->addDays((int) $this->settings->get('grace_days'));

        $status = match (true) {
            $today->lte($paidUntil) && $subscription->trial_ends_at && $paidUntil->lte($subscription->trial_ends_at) => SubscriptionStatus::Trial,
            $today->lte($paidUntil) => SubscriptionStatus::Active,
            $today->lte($graceEnds) => SubscriptionStatus::PastDue,
            default => SubscriptionStatus::ReadOnly,
        };

        $subscription->update([
            'status' => $status,
            'grace_ends_at' => $today->gt($paidUntil) ? $graceEnds : null,
        ]);

        return $subscription;
    }

    /** Issue the next monthly bill when it is close to due. One open bill at a time. */
    public function issueDueInvoice(Subscription $subscription, ?CarbonImmutable $today = null): ?Invoice
    {
        $today ??= CarbonImmutable::today();

        if ($this->invoices($subscription)->whereIn('status', InvoiceStatus::open())->exists()) {
            return null;
        }

        $nextStart = CarbonImmutable::parse($subscription->paid_until)->addDay();
        if ($today->lt($nextStart->subDays((int) $this->settings->get('invoice_days_before')))) {
            return null;
        }

        // A company that lapsed long ago is billed from today, not for the months it was read-only.
        $periodStart = $nextStart->max($today);
        $plan = $subscription->plan;

        $invoice = Invoice::withoutGlobalScope(CompanyScope::class)->create([
            'company_id' => $subscription->company_id,
            'subscription_id' => $subscription->id,
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'amount' => $plan->monthly_price,
            'period_start' => $periodStart,
            'period_end' => $periodStart->addMonthNoOverflow()->subDay(),
            'due_date' => $periodStart,
            'status' => InvoiceStatus::Pending,
        ]);

        if ($invoice->amount === 0) {
            $this->markPaid($invoice);
        }

        return $invoice;
    }

    /**
     * Settle an invoice and extend paid_until by one month. If the company had
     * lapsed, the new month starts today so they do not pay for time they could not use.
     */
    public function markPaid(Invoice $invoice): void
    {
        DB::transaction(function () use ($invoice) {
            $subscription = Subscription::withoutGlobalScope(CompanyScope::class)->lockForUpdate()->findOrFail($invoice->subscription_id);
            $today = CarbonImmutable::today();

            $start = CarbonImmutable::parse($subscription->paid_until)->addDay()->max($today);
            $end = $start->addMonthNoOverflow()->subDay();

            $invoice->update([
                'status' => InvoiceStatus::Paid,
                'paid_at' => now(),
                'period_start' => $start,
                'period_end' => $end,
            ]);

            $subscription->update(['paid_until' => $end, 'trial_ends_at' => null]);
            $this->refreshStatus($subscription);
        });
    }

    /** Pending bills past their due date become overdue. */
    public function markOverdueInvoices(?CarbonImmutable $today = null): int
    {
        return Invoice::withoutGlobalScope(CompanyScope::class)
            ->where('status', InvoiceStatus::Pending)
            ->whereDate('due_date', '<', $today ?? CarbonImmutable::today())
            ->update(['status' => InvoiceStatus::Overdue]);
    }

    private function invoices(Subscription $subscription)
    {
        return Invoice::withoutGlobalScope(CompanyScope::class)->where('subscription_id', $subscription->id);
    }
}
