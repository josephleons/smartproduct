@extends('layouts/layoutMaster')

@section('title', $branch->exists ? __('Edit branch') : __('Add branch'))

@section('content')
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-header"><h5 class="card-title mb-0">{{ $branch->exists ? __('Edit branch') : __('Add branch') }}</h5></div>
        <div class="card-body">
          <form method="POST" action="{{ $branch->exists ? route('branches.update', $branch) : route('branches.store') }}">
            @csrf
            @if ($branch->exists)
              @method('PUT')
            @endif
            <div class="row g-4">
              <div class="col-md-6">
                <label class="form-label" for="name">{{ __('Name') }} *</label>
                <input type="text" id="name" name="name" value="{{ old('name', $branch->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="col-md-6">
                <label class="form-label" for="location">{{ __('Location') }}</label>
                <input type="text" id="location" name="location" value="{{ old('location', $branch->location) }}" class="form-control @error('location') is-invalid @enderror" placeholder="Kariakoo, Dar es Salaam">
                @error('location') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="col-md-6">
                <label class="form-label" for="phone">{{ __('Phone') }}</label>
                <input type="tel" id="phone" name="phone" value="{{ old('phone', $branch->phone) }}" class="form-control @error('phone') is-invalid @enderror">
                <small class="text-body-secondary">{{ __('Leave empty to use the company number.') }}</small>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="whatsapp">{{ __('WhatsApp') }}</label>
                <input type="tel" id="whatsapp" name="whatsapp" value="{{ old('whatsapp', $branch->whatsapp) }}" class="form-control @error('whatsapp') is-invalid @enderror">
              </div>
              <x-lipa-namba-fields :numbers="$branch->payment_numbers ?? []" :hint="__('Only if this branch has its own Lipa Namba. Leave empty to use the company numbers from Settings.')" />
              <div class="col-12">
                <div class="form-check form-switch">
                  <input type="hidden" name="is_active" value="0">
                  <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $branch->is_active))>
                  <label class="form-check-label" for="is_active">{{ __('Active') }}</label>
                </div>
              </div>
              <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary" type="submit">{{ __('Save') }}</button>
                <a href="{{ route('branches.index') }}" class="btn btn-label-secondary">{{ __('Cancel') }}</a>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection
