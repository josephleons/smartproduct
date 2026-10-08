<?php

namespace App\Jobs;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Scopes\CompanyScope;
use App\Models\Subscription;
use App\Services\Billing\PaymentGatewayManager;
use App\Services\Billing\PaymentProcessor;
use App\Services\Billing\SubscriptionManager;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Scheduled billing sweep (hourly, see routes/console.php):
 *  1. ask each pending payment's gateway whether it was paid, and settle bills that are now paid;
 *  2. issue next month's bill when it is close to due;
 *  3. mark unpaid bills overdue;
 *  4. recalculate every subscription status (active -> past due -> read-only).
 * Runs without a user, so tenant scopes are bypassed explicitly.
 */
class CheckSubscriptions implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $uniqueFor = 3600;

    public function handle(SubscriptionManager $subscriptions, PaymentProcessor $payments, PaymentGatewayManager $gateways): void
    {
        Payment::withoutGlobalScope(CompanyScope::class)
            ->where('status', PaymentStatus::Pending)
            ->orderBy('id')
            ->each(function (Payment $payment) use ($payments, $gateways) {
                try {
                    match ($gateways->driver($payment->gateway)->check($payment)) {
                        PaymentStatus::Confirmed => $payments->confirm($payment),
                        PaymentStatus::Failed => $payments->reject($payment, note: __('Rejected by the payment provider.')),
                        PaymentStatus::Pending => null,
                    };
                } catch (Throwable $e) {
                    // One provider error must not stop the rest of the sweep.
                    Log::warning('Payment check failed', ['payment_id' => $payment->id, 'error' => $e->getMessage()]);
                }
            });

        Subscription::withoutGlobalScope(CompanyScope::class)
            ->with('plan')
            ->chunkById(200, function ($chunk) use ($subscriptions) {
                foreach ($chunk as $subscription) {
                    $subscriptions->issueDueInvoice($subscription);
                    $subscriptions->refreshStatus($subscription->refresh());
                }
            });

        $subscriptions->markOverdueInvoices();
    }
}
