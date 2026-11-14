@extends('layouts/layoutMaster')

@section('title', __('Products'))

@section('content')
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-4 mb-6">
    <div>
      <h4 class="mb-1">{{ __('Products') }}</h4>
      <p class="mb-0 text-body-secondary">{{ __('Every product gets a permanent QR label.') }}</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <a href="{{ route('products.import.create') }}" class="btn btn-label-primary">
        <i class="icon-base ti tabler-file-import me-1"></i>{{ __('Import from Excel') }}
      </a>
      <a href="{{ route('products.create') }}" class="btn btn-primary">
        <i class="icon-base ti tabler-plus me-1"></i>{{ __('Add product') }}
      </a>
    </div>
  </div>

  <div class="card">
    <div class="card-body border-bottom">
      <form method="GET" class="row g-3">
        <div class="col-md-4">
          <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="{{ __('Search brand, model, SKU or serial') }}">
        </div>
        <div class="col-md-2">
          <select name="category" class="form-select">
            <option value="">{{ __('All categories') }}</option>
            @foreach ($categories as $category)
              <option value="{{ $category->id }}" @selected(request('category') == $category->id)>{{ __($category->name) }}</option>
            @endforeach
          </select>
        </div>
        @if ($branches->count() > 1)
          <div class="col-md-2">
            <select name="branch" class="form-select">
              <option value="">{{ __('All branches') }}</option>
              @foreach ($branches as $branch)
                <option value="{{ $branch->id }}" @selected(request('branch') == $branch->id)>{{ $branch->name }}</option>
              @endforeach
            </select>
          </div>
        @endif
        <div class="col-md-2">
          <select name="status" class="form-select">
            <option value="">{{ __('Any status') }}</option>
            @foreach ($statuses as $status)
              <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
          <button class="btn btn-label-primary flex-grow-1" type="submit">{{ __('Filter') }}</button>
          @if (request()->hasAny(['q', 'category', 'branch', 'status']))
            <a href="{{ route('products.index') }}" class="btn btn-label-secondary" title="{{ __('Clear filters') }}"><i class="icon-base ti tabler-x"></i></a>
          @endif
        </div>
      </form>
    </div>

    <form method="GET" action="{{ route('labels.index') }}" id="bulkLabels">
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th style="width:1%"><input type="checkbox" class="form-check-input" data-check-all aria-label="{{ __('Select all') }}"></th>
              <th>{{ __('Product') }}</th>
              <th>{{ __('Category') }}</th>
              <th>{{ __('Branch') }}</th>
              <th class="text-end">{{ __('Price') }}</th>
              <th class="text-end">{{ __('Scans') }}</th>
              <th>{{ __('Status') }}</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @forelse ($products as $product)
              <tr>
                <td><input type="checkbox" class="form-check-input" name="products[]" value="{{ $product->id }}" aria-label="{{ __('Select') }}"></td>
                <td>
                  <a href="{{ route('products.show', $product) }}" class="text-heading fw-medium">{{ $product->name() }}</a><br>
                  <small class="text-body-secondary">{{ $product->sku }} · {{ $product->condition->label() }}</small>
                </td>
                <td>{{ __($product->category->name) }}</td>
                <td>{{ $product->branch->name }}</td>
                <td class="text-end text-nowrap">{{ money($product->price) }}</td>
                <td class="text-end">{{ $product->qrCode?->scans_count ?? 0 }}</td>
                <td><x-status-badge :status="$product->status" /></td>
                <td class="text-end text-nowrap">
                  <a href="{{ route('products.edit', $product) }}" class="btn btn-icon btn-text-secondary rounded-pill" title="{{ __('Edit') }}"><i class="icon-base ti tabler-edit"></i></a>
                  <a href="{{ route('labels.index', ['products' => [$product->id]]) }}" class="btn btn-icon btn-text-secondary rounded-pill" title="{{ __('Print label') }}"><i class="icon-base ti tabler-printer"></i></a>
                  <a href="{{ route('products.create', ['from' => $product->id]) }}" class="btn btn-icon btn-text-secondary rounded-pill" title="{{ __('Duplicate') }}"><i class="icon-base ti tabler-copy"></i></a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center py-6 text-body-secondary">{{ __('No products found.') }}</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
        <button type="submit" class="btn btn-sm btn-label-primary">
          <i class="icon-base ti tabler-printer me-1"></i>{{ __('Print labels for selected') }}
        </button>
        {{ $products->links() }}
      </div>
    </form>
  </div>
@endsection

@section('page-script')
  <script>
    document.querySelector('[data-check-all]')?.addEventListener('change', e => {
      document.querySelectorAll('#bulkLabels input[name="products[]"]').forEach(cb => (cb.checked = e.target.checked));
    });
  </script>
@endsection
