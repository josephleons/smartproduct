@extends('layouts/layoutMaster')

@section('title', __('Payments'))

@section('page-script')
  @vite(['resources/assets/js/smartlabel-scanner.js'])
@endsection

@section('content')
  <div class="mb-6">
    <h4 class="mb-1">{{ __('Payments') }}</h4>
    <p class="mb-0 text-body-secondary">{{ __('Customers confirm payments on the QR page. Scan the sticker to check and approve.') }}</p>
  </div>

  @error('sale') <div class="alert alert-danger">{{ $message }}</div> @enderror

  <div class="row g-6">
    <div class="col-lg-4">
      @include('sales._scanner')
    </div>

    <div class="col-lg-8">
      <ul class="nav nav-pills flex-wrap gap-1 mb-4">
        @foreach (App\Http\Controllers\SaleController::TABS as $key)
          <li class="nav-item">
            <a class="nav-link {{ $tab === $key ? 'active' : '' }}" href="{{ route('sales.index', ['status' => $key]) }}">
              {{ match ($key) {
                  'pending' => __('Waiting'),
                  'approved' => __('Approved'),
                  'rejected' => __('Rejected'),
                  'expired' => __('Expired'),
                  'all' => __('All'),
              } }}
              @if ($key === 'pending' && $pendingCount)
                <span class="badge rounded-pill bg-warning ms-1">{{ $pendingCount }}</span>
              @endif
            </a>
          </li>
        @endforeach
      </ul>

      <div class="card">
        <div class="list-group list-group-flush">
          @forelse ($sales as $sale)
            @php($expired = $sale->status === App\Enums\SaleStatus::Pending && ! $sale->isOpen())
            <a href="{{ route('sales.product', $sale->product?->qrCode?->token ?? $sale->product?->sku ?? '-') }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-3 py-3">
              <div class="min-w-0">
                <div class="fw-medium text-heading text-truncate">{{ $sale->product?->name() }}</div>
                <small class="text-body-secondary">
                  {{ $sale->reference }} · {{ $sale->customer_name }} · {{ $sale->networkName() ?? $sale->method->label() }} · {{ $sale->created_at->diffForHumans() }}
                  @if (! auth()->user()->branch_id) · {{ $sale->branch?->name }} @endif
                </small>
              </div>
              <div class="text-end text-nowrap">
                <div class="fw-medium">{{ money($sale->received_amount ?? $sale->amount) }}</div>
                <span class="badge bg-label-{{ $expired ? 'secondary' : $sale->status->color() }}">{{ $expired ? App\Enums\SaleStatus::Expired->label() : $sale->status->label() }}</span>
              </div>
            </a>
          @empty
            <div class="list-group-item text-center text-body-secondary py-8">
              {{ $tab === 'pending' ? __('No payments waiting. New ones appear here when a customer confirms on the QR page.') : __('No payments found.') }}
            </div>
          @endforelse
        </div>
        @if ($sales->hasPages())
          <div class="card-body">{{ $sales->links() }}</div>
        @endif
      </div>
    </div>
  </div>
@endsection
