@extends('layouts/layoutMaster')

@section('title', __('Payment :reference', ['reference' => $sale->reference]))

@section('content')
  <div class="row justify-content-center">
    <div class="col-lg-6">
      <a href="{{ route('sales.index') }}" class="btn btn-label-secondary mb-4"><i class="icon-base ti tabler-arrow-left me-1"></i>{{ __('Payments') }}</a>
      @error('sale') <div class="alert alert-danger">{{ $message }}</div> @enderror
      <h5 class="mb-3">{{ $sale->product?->name() }} <small class="text-body-secondary">{{ $sale->product?->sku }}</small></h5>
      @include('sales._review', ['sale' => $sale])
    </div>
  </div>
@endsection
