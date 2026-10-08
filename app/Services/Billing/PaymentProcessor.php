<?php

namespace App\Services\Billing;

use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Scopes\CompanyScope;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** Confirms or rejects payments and settles the invoice once it is fully paid. */
class PaymentProcessor
{
    public function __construct(private SubscriptionManager $subscriptions) {}

    public function confirm(Payment $payment, ?User $reviewer = null, ?string $note = null): void
    {
        DB::transaction(function () use ($payment, $reviewer, $note) {
            if ($payment->status !== PaymentStatus::Pending) {
                return;
            }

            $payment->update([
                'status' => PaymentStatus::Confirmed,
                'confirmed_at' => now(),
                'reviewed_by' => $reviewer?->id,
                'note' => $note ?? $payment->note,
            ]);

            $invoice = Invoice::withoutGlobalScope(CompanyScope::class)->findOrFail($payment->invoice_id);

            if ($invoice->isOpen() && $invoice->balance() === 0) {
                $this->subscriptions->markPaid($invoice);
            }
        });
    }

    public function reject(Payment $payment, ?User $reviewer = null, ?string $note = null): void
    {
        if ($payment->status !== PaymentStatus::Pending) {
            return;
        }

        $payment->update([
            'status' => PaymentStatus::Failed,
            'reviewed_by' => $reviewer?->id,
            'note' => $note,
        ]);
    }

    /** A payment the superadmin received directly (cash, bank): recorded and confirmed at once. */
    public function record(Invoice $invoice, array $data, User $reviewer): Payment
    {
        $payment = Payment::withoutGlobalScope(CompanyScope::class)->create([
            'company_id' => $invoice->company_id,
            'invoice_id' => $invoice->id,
            'amount' => $data['amount'],
            'gateway' => 'manual',
            'method' => $data['method'],
            'reference' => $data['reference'] ?? null,
            'status' => PaymentStatus::Pending,
            'submitted_by' => $reviewer->id,
        ]);

        $this->confirm($payment, $reviewer, $data['note'] ?? null);

        return $payment;
    }
}
