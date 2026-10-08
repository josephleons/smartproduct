@php
  $containerFooter = ($configData['contentLayout'] ?? 'compact') === 'compact' ? 'container-xxl' : 'container-fluid';
@endphp
<footer class="content-footer footer bg-footer-theme">
  <div class="{{ $containerFooter }}">
    <div class="footer-container d-flex align-items-center justify-content-between py-4 flex-md-row flex-column">
      <div class="text-body">&copy; {{ date('Y') }} {{ config('variables.creatorName') }}</div>
      <div class="d-none d-lg-inline-block text-body-secondary">{{ __('Smart product labels for electronics retailers') }}</div>
    </div>
  </div>
</footer>
