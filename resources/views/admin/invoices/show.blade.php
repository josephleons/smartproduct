@extends('layouts/layoutMaster')

@section('title', $invoice->number)

@section('content')
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-4 mb-6">
    <div>
      <h4 class="mb-1">{{ $invoice->number }} <x-money-badge :status="$invoice->status" /></h4>
      <p class="mb-0 text-body-secondary">
        <a href="{{ route('admin.companies.edit', $invoice->company) }}">{{ $invoice->company->name }}</a> ·
        {{ $invoice->plan_name }} · {{ $invoice->period_start->format('d M Y') }} – {{ $invoice->period_end->format('d M Y') }}
      </p>
    </div>
    <a href="{{ route('admin.invoices.index') }}" class="btn btn-label-secondary">{{ __('Back') }}</a>
  </div>

  <div class="row g-6">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-header"><h5 class="card-title mb-0">{{ __('Payments') }}</h5></div>
        <div class="table-responsive">
          <table class="table">
            <thead><tr><th>{{ __('Submitted') }}</th><th>{{ __('Method / reference') }}</th><th class="text-end">{{ __('Amount') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
            <tbody>
              @forelse ($invoice->payments as $payment)
                <tr>
                  <td class="text-nowrap">{{ $payment->created_at->format('d M Y H:i') }}<br><small class="text-body-secondary">{{ $payment->submitter?->name }}</small></td>
                  <td>{{ $payment->method->label() }}<br><code>{{ $payment->reference }}</code>
                    @if ($payment->note)<br><small class="text-body-secondary">{{ $payment->note }}</small>@endif
                  </td>
                  <td class="text-end text-nowrap">{{ money($payment->amount) }}</td>
                  <td><x-money-badge :status="$payment->status" />
                    @if ($payment->reviewer)<br><small class="text-body-secondary">{{ $payment->reviewer->name }}</small>@endif
                  </td>
                  <td class="text-end text-nowrap">
                    @if ($payment->status === App\Enums\PaymentStatus::Pending)
                      <form method="POST" action="{{ route('admin.payments.confirm', $payment->id) }}" class="d-inline">
                        @csrf
                        <button class="btn btn-sm btn-success" type="submit">{{ __('Confirm') }}</button>
                      </form>
                      <form method="POST" action="{{ route('admin.payments.reject', $payment->id) }}" class="d-inline" onsubmit="const r = prompt(@js(__('Reason for rejecting?'))); if (!r) return false; this.note.value = r;">
                        @csrf
                        <input type="hidden" name="note">
                        <button class="btn btn-sm btn-label-danger" type="submit">{{ __('Reject') }}</button>
                      </form>
                    @endif
                  </td>
                </tr>
              @empty
                <tr><td colspan="5" class="text-center text-body-secondary py-6">{{ __('No payments yet.') }}</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card mb-6">
        <div class="card-body">
          <div class="d-flex justify-content-between mb-2"><span>{{ __('Amount') }}</span><strong>{{ money($invoice->amount) }}</strong></div>
          <div class="d-flex justify-content-between mb-2"><span>{{ __('Confirmed') }}</span><span>{{ money($invoice->confirmedAmount()) }}</span></div>
          <div class="d-flex justify-content-between"><span>{{ __('Balance') }}</span><strong>{{ money($invoice->balance()) }}</strong></div>
          <hr>
          <div class="d-flex justify-content-between"><span>{{ __('Due') }}</span><span>{{ $invoice->due_date->format('d M Y') }}</span></div>
          @if ($invoice->paid_at)
            <div class="d-flex justify-content-between"><span>{{ __('Paid') }}</span><span>{{ $invoice->paid_at->format('d M Y') }}</span></div>
          @endif
        </div>
      </div>

      @if ($invoice->isOpen())
        <form method="POST" action="{{ route('admin.invoices.payments.store', $invoice->id) }}" class="card">
          @csrf
          <div class="card-header"><h6 class="card-title mb-0">{{ __('Record a payment received') }}</h6></div>
          <div class="card-body">
            @include('billing._payment-fields', ['amount' => $invoice->balance()])
            <div class="mt-4">
              <label class="form-label" for="note">{{ __('Note') }}</label>
              <input type="text" id="note" name="note" class="form-control">
            </div>
          </div>
          <div class="card-footer"><button class="btn btn-primary w-100" type="submit">{{ __('Record and confirm') }}</button></div>
        </form>
      @endif
    </div>
  </div>
@endsection
