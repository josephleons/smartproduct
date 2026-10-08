@extends('layouts/layoutMaster')

@section('title', __('Add company'))

@section('content')
  <form method="POST" action="{{ route('admin.companies.store') }}">
    @csrf
    <div class="row g-6">
      <div class="col-lg-6">
        <div class="card h-100">
          <div class="card-header"><h5 class="card-title mb-0">{{ __('Company') }}</h5></div>
          <div class="card-body">
            <div class="row g-4">
              @include('admin.companies._company-fields', ['company' => null])
              <div class="col-12">
                <label class="form-label" for="plan_id">{{ __('Subscription plan') }} *</label>
                <select id="plan_id" name="plan_id" class="form-select @error('plan_id') is-invalid @enderror" required>
                  @foreach ($plans as $plan)
                    <option value="{{ $plan->id }}" @selected(old('plan_id') == $plan->id)>{{ $plan->name }} · {{ money($plan->monthly_price) }} / {{ __('month') }}</option>
                  @endforeach
                </select>
                @error('plan_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <small class="text-body-secondary">{{ __('The free trial and billing rules come from Billing settings.') }}</small>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="branch_name">{{ __('First branch') }} *</label>
                <input type="text" id="branch_name" name="branch_name" value="{{ old('branch_name', __('Main branch')) }}" class="form-control @error('branch_name') is-invalid @enderror" required>
                @error('branch_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="col-md-6">
                <label class="form-label" for="branch_location">{{ __('Branch location') }}</label>
                <input type="text" id="branch_location" name="branch_location" value="{{ old('branch_location') }}" class="form-control @error('branch_location') is-invalid @enderror" placeholder="Kariakoo, Dar es Salaam">
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card h-100">
          <div class="card-header"><h5 class="card-title mb-0">{{ __('Owner account') }}</h5></div>
          <div class="card-body">
            <p class="text-body-secondary small">{{ __('The owner logs in with these details, then adds their own managers and staff.') }}</p>
            <div class="row g-4">
              <div class="col-12">
                <label class="form-label" for="owner_name">{{ __('Owner name') }} *</label>
                <input type="text" id="owner_name" name="owner_name" value="{{ old('owner_name') }}" class="form-control @error('owner_name') is-invalid @enderror" required>
                @error('owner_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="col-12">
                <label class="form-label" for="owner_email">{{ __('Owner email (login)') }} *</label>
                <input type="email" id="owner_email" name="owner_email" value="{{ old('owner_email') }}" class="form-control @error('owner_email') is-invalid @enderror" required>
                @error('owner_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="col-md-6">
                <label class="form-label" for="owner_password">{{ __('Temporary password') }} *</label>
                <input type="password" id="owner_password" name="owner_password" class="form-control @error('owner_password') is-invalid @enderror" autocomplete="new-password" required>
                @error('owner_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="col-md-6">
                <label class="form-label" for="owner_password_confirmation">{{ __('Confirm password') }} *</label>
                <input type="password" id="owner_password_confirmation" name="owner_password_confirmation" class="form-control" autocomplete="new-password" required>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 d-flex gap-2">
        <button class="btn btn-primary" type="submit">{{ __('Create company') }}</button>
        <a href="{{ route('admin.companies.index') }}" class="btn btn-label-secondary">{{ __('Cancel') }}</a>
      </div>
    </div>
  </form>
@endsection
