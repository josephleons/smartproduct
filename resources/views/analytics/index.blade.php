@extends('layouts/layoutMaster')

@section('title', __('Analytics'))

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
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-4 mb-6">
    <h4 class="mb-0">{{ __('Analytics') }}</h4>
    <form method="GET" class="d-flex flex-wrap gap-2">
      <select name="days" class="form-select w-auto" onchange="this.form.submit()">
        @foreach ($periods as $period)
          <option value="{{ $period }}" @selected($filters['days'] === $period)>{{ __('Last :days days', ['days' => $period]) }}</option>
        @endforeach
      </select>
      @if (!auth()->user()->branch_id && $branches->count() > 1)
        <select name="branch" class="form-select w-auto" onchange="this.form.submit()">
          <option value="">{{ __('All branches') }}</option>
          @foreach ($branches as $branch)
            <option value="{{ $branch->id }}" @selected($filters['branch_id'] === $branch->id)>{{ $branch->name }}</option>
          @endforeach
        </select>
      @endif
      <select name="category" class="form-select w-auto" onchange="this.form.submit()">
        <option value="">{{ __('All categories') }}</option>
        @foreach ($categories as $category)
          <option value="{{ $category->id }}" @selected($filters['category_id'] === $category->id)>{{ $category->name }}</option>
        @endforeach
      </select>
    </form>
  </div>

  @php
    $cards = [
        ['label' => __('Total scans'), 'value' => number_format($summary['scans']), 'hint' => __(':count unique', ['count' => number_format($summary['unique_scans'])])],
        ['label' => __('Products scanned'), 'value' => number_format($summary['products_scanned']), 'hint' => __('different products')],
        ['label' => __('WhatsApp clicks'), 'value' => number_format($summary['whatsapp_clicks']), 'hint' => __(':count calls', ['count' => number_format($summary['call_clicks'])])],
        ['label' => __('Scan-to-contact rate'), 'value' => $summary['contact_rate'] . '%', 'hint' => __('WhatsApp + calls ÷ unique scans')],
    ];
  @endphp

  <div class="row g-6 mb-6">
    @foreach ($cards as $card)
      <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
          <div class="card-body">
            <span class="text-heading">{{ $card['label'] }}</span>
            <h4 class="mb-0 mt-1">{{ $card['value'] }}</h4>
            <small class="text-body-secondary">{{ $card['hint'] }}</small>
          </div>
        </div>
      </div>
    @endforeach
  </div>

  <div class="row g-6 mb-6">
    <div class="col-lg-8">
      <div class="card h-100">
        <div class="card-header"><h5 class="card-title mb-0">{{ __('Scans per day') }}</h5></div>
        <div class="card-body">
          <div id="dailyChart" data-chart='@json(['labels' => $summary['daily']->pluck('label'), 'series' => $summary['daily']->pluck('scans'), 'name' => __('Scans')])'></div>
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card h-100">
        <div class="card-header"><h5 class="card-title mb-0">{{ __('Scans by branch') }}</h5></div>
        <div class="card-body">
          <div id="branchChart" data-horizontal="true" data-chart='@json(['labels' => $summary['by_branch']->pluck('name'), 'series' => $summary['by_branch']->pluck('scans'), 'name' => __('Scans')])'></div>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><h5 class="card-title mb-0">{{ __('Most scanned products') }}</h5></div>
    <div class="table-responsive">
      <table class="table">
        <thead><tr><th>{{ __('Product') }}</th><th>{{ __('Branch') }}</th><th class="text-end">{{ __('Scans') }}</th></tr></thead>
        <tbody>
          @forelse ($summary['top_products'] as $row)
            <tr>
              <td><a href="{{ route('products.show', $row->product_id) }}" class="text-heading">{{ $row->product?->name() }}</a></td>
              <td>{{ $row->product?->branch?->name }}</td>
              <td class="text-end">{{ $row->scans }}</td>
            </tr>
          @empty
            <tr><td colspan="3" class="text-center text-body-secondary py-6">{{ __('No scans in this period.') }}</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endsection
