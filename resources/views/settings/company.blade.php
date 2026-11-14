@extends('layouts/layoutMaster')

@section('title', __('Company profile'))

@section('content')
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-header"><h5 class="card-title mb-0">{{ __('Company profile') }}</h5></div>
        <div class="card-body">
          <p class="text-body-secondary">{{ __('These details appear on every customer product page and label.') }}</p>
          <form method="POST" action="{{ route('settings.company.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="d-flex align-items-center gap-4 mb-6">
              @if ($company->logoUrl())
                <img src="{{ $company->logoUrl() }}" alt="" class="rounded" style="height:64px">
              @else
                <x-app-logo :size="64" />
              @endif
              <div>
                <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="form-control @error('logo') is-invalid @enderror">
                @error('logo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <small class="text-body-secondary">{{ __('PNG, JPG or WEBP, up to 1 MB.') }}</small>
                @if ($company->logo_path)
                  <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="remove_logo" value="1" id="remove_logo">
                    <label class="form-check-label" for="remove_logo">{{ __('Remove logo') }}</label>
                  </div>
                @endif
              </div>
            </div>
            <div class="row g-4">
              <div class="col-12">
                <label class="form-label" for="name">{{ __('Company name') }} *</label>
                <input type="text" id="name" name="name" value="{{ old('name', $company->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="col-md-6">
                <label class="form-label" for="phone">{{ __('Phone') }}</label>
                <input type="tel" id="phone" name="phone" value="{{ old('phone', $company->phone) }}" class="form-control @error('phone') is-invalid @enderror">
              </div>
              <div class="col-md-6">
                <label class="form-label" for="whatsapp">{{ __('WhatsApp') }}</label>
                <input type="tel" id="whatsapp" name="whatsapp" value="{{ old('whatsapp', $company->whatsapp) }}" class="form-control @error('whatsapp') is-invalid @enderror">
              </div>
              <div class="col-md-6">
                <label class="form-label" for="email">{{ __('Email') }}</label>
                <input type="email" id="email" name="email" value="{{ old('email', $company->email) }}" class="form-control @error('email') is-invalid @enderror">
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="col-md-6">
                <label class="form-label" for="address">{{ __('Address') }}</label>
                <input type="text" id="address" name="address" value="{{ old('address', $company->address) }}" class="form-control @error('address') is-invalid @enderror">
              </div>
              <x-lipa-namba-fields :numbers="$company->payment_numbers ?? []" />
              <div class="col-12"><button class="btn btn-primary" type="submit">{{ __('Save') }}</button></div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection
