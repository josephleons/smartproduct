@extends('layouts/layoutMaster')

@section('title', __('QR & Labels'))

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('page-script')
  @vite(['resources/assets/js/smartlabel-labels.js'])
@endsection

@section('content')
  <div class="mb-6">
    <h4 class="mb-1">{{ __('QR & Labels') }}</h4>
    <p class="mb-0 text-body-secondary">{{ __('Choose products and a sticker size, check the preview, then download or print.') }}</p>
  </div>

  <div class="row g-6">
    <div class="col-lg-4">
      <div class="card">
        <div class="card-body">
          <form id="labelForm" data-preview-url="{{ route('labels.preview') }}" data-pdf-url="{{ route('labels.pdf') }}">
            <div class="mb-4">
              <label class="form-label" for="products">{{ __('Products') }}</label>
              <select id="products" name="products[]" class="form-select select2" multiple data-placeholder="{{ __('Search products…') }}">
                @foreach ($products as $product)
                  <option value="{{ $product->id }}" @selected(in_array($product->id, $selected, true))>{{ $product->name() }} ({{ $product->sku }})</option>
                @endforeach
              </select>
            </div>

            <div class="mb-4">
              <label class="form-label" for="template">{{ __('Sticker size') }}</label>
              <select id="template" name="template" class="form-select">
                @foreach ($templates as $key => $template)
                  <option value="{{ $key }}" @selected($key === $defaultTemplate)>{{ $template['name'] }} · {{ __('A4 sheet of :count', ['count' => $template['sheet']['columns'] * $template['sheet']['rows']]) }}</option>
                @endforeach
              </select>
            </div>

            <div class="mb-4">
              <span class="form-label d-block">{{ __('Print on') }}</span>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="layout" id="layout_sheet" value="sheet" checked>
                <label class="form-check-label" for="layout_sheet">{{ __('A4 sheet') }}</label>
              </div>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="layout" id="layout_single" value="single">
                <label class="form-check-label" for="layout_single">{{ __('Single sticker') }}</label>
              </div>
            </div>

            <div class="mb-4">
              <label class="form-label" for="copies">{{ __('Copies per product') }}</label>
              <input type="number" id="copies" name="copies" value="1" min="1" max="{{ config('labels.max_copies') }}" class="form-control">
            </div>

            <div class="mb-6">
              <span class="form-label d-block">{{ __('Show on label') }}</span>
              @php
                $fieldLabels = ['company' => __('Company name'), 'specs' => __('Key specs'), 'price' => __('Price'), 'branch' => __('Branch'), 'sku' => __('Product ID')];
              @endphp
              @foreach ($fields as $field)
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="fields[]" value="{{ $field }}" id="field_{{ $field }}" checked>
                  <label class="form-check-label" for="field_{{ $field }}">{{ $fieldLabels[$field] ?? $field }}</label>
                </div>
              @endforeach
              <small class="text-body-secondary">{{ __('The model, QR code and "Scan for specs" always print.') }}</small>
            </div>

            <div class="d-grid gap-2">
              <button type="button" class="btn btn-primary" data-action="pdf"><i class="icon-base ti tabler-download me-1"></i>{{ __('Download PDF') }}</button>
              <button type="button" class="btn btn-label-primary" data-action="print"><i class="icon-base ti tabler-printer me-1"></i>{{ __('Print') }}</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="col-lg-8">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0">{{ __('Preview') }}</h5>
          <small class="text-body-secondary">{{ __('Identical to the PDF') }}</small>
        </div>
        <div class="card-body">
          <div id="previewEmpty" class="text-center text-body-secondary py-10">{{ __('Select at least one product to see the preview.') }}</div>
          <iframe id="previewFrame" class="label-preview-frame d-none" title="{{ __('Label preview') }}" style="height: 70vh"></iframe>
        </div>
      </div>
    </div>
  </div>
@endsection
