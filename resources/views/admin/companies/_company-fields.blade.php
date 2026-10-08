<div class="col-12">
  <label class="form-label" for="name">{{ __('Company name') }} *</label>
  <input type="text" id="name" name="name" value="{{ old('name', $company?->name) }}" class="form-control @error('name') is-invalid @enderror" placeholder="Kariakoo Electronics" required>
  @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
<div class="col-md-6">
  <label class="form-label" for="phone">{{ __('Phone') }}</label>
  <input type="tel" id="phone" name="phone" value="{{ old('phone', $company?->phone) }}" class="form-control @error('phone') is-invalid @enderror" placeholder="0712 345 678">
  @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
<div class="col-md-6">
  <label class="form-label" for="whatsapp">{{ __('WhatsApp') }}</label>
  <input type="tel" id="whatsapp" name="whatsapp" value="{{ old('whatsapp', $company?->whatsapp) }}" class="form-control @error('whatsapp') is-invalid @enderror">
  @error('whatsapp') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
<div class="col-md-6">
  <label class="form-label" for="email">{{ __('Company email') }}</label>
  <input type="email" id="email" name="email" value="{{ old('email', $company?->email) }}" class="form-control @error('email') is-invalid @enderror">
  @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
<div class="col-md-6">
  <label class="form-label" for="address">{{ __('Address') }}</label>
  <input type="text" id="address" name="address" value="{{ old('address', $company?->address) }}" class="form-control @error('address') is-invalid @enderror">
  @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
