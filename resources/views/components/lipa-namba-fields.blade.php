{{-- Lipa Namba inputs, one per network in config/payments.php. --}}
@props(['numbers' => [], 'hint' => null])

<div class="col-12">
  <h6 class="mb-1 mt-2">{{ __('Lipa Namba (mobile money)') }}</h6>
  <p class="small text-body-secondary mb-3">{{ $hint ?? __('Customers see these numbers when they choose mobile money on the QR page. Leave a network empty to hide it.') }}</p>
  <div class="row g-4">
    @foreach (config('payments.networks') as $key => $network)
      <div class="col-md-6">
        <label class="form-label" for="payment_numbers_{{ $key }}">
          <span class="badge badge-dot me-1" style="background: {{ $network['color'] }}"></span>{{ $network['name'] }}
        </label>
        <input type="text" inputmode="numeric" id="payment_numbers_{{ $key }}" name="payment_numbers[{{ $key }}]"
          value="{{ old('payment_numbers.'.$key, $numbers[$key] ?? '') }}"
          class="form-control @error('payment_numbers.'.$key) is-invalid @enderror" maxlength="20">
        @error('payment_numbers.'.$key) <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
    @endforeach
  </div>
</div>
