@extends('layouts/layoutMaster')

@section('title', __('Payments for :product', ['product' => $product->name()]))

@section('page-script')
  @vite(['resources/assets/js/smartlabel-scanner.js'])
@endsection

@section('content')
  <div class="d-flex justify-content-between align-items-center gap-2 mb-4">
    <a href="{{ route('sales.index') }}" class="btn btn-label-secondary"><i class="icon-base ti tabler-arrow-left me-1"></i>{{ __('Payments') }}</a>
    <a href="{{ route('products.show', $product) }}" class="btn btn-text-secondary">{{ __('Product details') }}</a>
  </div>

  @error('sale') <div class="alert alert-danger">{{ $message }}</div> @enderror

  <div class="row g-6">
    <div class="col-lg-5">
      {{-- The device, so staff can check it is the one in front of them. --}}
      <div class="card mb-6">
        <div class="card-body">
          <div class="d-flex align-items-center gap-2 mb-1">
            <h5 class="mb-0">{{ $product->name() }}</h5>
            <x-status-badge :status="$product->status" />
          </div>
          <p class="text-body-secondary small mb-3">{{ $product->sku }} · {{ __($product->category->name) }} · {{ $product->branch->name }}</p>
          <div class="spec-grid">
            <div><small class="text-body-secondary d-block">{{ __('Price') }}</small><span class="fw-medium">{{ money($product->price) }}</span></div>
            <div><small class="text-body-secondary d-block">{{ __('In stock') }}</small><span class="fw-medium">{{ $product->quantity }}</span></div>
            <div><small class="text-body-secondary d-block">{{ __('Condition') }}</small><span class="fw-medium">{{ $product->condition->label() }}</span></div>
            @if ($product->serial_number)
              <div><small class="text-body-secondary d-block">{{ __('Serial number') }}</small><span class="fw-medium">{{ $product->serial_number }}</span></div>
            @endif
            @foreach ($product->specs->where('show_on_label', true) as $spec)
              <div><small class="text-body-secondary d-block">{{ __($spec->label) }}</small><span class="fw-medium">{{ $spec->displayValue() }}</span></div>
            @endforeach
          </div>
        </div>
      </div>
      <div class="d-none d-lg-block">@include('sales._scanner')</div>
    </div>

    <div class="col-lg-7">
      <h5 class="mb-3">{{ __('Waiting for approval') }} <span class="badge bg-label-warning">{{ $openSales->count() }}</span></h5>
      @forelse ($openSales as $sale)
        @include('sales._review', ['sale' => $sale])
      @empty
        <div class="card mb-6"><div class="card-body text-center text-body-secondary py-6">
          {{ __('No payment is waiting for this product. Ask the customer to tap "Pay for this item" on the QR page and confirm.') }}
        </div></div>
      @endforelse

      @if ($history->isNotEmpty())
        <h6 class="mt-6 mb-3 text-body-secondary">{{ __('Earlier payments') }}</h6>
        @foreach ($history as $sale)
          @include('sales._review', ['sale' => $sale])
        @endforeach
      @endif
    </div>
  </div>
@endsection
