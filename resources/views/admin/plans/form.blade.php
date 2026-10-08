@extends('layouts/layoutMaster')

@section('title', $plan->exists ? __('Edit plan') : __('Add plan'))

@section('content')
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <form method="POST" action="{{ $plan->exists ? route('admin.plans.update', $plan) : route('admin.plans.store') }}" class="card">
        @csrf
        @if ($plan->exists)
          @method('PUT')
        @endif
        <div class="card-header"><h5 class="card-title mb-0">{{ $plan->exists ? __('Edit plan') : __('Add plan') }}</h5></div>
        <div class="card-body">
          <div class="row g-4">
            <div class="col-md-6">
              <label class="form-label" for="name">{{ __('Name') }} *</label>
              <input type="text" id="name" name="name" value="{{ old('name', $plan->name) }}" class="form-control @error('name') is-invalid @enderror" placeholder="Premium" required>
              @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
              <label class="form-label" for="monthly_price">{{ __('Monthly price (TZS)') }} *</label>
              <input type="text" inputmode="numeric" id="monthly_price" name="monthly_price" value="{{ old('monthly_price', $plan->monthly_price) }}" class="form-control @error('monthly_price') is-invalid @enderror" required>
              @error('monthly_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-12">
              <label class="form-label" for="description">{{ __('Short description') }}</label>
              <input type="text" id="description" name="description" value="{{ old('description', $plan->description) }}" class="form-control @error('description') is-invalid @enderror">
            </div>

            <div class="col-12"><h6 class="mb-0 mt-2">{{ __('Scope of usage') }}</h6><small class="text-body-secondary">{{ __('Leave empty for unlimited.') }}</small></div>
            @foreach (['max_products' => __('Max products'), 'max_branches' => __('Max branches'), 'max_users' => __('Max users')] as $field => $label)
              <div class="col-md-4">
                <label class="form-label" for="{{ $field }}">{{ $label }}</label>
                <input type="number" min="1" id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $plan->$field) }}" class="form-control @error($field) is-invalid @enderror" placeholder="{{ __('Unlimited') }}">
                @error($field) <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
            @endforeach

            <div class="col-12">
              <h6 class="mb-2 mt-2">{{ __('Features') }}</h6>
              @foreach ($features as $feature)
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="features[]" value="{{ $feature->value }}" id="f_{{ $feature->value }}" @checked(in_array($feature->value, old('features', $plan->features ?? []), true))>
                  <label class="form-check-label" for="f_{{ $feature->value }}">
                    {{ $feature->label() }}
                    @if ($feature->comingSoon())<span class="badge bg-label-info ms-1">{{ __('Coming soon') }}</span>@endif
                  </label>
                </div>
              @endforeach
            </div>

            <div class="col-md-4">
              <label class="form-label" for="sort_order">{{ __('Display order') }}</label>
              <input type="number" min="0" id="sort_order" name="sort_order" value="{{ old('sort_order', $plan->sort_order ?? 0) }}" class="form-control">
            </div>
            <div class="col-md-8 d-flex align-items-end">
              <div class="form-check form-switch">
                <input type="hidden" name="is_active" value="0">
                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $plan->is_active))>
                <label class="form-check-label" for="is_active">{{ __('Available for new companies') }}</label>
              </div>
            </div>
          </div>
        </div>
        <div class="card-footer d-flex gap-2">
          <button class="btn btn-primary" type="submit">{{ __('Save') }}</button>
          <a href="{{ route('admin.plans.index') }}" class="btn btn-label-secondary">{{ __('Cancel') }}</a>
        </div>
      </form>
    </div>
  </div>
@endsection
