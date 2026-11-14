{{-- One customer payment with the approve / reject forms. Expects $sale (product loaded). --}}
@php($open = $sale->isOpen())
<div class="card mb-4 {{ $open ? 'border border-warning' : '' }}">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
      <div>
        <h5 class="mb-0">{{ $sale->reference }}</h5>
        <small class="text-body-secondary">{{ $sale->created_at->diffForHumans() }} · {{ $sale->created_at->format('d M Y, H:i') }}</small>
      </div>
      <span class="badge bg-label-{{ $open || $sale->status !== App\Enums\SaleStatus::Pending ? $sale->status->color() : 'secondary' }}">
        {{ $open || $sale->status !== App\Enums\SaleStatus::Pending ? $sale->status->label() : App\Enums\SaleStatus::Expired->label() }}
      </span>
    </div>

    <dl class="row mb-0 small">
      <dt class="col-5 text-body-secondary fw-normal">{{ __('Customer') }}</dt>
      <dd class="col-7">{{ $sale->customer_name }}<br><a href="tel:+{{ $sale->customer_phone }}">+{{ $sale->customer_phone }}</a></dd>
      <dt class="col-5 text-body-secondary fw-normal">{{ __('Paid with') }}</dt>
      <dd class="col-7">
        {{ $sale->method->label() }}
        @if ($sale->network)
          · {{ $sale->networkName() }} ({{ __('Lipa Namba') }} {{ $sale->payment_number }})
        @endif
      </dd>
      @if ($sale->transaction_code)
        <dt class="col-5 text-body-secondary fw-normal">{{ __('Transaction code') }}</dt>
        <dd class="col-7"><code class="fs-6">{{ $sale->transaction_code }}</code></dd>
      @endif
      <dt class="col-5 text-body-secondary fw-normal">{{ __('Listed price') }}</dt>
      <dd class="col-7">{{ money($sale->listed_price) }}</dd>
      <dt class="col-5 text-body-secondary fw-normal">{{ __('Customer says they paid') }}</dt>
      <dd class="col-7 fw-medium {{ $sale->amount < $sale->listed_price ? 'text-warning' : '' }}">
        {{ money($sale->amount) }}
        @if ($sale->amount < $sale->listed_price)
          <small class="d-block">{{ __(':amount below the listed price', ['amount' => money($sale->listed_price - $sale->amount)]) }}</small>
        @endif
      </dd>
      @if ($sale->received_amount !== null)
        <dt class="col-5 text-body-secondary fw-normal">{{ __('Amount received') }}</dt>
        <dd class="col-7 fw-medium">{{ money($sale->received_amount) }}</dd>
      @endif
      @if ($sale->reviewer)
        <dt class="col-5 text-body-secondary fw-normal">{{ __('Reviewed by') }}</dt>
        <dd class="col-7">{{ $sale->reviewer->name }} · {{ $sale->reviewed_at?->format('d M, H:i') }}</dd>
      @endif
      @if ($sale->note)
        <dt class="col-5 text-body-secondary fw-normal">{{ __('Note') }}</dt>
        <dd class="col-7">{{ $sale->note }}</dd>
      @endif
    </dl>

    @if ($open)
      @can('review', $sale)
        <hr>
        <form method="POST" action="{{ route('sales.approve', $sale) }}" onsubmit="this.querySelector('button').disabled = true">
          @csrf
          <label class="form-label" for="received_{{ $sale->id }}">{{ __('Amount received (TZS)') }} *</label>
          <input type="text" inputmode="numeric" id="received_{{ $sale->id }}" name="received_amount" value="{{ old('received_amount', $sale->amount) }}" class="form-control form-control-lg mb-1 @error('received_amount') is-invalid @enderror" required>
          <small class="text-body-secondary d-block mb-3">
            {{ $sale->method === App\Enums\SaleMethod::Mobile ? __('Check the payment SMS on the shop phone for this transaction code and enter the amount that arrived.') : __('Enter the cash you received from the customer.') }}
          </small>
          <input type="text" name="note" maxlength="255" class="form-control mb-3" placeholder="{{ __('Note (optional)') }}">
          <button type="submit" class="btn btn-success btn-lg w-100"><i class="icon-base ti tabler-check me-1"></i>{{ __('Approve and hand over') }}</button>
        </form>
        <form method="POST" action="{{ route('sales.reject', $sale) }}" class="mt-2" onsubmit="return confirm(@js(__('Reject this payment? The customer will see that the shop could not confirm it.')))">
          @csrf
          <button type="submit" class="btn btn-label-danger w-100">{{ __('Reject') }}</button>
        </form>
      @endcan
    @endif
  </div>
</div>
