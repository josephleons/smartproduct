{{-- Shared card shell for the Fortify auth pages. --}}
<div class="container-xxl">
  <div class="authentication-wrapper authentication-basic container-p-y">
    <div class="authentication-inner py-6" style="max-width: {{ $maxWidth ?? '460px' }}">
      <div class="card">
        <div class="card-body">
          <div class="app-brand justify-content-center mb-4">
            <a href="{{ url('/') }}" class="app-brand-link gap-2">
              <x-app-logo :size="36" />
              <span class="app-brand-text demo text-heading fw-bold">{{ config('variables.templateName') }}</span>
            </a>
          </div>
          @yield('auth-card')
        </div>
      </div>
    </div>
  </div>
</div>
