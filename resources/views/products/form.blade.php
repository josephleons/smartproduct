@extends('layouts/layoutMaster')

@php
  $editing = $product->exists;
  $specValues = old('specs', $product->exists
      ? $product->specs->mapWithKeys(fn ($s) => [$s->key => $s->value])->all()
      : []);
  $basicFields = ['category_id', 'branch_id', 'brand', 'model', 'condition', 'price', 'quantity', 'status', 'serial_number', 'warranty_months', 'description'];
  $specErrors = collect($errors->getMessages())
      ->filter(fn ($messages, $key) => str_starts_with($key, 'specs.'))
      ->mapWithKeys(fn ($messages, $key) => [substr($key, 6) => $messages[0]]);
  $errorStep = collect($errors->keys())->contains(fn ($k) => str_starts_with($k, 'specs')) && !collect($errors->keys())->intersect($basicFields)->count() ? 1 : 0;
@endphp

@section('title', $editing ? __('Edit product') : __('Add product'))

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.js'])
@endsection

@section('page-script')
  @vite(['resources/assets/js/smartlabel-product-wizard.js'])
@endsection

@section('content')
  <div class="d-flex justify-content-between align-items-center mb-6">
    <div>
      <h4 class="mb-1">{{ $editing ? __('Edit :name', ['name' => $product->name()]) : __('Add product') }}</h4>
      <p class="mb-0 text-body-secondary">
        @if ($editing)
          {{ __('The QR code stays the same, so printed labels keep working.') }}
        @else
          {{ __('Three quick steps. Only the fields for the chosen category appear.') }}
        @endif
      </p>
    </div>
    <a href="{{ $editing ? route('products.show', $product) : route('products.index') }}" class="btn btn-label-secondary">{{ __('Cancel') }}</a>
  </div>

  @if ($errors->any())
    <div class="alert alert-danger">{{ __('Please check the highlighted fields.') }}</div>
  @endif

  <div class="bs-stepper wizard-numbered" id="productWizard" data-error-step="{{ $errorStep }}"
    data-fields-url="{{ url('/categories/__ID__/fields') }}" data-spec-values='@json((object) $specValues)'
    data-spec-errors='@json((object) $specErrors->all())'>
    <div class="bs-stepper-header">
      <div class="step" data-target="#step-basic">
        <button type="button" class="step-trigger">
          <span class="bs-stepper-circle">1</span>
          <span class="bs-stepper-label">
            <span class="bs-stepper-title">{{ __('Basic info') }}</span>
            <span class="bs-stepper-subtitle">{{ __('Category, model, price') }}</span>
          </span>
        </button>
      </div>
      <div class="line"><i class="icon-base ti tabler-chevron-right scaleX-n1-rtl"></i></div>
      <div class="step" data-target="#step-specs">
        <button type="button" class="step-trigger">
          <span class="bs-stepper-circle">2</span>
          <span class="bs-stepper-label">
            <span class="bs-stepper-title">{{ __('Specs') }}</span>
            <span class="bs-stepper-subtitle">{{ __('Category template') }}</span>
          </span>
        </button>
      </div>
      <div class="line"><i class="icon-base ti tabler-chevron-right scaleX-n1-rtl"></i></div>
      <div class="step" data-target="#step-review">
        <button type="button" class="step-trigger">
          <span class="bs-stepper-circle">3</span>
          <span class="bs-stepper-label">
            <span class="bs-stepper-title">{{ __('Review & label') }}</span>
            <span class="bs-stepper-subtitle">{{ __('Save and print') }}</span>
          </span>
        </button>
      </div>
    </div>

    <div class="bs-stepper-content">
      <form method="POST" action="{{ $editing ? route('products.update', $product) : route('products.store') }}" id="productForm" novalidate>
        @csrf
        @if ($editing)
          @method('PUT')
        @endif

        {{-- Step 1: basic info --}}
        <div id="step-basic" class="content">
          <div class="row g-4">
            <div class="col-md-6">
              <label class="form-label" for="category_id">{{ __('Category') }} *</label>
              <select id="category_id" name="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                <option value="">{{ __('Choose…') }}</option>
                @foreach ($categories as $category)
                  <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ __($category->name) }}</option>
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
                  <option value="{{ $branch->id }}" @selected(old('branch_id', $product->branch_id) == $branch->id)>{{ $branch->name }}</option>
                @endforeach
              </select>
              @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
              <label class="form-label" for="brand">{{ __('Brand') }} *</label>
              <input type="text" id="brand" name="brand" value="{{ old('brand', $product->brand) }}" class="form-control @error('brand') is-invalid @enderror" placeholder="Dell" required>
              @error('brand') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
              <label class="form-label" for="model">{{ __('Model') }} *</label>
              <input type="text" id="model" name="model" value="{{ old('model', $product->model) }}" class="form-control @error('model') is-invalid @enderror" placeholder="Latitude 5420" required>
              @error('model') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
              <label class="form-label" for="price">{{ __('Price (TZS)') }} *</label>
              <input type="text" inputmode="numeric" id="price" name="price" value="{{ old('price', $product->price) }}" class="form-control @error('price') is-invalid @enderror" placeholder="850000" required>
              @error('price') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
              <label class="form-label" for="condition">{{ __('Condition') }} *</label>
              <select id="condition" name="condition" class="form-select @error('condition') is-invalid @enderror">
                @foreach ($conditions as $condition)
                  <option value="{{ $condition->value }}" @selected(old('condition', $product->condition?->value) === $condition->value)>{{ $condition->label() }}</option>
                @endforeach
              </select>
              @error('condition') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
              <label class="form-label" for="status">{{ __('Status') }} *</label>
              <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
                @foreach ($statuses as $status)
                  <option value="{{ $status->value }}" @selected(old('status', $product->status?->value) === $status->value)>{{ $status->label() }}</option>
                @endforeach
              </select>
              @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
              <label class="form-label" for="quantity">{{ __('Quantity') }}</label>
              <input type="number" min="0" id="quantity" name="quantity" value="{{ old('quantity', $product->quantity) }}" class="form-control @error('quantity') is-invalid @enderror">
              @error('quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
              <label class="form-label" for="warranty_months">{{ __('Warranty (months)') }}</label>
              <input type="number" min="0" max="120" id="warranty_months" name="warranty_months" value="{{ old('warranty_months', $product->warranty_months) }}" class="form-control @error('warranty_months') is-invalid @enderror">
              @error('warranty_months') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
              <label class="form-label" for="serial_number">{{ __('Serial number') }}</label>
              <input type="text" id="serial_number" name="serial_number" value="{{ old('serial_number', $product->serial_number) }}" class="form-control @error('serial_number') is-invalid @enderror">
              @error('serial_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-12">
              <label class="form-label" for="description">{{ __('Notes for customers') }}</label>
              <textarea id="description" name="description" rows="2" class="form-control @error('description') is-invalid @enderror">{{ old('description', $product->description) }}</textarea>
              @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-12 d-flex justify-content-end">
              <button type="button" class="btn btn-primary" data-wizard-next>{{ __('Next') }} <i class="icon-base ti tabler-arrow-right ms-1"></i></button>
            </div>
          </div>
        </div>

        {{-- Step 2: specs, rendered from the category template --}}
        <div id="step-specs" class="content">
          <div class="row g-4" id="specFields">
            <div class="col-12 text-body-secondary" data-spec-placeholder>{{ __('Choose a category in step 1 to see its spec fields.') }}</div>
          </div>
          <div class="d-flex justify-content-between mt-6">
            <button type="button" class="btn btn-label-secondary" data-wizard-prev><i class="icon-base ti tabler-arrow-left me-1"></i>{{ __('Back') }}</button>
            <button type="button" class="btn btn-primary" data-wizard-next>{{ __('Next') }} <i class="icon-base ti tabler-arrow-right ms-1"></i></button>
          </div>
        </div>

        {{-- Step 3: review --}}
        <div id="step-review" class="content">
          <div class="row g-6">
            <div class="col-lg-7">
              <h6 class="mb-3">{{ __('Summary') }}</h6>
              <dl class="row mb-0" id="reviewList"></dl>
            </div>
            <div class="col-lg-5">
              <div class="bg-soft-teal rounded p-4">
                <h6 class="mb-2"><i class="icon-base ti tabler-qrcode me-1"></i>{{ __('QR label') }}</h6>
                @if ($editing && $product->qrCode)
                  <p class="mb-0 small">{{ __('This product keeps its QR code:') }}<br><code>{{ $product->qrCode->url() }}</code></p>
                @else
                  <p class="mb-0 small">{{ __('A permanent QR code is created when you save. You can print the label on the next screen.') }}</p>
                @endif
              </div>
            </div>
          </div>
          <div class="d-flex justify-content-between mt-6">
            <button type="button" class="btn btn-label-secondary" data-wizard-prev><i class="icon-base ti tabler-arrow-left me-1"></i>{{ __('Back') }}</button>
            <button type="submit" class="btn btn-success"><i class="icon-base ti tabler-check me-1"></i>{{ $editing ? __('Save changes') : __('Save product') }}</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <template id="i18n" data-yes="{{ __('Yes') }}" data-no="{{ __('No') }}" data-choose="{{ __('Choose…') }}"
    data-empty="{{ __('This category has no extra spec fields.') }}" data-loading="{{ __('Loading fields…') }}"
    data-required="{{ __('This field is required.') }}"></template>
@endsection
