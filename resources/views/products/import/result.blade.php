@extends('layouts/layoutMaster')

@php($created = count($result['created']))

@section('title', __('Import finished'))

@section('content')
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card mb-6">
        <div class="card-body text-center py-8">
          <i class="icon-base ti tabler-circle-check text-success mb-3" style="font-size:3rem"></i>
          <h4 class="mb-1">{{ trans_choice(':count product imported|:count products imported', $created) }}</h4>
          <p class="text-body-secondary mb-6">{{ __('Each one has its own SKU and permanent QR code. Print the labels and stick them on.') }}</p>

          <div class="d-flex flex-wrap justify-content-center gap-2">
            @foreach ($labelBatches as $i => $batch)
              <a href="{{ route('labels.index', ['products' => $batch]) }}" class="btn btn-primary">
                <i class="icon-base ti tabler-printer me-1"></i>
                @if (count($labelBatches) > 1)
                  {{ __('Print labels :from–:to', ['from' => $i * 200 + 1, 'to' => $i * 200 + count($batch)]) }}
                @else
                  {{ __('Print labels') }}
                @endif
              </a>
            @endforeach
            <a href="{{ route('products.index') }}" class="btn btn-label-secondary">{{ __('Go to products') }}</a>
            <a href="{{ route('products.import.create') }}" class="btn btn-label-secondary">{{ __('Import another file') }}</a>
          </div>
        </div>
      </div>

      @if ($result['over_limit'] > 0)
        <div class="alert alert-warning">
          {{ trans_choice(':count ready row was not imported because your plan limit was reached.|:count ready rows were not imported because your plan limit was reached.', $result['over_limit']) }}
          <a href="{{ route('billing.show') }}">{{ __('See plans') }}</a>
        </div>
      @endif

      @if ($result['failed'])
        <div class="card">
          <div class="card-header">
            <h5 class="card-title mb-1 text-danger">{{ __('Not imported') }}</h5>
            <small class="text-body-secondary">{{ __('These rows changed status since the preview (for example, the serial number was registered meanwhile).') }}</small>
          </div>
          <div class="table-responsive">
            <table class="table table-sm">
              <thead><tr><th style="width:1%">{{ __('Row') }}</th><th>{{ __('Problems') }}</th></tr></thead>
              <tbody>
                @foreach ($result['failed'] as $error)
                  <tr><td>{{ $error['row'] }}</td><td>{{ implode(' ', $error['messages']) }}</td></tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      @endif
    </div>
  </div>
@endsection
