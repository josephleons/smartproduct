<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentRequest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Scopes\CompanyScope;
use App\Services\Billing\PaymentProcessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Superadmin: every company's bills and payments. Admin code reads across
 * tenants, so the company scope is bypassed explicitly.
 */
class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $invoices = Invoice::withoutGlobalScope(CompanyScope::class)
            ->with('company')
            ->withCount(['payments as pending_payments_count' => fn ($q) => $q->withoutGlobalScope(CompanyScope::class)->where('status', PaymentStatus::Pending)])
            ->when($request->string('status')->value(), fn ($q, $status) => $q->where('status', $status))
            ->when($request->integer('company'), fn ($q, $companyId) => $q->where('company_id', $companyId))
            ->when($request->boolean('awaiting'), fn ($q) => $q->whereHas('payments', fn ($p) => $p->withoutGlobalScope(CompanyScope::class)->where('status', PaymentStatus::Pending)))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $awaiting = Payment::withoutGlobalScope(CompanyScope::class)->where('status', PaymentStatus::Pending)->count();

        return view('admin.invoices.index', [
            'invoices' => $invoices,
            'statuses' => InvoiceStatus::cases(),
            'awaiting' => $awaiting,
        ]);
    }

    public function show(int $invoice): View
    {
        $invoice = $this->findInvoice($invoice);

        return view('admin.invoices.show', [
            'invoice' => $invoice->load(['company', 'payments' => fn ($q) => $q->withoutGlobalScope(CompanyScope::class)->with(['submitter', 'reviewer'])]),
            'methods' => PaymentMethod::cases(),
        ]);
    }

    public function recordPayment(PaymentRequest $request, int $invoice, PaymentProcessor $payments): RedirectResponse
    {
        $invoice = $this->findInvoice($invoice);
        abort_unless($invoice->isOpen(), 422, __('This invoice is already settled.'));

        $payments->record($invoice, $request->validated(), $request->user());

        return back()->with('success', __('Payment recorded.'));
    }

    public function confirm(Request $request, int $payment, PaymentProcessor $payments): RedirectResponse
    {
        $payments->confirm($this->findPayment($payment), $request->user(), $request->input('note'));

        return back()->with('success', __('Payment confirmed.'));
    }

    public function reject(Request $request, int $payment, PaymentProcessor $payments): RedirectResponse
    {
        $request->validate(['note' => ['required', 'string', 'max:255']]);

        $payments->reject($this->findPayment($payment), $request->user(), $request->input('note'));

        return back()->with('success', __('Payment rejected.'));
    }

    private function findInvoice(int $id): Invoice
    {
        return Invoice::withoutGlobalScope(CompanyScope::class)->findOrFail($id);
    }

    private function findPayment(int $id): Payment
    {
        return Payment::withoutGlobalScope(CompanyScope::class)->findOrFail($id);
    }
}
