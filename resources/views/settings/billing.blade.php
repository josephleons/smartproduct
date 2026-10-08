@extends('layouts/layoutMaster')

@section('title', __('Billing'))

@section('content')
  <h4 class="mb-6">{{ __('Billing') }}</h4>

  <div class="row g-6 mb-6">
    <div class="col-lg-5">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0">{{ __('Your plan') }}</h5>
          <x-subscription-badge :status="$subscription?->status" />
        </div>
        <div class="card-body">
          @if ($subscription)
            <h3 class="mb-1">{{ $subscription->plan->name }}</h3>
            <p class="text-primary fw-medium mb-4">{{ money($subscription->plan->monthly_price) }} / {{ __('month') }}</p>
            <p class="mb-1">
              @if ($subscription->status === App\Enums\SubscriptionStatus::Trial)
                {{ __('Free trial until :date', ['date' => $subscription->paid_until->format('d M Y')]) }}
              @else
                {{ __('Paid until :date', ['date' => $subscription->paid_until->format('d M Y')]) }}
              @endif
            </p>
            @if ($subscription->grace_ends_at && $subscription->status === App\Enums\SubscriptionStatus::PastDue)
              <p class="text-warning mb-0">{{ __('Pay before :date to avoid read-only mode.', ['date' => $subscription->grace_ends_at->format('d M Y')]) }}</p>
            @endif
            <hr>
            @foreach ($usage as $resource => $row)
              @php($pct = $row['limit'] ? min(100, round($row['used'] / $row['limit'] * 100)) : 0)
              <div class="mb-3">
                <div class="d-flex justify-content-between small"><span>{{ __(ucfirst($resource)) }}</span><span>{{ $row['used'] }} / {{ $row['limit'] ?? __('Unlimited') }}</span></div>
                @if ($row['limit'])
                  <div class="progress" style="height:6px"><div class="progress-bar {{ $pct >= 90 ? 'bg-danger' : '' }}" style="width: {{ $pct }}%"></div></div>
                @endif
              </div>
            @endforeach
            <hr>
            @foreach ($features as $feature)
              <div class="small mb-1 {{ $subscription->plan->hasFeature($feature) ? '' : 'text-body-secondary' }}">
                <i class="icon-base ti {{ $subscription->plan->hasFeature($feature) ? 'tabler-check text-success' : 'tabler-lock' }} me-1"></i>{{ $feature->label() }}
              </div>
            @endforeach
            <p class="small text-body-secondary mt-4 mb-0">{{ __('To change your plan, contact SmartLabel support.') }}</p>
          @else
            <p class="mb-0">{{ __('No subscription yet. Please contact SmartLabel support.') }}</p>
          @endif
        </div>
      </div>
    </div>

    <div class="col-lg-7">
      <div class="card h-100">
        <div class="card-header"><h5 class="card-title mb-0">{{ __('How to pay') }}</h5></div>
        <div class="card-body">
          @if ($instructions)
            <div class="bg-soft-teal rounded p-4 mb-4" style="white-space: pre-line">{{ $instructions }}</div>
          @endif
          @php($open = $invoices->first(fn ($i) => $i->isOpen()))
          @if ($open)
            @php($pending = $open->payments->firstWhere('status', App\Enums\PaymentStatus::Pending))
            <p class="mb-4">{{ __('Bill :number for :amount is due on :date.', ['number' => $open->number, 'amount' => money($open->amount), 'date' => $open->due_date->format('d M Y')]) }}</p>
            @if ($pending)
              <div class="alert alert-info mb-0">{{ __('We received your payment details (:ref) and will confirm shortly.', ['ref' => $pending->reference]) }}</div>
            @else
              <form method="POST" action="{{ route('billing.payments.store', $open) }}">
                @csrf
                <p class="small text-body-secondary">{{ __('After paying, enter the transaction number so we can confirm it.') }}</p>
                @include('billing._payment-fields', ['amount' => $open->balance()])
                <button class="btn btn-primary mt-4" type="submit">{{ __('I have paid') }}</button>
              </form>
            @endif
          @else
            <p class="mb-0 text-body-secondary">{{ __('You have no unpaid bills.') }}</p>
          @endif
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h5 class="card-title mb-0">{{ __('Bills') }}</h5></div>
    <div class="table-responsive">
      <table class="table">
        <thead><tr><th>{{ __('Bill') }}</th><th>{{ __('Period') }}</th><th>{{ __('Due') }}</th><th class="text-end">{{ __('Amount') }}</th><th>{{ __('Status') }}</th></tr></thead>
        <tbody>
          @forelse ($invoices as $invoice)
            <tr>
              <td>{{ $invoice->number }}<br><small class="text-body-secondary">{{ $invoice->plan_name }}</small></td>
              <td class="text-nowrap">{{ $invoice->period_start->format('d M') }} – {{ $invoice->period_end->format('d M Y') }}</td>
              <td class="text-nowrap">{{ $invoice->due_date->format('d M Y') }}</td>
              <td class="text-end text-nowrap">{{ money($invoice->amount) }}</td>
              <td><x-money-badge :status="$invoice->status" /></td>
            </tr>
          @empty
            <tr><td colspan="5" class="text-center text-body-secondary py-6">{{ __('No bills yet.') }}</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="card-body">{{ $invoices->links() }}</div>
  </div>
@endsection
