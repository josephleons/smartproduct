@extends('layouts/layoutMaster')

@section('title', __('Billing settings'))

@section('content')
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <form method="POST" action="{{ route('admin.billing.settings.update') }}" class="card">
        @csrf
        @method('PUT')
        <div class="card-header">
          <h5 class="card-title mb-1">{{ __('Billing settings') }}</h5>
          <small class="text-body-secondary">{{ __('Subscriptions are monthly. The billing job runs every hour.') }}</small>
        </div>
        <div class="card-body">
          <div class="row g-4">
            <div class="col-md-4">
              <label class="form-label" for="trial_days">{{ __('Free trial (days)') }}</label>
              <input type="number" min="0" id="trial_days" name="trial_days" value="{{ old('trial_days', $settings['trial_days']) }}" class="form-control @error('trial_days') is-invalid @enderror">
              <small class="text-body-secondary">{{ __('For newly created companies. 0 = bill immediately.') }}</small>
            </div>
            <div class="col-md-4">
              <label class="form-label" for="invoice_days_before">{{ __('Send bill (days before due)') }}</label>
              <input type="number" min="0" id="invoice_days_before" name="invoice_days_before" value="{{ old('invoice_days_before', $settings['invoice_days_before']) }}" class="form-control @error('invoice_days_before') is-invalid @enderror">
            </div>
            <div class="col-md-4">
              <label class="form-label" for="grace_days">{{ __('Grace period (days)') }}</label>
              <input type="number" min="0" id="grace_days" name="grace_days" value="{{ old('grace_days', $settings['grace_days']) }}" class="form-control @error('grace_days') is-invalid @enderror">
              <small class="text-body-secondary">{{ __('After this, unpaid companies become read-only.') }}</small>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="gateway">{{ __('Payment confirmation') }}</label>
              <select id="gateway" name="gateway" class="form-select">
                @foreach ($gateways as $gateway)
                  <option value="{{ $gateway }}" @selected(old('gateway', $settings['gateway']) === $gateway)>{{ $gateway === 'manual' ? __('Manual (superadmin confirms)') : ucfirst($gateway) }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-12">
              <label class="form-label" for="payment_instructions">{{ __('Payment instructions shown to companies') }}</label>
              <textarea id="payment_instructions" name="payment_instructions" rows="4" class="form-control @error('payment_instructions') is-invalid @enderror" placeholder="{{ __('e.g. Pay with M-Pesa Lipa Namba 123456 (SmartLabel Ltd). Use your bill number as reference.') }}">{{ old('payment_instructions', $settings['payment_instructions']) }}</textarea>
            </div>
          </div>
        </div>
        <div class="card-footer"><button class="btn btn-primary" type="submit">{{ __('Save') }}</button></div>
      </form>
    </div>
  </div>
@endsection
