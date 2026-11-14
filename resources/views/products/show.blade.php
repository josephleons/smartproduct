@extends('layouts/layoutMaster')

@section('title', $product->name())

@section('content')
  <div class="d-flex flex-wrap justify-content-between align-items-start gap-4 mb-6">
    <div>
      <div class="d-flex align-items-center gap-2 mb-1">
        <h4 class="mb-0">{{ $product->name() }}</h4>
        <x-status-badge :status="$product->status" />
      </div>
      <p class="mb-0 text-body-secondary">{{ $product->sku }} · {{ $product->category->name }} · {{ $product->branch->name }}</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <a href="{{ route('labels.index', ['products' => [$product->id]]) }}" class="btn btn-primary"><i class="icon-base ti tabler-printer me-1"></i>{{ __('Print label') }}</a>
      @can('update', $product)
        <a href="{{ route('products.edit', $product) }}" class="btn btn-label-primary"><i class="icon-base ti tabler-edit me-1"></i>{{ __('Edit') }}</a>
      @endcan
      @can('create', App\Models\Product::class)
        <a href="{{ route('products.create', ['from' => $product->id]) }}" class="btn btn-label-primary" title="{{ __('Add another unit with the same details') }}"><i class="icon-base ti tabler-copy me-1"></i>{{ __('Duplicate') }}</a>
      @endcan
      @can('delete', $product)
        <form method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm(@js(__('Delete this product? Its printed label will show a not-found page.')))">
          @csrf
          @method('DELETE')
          <button class="btn btn-label-danger" type="submit"><i class="icon-base ti tabler-trash"></i></button>
        </form>
      @endcan
    </div>
  </div>

  <div class="row g-6">
    <div class="col-lg-8">
      <div class="card mb-6">
        <div class="card-header"><h5 class="card-title mb-0">{{ __('Details') }}</h5></div>
        <div class="card-body">
          <div class="spec-grid mb-4">
            <div><small class="text-body-secondary d-block">{{ __('Price') }}</small><span class="fw-medium">{{ money($product->price) }}</span></div>
            <div><small class="text-body-secondary d-block">{{ __('Condition') }}</small><span class="fw-medium">{{ $product->condition->label() }}</span></div>
            <div><small class="text-body-secondary d-block">{{ __('Warranty') }}</small><span class="fw-medium">{{ $product->warranty_months ? trans_choice(':count month|:count months', $product->warranty_months) : __('None') }}</span></div>
            <div><small class="text-body-secondary d-block">{{ __('Quantity') }}</small><span class="fw-medium">{{ $product->quantity }}</span></div>
            @if ($product->serial_number)
              <div><small class="text-body-secondary d-block">{{ __('Serial number') }}</small><span class="fw-medium">{{ $product->serial_number }}</span></div>
            @endif
            @foreach ($product->specs as $spec)
              <div><small class="text-body-secondary d-block">{{ __($spec->label) }}</small><span class="fw-medium">{{ $spec->displayValue() }}</span></div>
            @endforeach
          </div>
          @if ($product->description)
            <p class="mb-0">{{ $product->description }}</p>
          @endif
        </div>
      </div>

      @can('update', $product)
        <div class="card">
          <div class="card-header"><h5 class="card-title mb-0">{{ __('Quick update') }}</h5></div>
          <div class="card-body">
            <form method="POST" action="{{ route('products.quick-update', $product) }}" class="row g-4 align-items-end">
              @csrf
              @method('PATCH')
              <div class="col-md-4">
                <label class="form-label" for="q_price">{{ __('Price (TZS)') }}</label>
                <input type="text" inputmode="numeric" id="q_price" name="price" value="{{ old('price', $product->price) }}" class="form-control @error('price') is-invalid @enderror">
                @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="col-md-3">
                <label class="form-label" for="q_quantity">{{ __('Quantity') }}</label>
                <input type="number" min="0" id="q_quantity" name="quantity" value="{{ old('quantity', $product->quantity) }}" class="form-control @error('quantity') is-invalid @enderror">
              </div>
              <div class="col-md-3">
                <label class="form-label" for="q_status">{{ __('Status') }}</label>
                <select id="q_status" name="status" class="form-select">
                  @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected($product->status === $status)>{{ $status->label() }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-2"><button class="btn btn-primary w-100" type="submit">{{ __('Save') }}</button></div>
            </form>
          </div>
        </div>
      @endcan
    </div>

    <div class="col-lg-4">
      <div class="card">
        <div class="card-header"><h5 class="card-title mb-0">{{ __('QR code') }}</h5></div>
        <div class="card-body text-center">
          @if ($qrSvg)
            <div class="qr-thumb mx-auto mb-4" style="max-width: 200px">{!! $qrSvg !!}</div>
            <p class="mb-1"><code>{{ $product->qrCode->token }}</code></p>
            <a href="{{ $product->publicUrl() }}" target="_blank" rel="noopener" class="small d-block mb-4">{{ $product->publicUrl() }}</a>
            <div class="d-flex justify-content-around border-top pt-4">
              <div><h5 class="mb-0">{{ $product->qrCode->scans_count }}</h5><small class="text-body-secondary">{{ __('Scans') }}</small></div>
              <div><h6 class="mb-0">{{ $product->qrCode->last_scanned_at?->diffForHumans() ?? '—' }}</h6><small class="text-body-secondary">{{ __('Last scan') }}</small></div>
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
@endsection
