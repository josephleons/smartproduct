{{-- Camera QR scanner (smartlabel-scanner.js) with a typed-code fallback. --}}
<div class="card" id="scanner"
  data-product-url="{{ route('sales.product', '__CODE__') }}"
  data-error-camera="{{ __('The camera could not start. Allow camera access for this site, or type the code below.') }}"
  data-error-insecure="{{ __('The camera needs a secure (https) connection. Type the code below instead.') }}"
  data-error-foreign="{{ __('This QR code is not a SmartLabel sticker.') }}">
  <div class="card-body">
    <button type="button" class="btn btn-primary btn-lg w-100" data-scan-start>
      <i class="icon-base ti tabler-scan me-2"></i>{{ __('Scan product QR') }}
    </button>
    <div class="d-none" data-scan-area>
      <video class="w-100 rounded bg-dark" playsinline muted style="aspect-ratio: 1; object-fit: cover"></video>
      <button type="button" class="btn btn-label-secondary w-100 mt-2" data-scan-stop>{{ __('Close camera') }}</button>
    </div>
    <div class="alert alert-warning small mt-3 mb-0 d-none" data-scan-error></div>

    <form method="GET" action="{{ route('sales.lookup') }}" class="mt-4">
      <label class="form-label small text-body-secondary" for="code">{{ __('Or type the SKU or sticker code') }}</label>
      <div class="input-group">
        <input type="text" id="code" name="code" class="form-control text-uppercase" placeholder="TZ-LAP5420-024" autocomplete="off">
        <button class="btn btn-label-primary" type="submit">{{ __('Open') }}</button>
      </div>
    </form>
  </div>
</div>
