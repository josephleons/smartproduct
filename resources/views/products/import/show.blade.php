@extends('layouts/layoutMaster')

@php
  $ready = count($pending['rows']);
  $withErrors = count($pending['errors']);
  $willImport = $room === null ? $ready : min($ready, $room);
@endphp

@section('title', __('Check import'))

@section('content')
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-4 mb-6">
    <div>
      <h4 class="mb-1">{{ __('Check import') }}</h4>
      <p class="mb-0 text-body-secondary">
        {{ $pending['file_name'] }} · {{ __($category->name) }} · {{ $branch?->name }}
      </p>
    </div>
    <a href="{{ route('products.import.create') }}" class="btn btn-label-secondary">{{ __('Upload a different file') }}</a>
  </div>

  <div class="row g-6 mb-6">
    <div class="col-sm-4">
      <div class="card h-100"><div class="card-body">
        <small class="text-body-secondary d-block">{{ __('Rows in file') }}</small>
        <h4 class="mb-0">{{ number_format($pending['total']) }}</h4>
      </div></div>
    </div>
    <div class="col-sm-4">
      <div class="card h-100"><div class="card-body">
        <small class="text-body-secondary d-block">{{ __('Ready to import') }}</small>
        <h4 class="mb-0 text-success">{{ number_format($ready) }}</h4>
      </div></div>
    </div>
    <div class="col-sm-4">
      <div class="card h-100"><div class="card-body">
        <small class="text-body-secondary d-block">{{ __('Rows with errors') }}</small>
        <h4 class="mb-0 {{ $withErrors ? 'text-danger' : '' }}">{{ number_format($withErrors) }}</h4>
      </div></div>
    </div>
  </div>

  @if ($room !== null && $ready > $room)
    <div class="alert alert-warning">
      {{ __('Your plan has room for :room more products, so only the first :room ready rows will be imported. Upgrade your plan to import all :ready.', ['room' => $room, 'ready' => $ready]) }}
    </div>
  @endif

  @if ($pending['ignored_columns'])
    <div class="alert alert-info">
      {{ __('These columns are not part of the template and were ignored: :columns', ['columns' => implode(', ', $pending['ignored_columns'])]) }}
    </div>
  @endif

  <div class="card mb-6">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-4">
      <div>
        @if ($willImport > 0)
          <h5 class="mb-1">{{ trans_choice('Import :count product|Import :count products', $willImport) }}</h5>
          <p class="mb-0 text-body-secondary">
            {{ __('Each product gets its own SKU and permanent QR code. You can print all the labels on the next screen.') }}
            @if ($withErrors)
              {{ __('Rows with errors are skipped: fix them in your file and upload just those rows again later.') }}
            @endif
          </p>
        @else
          <h5 class="mb-1">{{ __('Nothing to import yet') }}</h5>
          <p class="mb-0 text-body-secondary">{{ __('Fix the rows below in your file and upload it again.') }}</p>
        @endif
      </div>
      @if ($willImport > 0)
        <form method="POST" action="{{ route('products.import.confirm', $import) }}" onsubmit="this.querySelector('button').disabled = true">
          @csrf
          <button type="submit" class="btn btn-success"><i class="icon-base ti tabler-check me-1"></i>{{ trans_choice('Import :count product|Import :count products', $willImport) }}</button>
        </form>
      @endif
    </div>
  </div>

  @if ($withErrors)
    <div class="card mb-6">
      <div class="card-header"><h5 class="card-title mb-0 text-danger">{{ __('Rows with errors') }}</h5></div>
      <div class="table-responsive">
        <table class="table table-sm">
          <thead><tr><th style="width:1%">{{ __('Row') }}</th><th>{{ __('Problems') }}</th></tr></thead>
          <tbody>
            @foreach ($pending['errors'] as $error)
              <tr>
                <td class="fw-medium">{{ $error['row'] }}</td>
                <td>
                  <ul class="mb-0 ps-4">
                    @foreach ($error['messages'] as $message)
                      <li>{{ $message }}</li>
                    @endforeach
                  </ul>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  @endif

  @if ($ready)
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">{{ __('Ready to import') }}</h5>
        @if ($ready > 100)
          <small class="text-body-secondary">{{ __('Showing the first :count rows', ['count' => 100]) }}</small>
        @endif
      </div>
      <div class="table-responsive">
        <table class="table table-sm">
          <thead>
            <tr>
              <th style="width:1%">{{ __('Row') }}</th>
              <th>{{ __('Product') }}</th>
              <th>{{ __('Serial number') }}</th>
              <th>{{ __('Condition') }}</th>
              <th class="text-end">{{ __('Price') }}</th>
              <th>{{ __('Status') }}</th>
            </tr>
          </thead>
          <tbody>
            @foreach (array_slice($pending['rows'], 0, 100, true) as $number => $row)
              <tr>
                <td>{{ $number }}</td>
                <td class="fw-medium">{{ $row['brand'] }} {{ $row['model'] }}</td>
                <td>{{ $row['serial_number'] ?? '—' }}</td>
                <td>{{ App\Enums\ProductCondition::from($row['condition'])->label() }}</td>
                <td class="text-end text-nowrap">{{ money($row['price']) }}</td>
                <td><x-status-badge :status="App\Enums\ProductStatus::from($row['status'])" /></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  @endif
@endsection
