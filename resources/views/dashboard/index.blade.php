@extends('layouts/layoutMaster')

@section('title', __('Dashboard'))

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js'])
@endsection

@section('page-script')
  @vite(['resources/assets/js/smartlabel-charts.js'])
@endsection

@section('content')
  @php
    $cards = [
        ['label' => __('Products'), 'value' => $stats['products'], 'icon' => 'tabler-devices', 'color' => 'primary'],
        ['label' => __('Available'), 'value' => $stats['available'], 'icon' => 'tabler-circle-check', 'color' => 'success'],
        ['label' => __('QR scans (30 days)'), 'value' => $stats['scans'], 'icon' => 'tabler-qrcode', 'color' => 'info'],
        ['label' => __('Branches'), 'value' => $stats['branches'], 'icon' => 'tabler-building-store', 'color' => 'warning'],
    ];
  @endphp

  <div class="row g-6 mb-6">
    @foreach ($cards as $card)
      <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
          <div class="card-body d-flex justify-content-between align-items-center">
            <div>
              <span class="text-heading">{{ $card['label'] }}</span>
              <h4 class="mb-0 mt-1">{{ number_format($card['value']) }}</h4>
            </div>
            <div class="avatar">
              <span class="avatar-initial rounded bg-label-{{ $card['color'] }}">
                <i class="icon-base ti {{ $card['icon'] }} icon-26px"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    @endforeach
  </div>

  <div class="row g-6 mb-6">
    <div class="col-lg-8">
      <div class="card h-100">
        <div class="card-header"><h5 class="card-title mb-0">{{ __('Scans, last 7 days') }}</h5></div>
        <div class="card-body">
          <div id="scansChart" data-chart='@json(['labels' => $daily->pluck('label'), 'series' => $daily->pluck('scans'), 'name' => __('Scans')])'></div>
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card h-100">
        <div class="card-header"><h5 class="card-title mb-0">{{ __('Top viewed products') }}</h5></div>
        <div class="card-body">
          <ul class="list-unstyled mb-0">
            @forelse ($topProducts as $row)
              <li class="d-flex justify-content-between align-items-center mb-4">
                <div class="overflow-hidden">
                  <a href="{{ route('products.show', $row->product_id) }}" class="text-heading d-block text-truncate">{{ $row->product?->name() }}</a>
                  <small class="text-body-secondary">{{ $row->product?->branch?->name }}</small>
                </div>
                <span class="badge bg-label-primary">{{ $row->scans }}</span>
              </li>
            @empty
              <li class="text-body-secondary">{{ __('No scans yet. Print a label and scan it to try.') }}</li>
            @endforelse
          </ul>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="card-title mb-0">{{ __('Recent products') }}</h5>
      <a href="{{ route('products.index') }}" class="btn btn-sm btn-label-primary">{{ __('View all') }}</a>
    </div>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>{{ __('Product') }}</th>
            <th>{{ __('Category') }}</th>
            <th>{{ __('Branch') }}</th>
            <th class="text-end">{{ __('Price') }}</th>
            <th>{{ __('Status') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($recentProducts as $product)
            <tr>
              <td><a href="{{ route('products.show', $product) }}" class="text-heading">{{ $product->name() }}</a><br><small class="text-body-secondary">{{ $product->sku }}</small></td>
              <td>{{ $product->category->name }}</td>
              <td>{{ $product->branch->name }}</td>
              <td class="text-end text-nowrap">{{ money($product->price) }}</td>
              <td><x-status-badge :status="$product->status" /></td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="text-center py-6">
                <p class="mb-3">{{ __('No products yet.') }}</p>
                <a href="{{ route('products.create') }}" class="btn btn-primary">{{ __('Add your first product') }}</a>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endsection
