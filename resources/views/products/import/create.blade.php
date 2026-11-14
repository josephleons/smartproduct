@extends('layouts/layoutMaster')

@section('title', __('Import products'))

@section('content')
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-4 mb-6">
    <div>
      <h4 class="mb-1">{{ __('Import products') }}</h4>
      <p class="mb-0 text-body-secondary">{{ __('Register many products at once from Excel. You will see a preview before anything is saved.') }}</p>
    </div>
    <a href="{{ route('products.index') }}" class="btn btn-label-secondary">{{ __('Cancel') }}</a>
  </div>

  <div class="row justify-content-center">
    <div class="col-lg-8">
      <form method="GET" action="{{ route('products.import.template') }}" id="templateForm">
        <input type="hidden" name="category" value="{{ old('category_id') }}" data-template-category>
      </form>

      <form method="POST" action="{{ route('products.import.store') }}" enctype="multipart/form-data" class="card">
        @csrf
        <div class="card-body">
          <h6 class="mb-1"><span class="badge badge-center rounded-pill bg-label-primary me-2">1</span>{{ __('Choose the category and branch') }}</h6>
          <p class="small text-body-secondary mb-4 ms-8">{{ __('One file per category: the template only has the spec columns of that category.') }}</p>
          <div class="row g-4 mb-6 ms-md-6">
            <div class="col-md-6">
              <label class="form-label" for="category_id">{{ __('Category') }} *</label>
              <select id="category_id" name="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                <option value="">{{ __('Choose…') }}</option>
                @foreach ($categories as $category)
                  <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ __($category->name) }}</option>
                @endforeach
              </select>
              @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
              <label class="form-label" for="branch_id">{{ __('Branch') }} *</label>
              <select id="branch_id" name="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
                @if ($branches->count() !== 1)
                  <option value="">{{ __('Choose…') }}</option>
                @endif
                @foreach ($branches as $branch)
                  <option value="{{ $branch->id }}" @selected(old('branch_id') == $branch->id)>{{ $branch->name }}</option>
                @endforeach
              </select>
              @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
              <small class="text-body-secondary">{{ __('All products in the file go to this branch.') }}</small>
            </div>
          </div>

          <h6 class="mb-1"><span class="badge badge-center rounded-pill bg-label-primary me-2">2</span>{{ __('Download the template and fill it in') }}</h6>
          <p class="small text-body-secondary mb-4 ms-8">{{ __('One product per row. The second sheet lists the allowed values for each column.') }}</p>
          <div class="d-flex flex-wrap gap-2 mb-6 ms-md-8">
            <button type="submit" form="templateForm" name="format" value="xlsx" class="btn btn-label-primary" data-template-button @disabled(!old('category_id'))>
              <i class="icon-base ti tabler-file-spreadsheet me-1"></i>{{ __('Excel template (.xlsx)') }}
            </button>
            <button type="submit" form="templateForm" name="format" value="csv" class="btn btn-label-secondary" data-template-button @disabled(!old('category_id'))>
              <i class="icon-base ti tabler-file-text me-1"></i>{{ __('CSV template') }}
            </button>
          </div>

          <h6 class="mb-1"><span class="badge badge-center rounded-pill bg-label-primary me-2">3</span>{{ __('Upload the file') }}</h6>
          <p class="small text-body-secondary mb-4 ms-8">{{ __('Excel (.xlsx) or CSV, up to :max products per file.', ['max' => number_format($maxRows)]) }}</p>
          <div class="ms-md-8">
            <input type="file" name="file" accept=".xlsx,.csv" class="form-control @error('file') is-invalid @enderror" required>
            @error('file') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
        </div>
        <div class="card-footer d-flex justify-content-end">
          <button type="submit" class="btn btn-primary"><i class="icon-base ti tabler-checklist me-1"></i>{{ __('Check file') }}</button>
        </div>
      </form>
    </div>
  </div>
@endsection

@section('page-script')
  <script>
    // The template download uses the category chosen in step 1.
    document.getElementById('category_id')?.addEventListener('change', e => {
      document.querySelector('[data-template-category]').value = e.target.value;
      document.querySelectorAll('[data-template-button]').forEach(b => (b.disabled = !e.target.value));
    });
  </script>
@endsection
