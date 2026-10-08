@props(['size' => 32])
{{-- SmartLabel mark: a tag with a QR corner. --}}
<span {{ $attributes->merge(['class' => 'app-brand-logo d-inline-flex']) }}>
  <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <rect width="32" height="32" rx="8" fill="#0F6680" />
    <rect x="7" y="7" width="7" height="7" rx="1.5" fill="#fff" />
    <rect x="18" y="7" width="7" height="7" rx="1.5" fill="#fff" />
    <rect x="7" y="18" width="7" height="7" rx="1.5" fill="#fff" />
    <rect x="18" y="18" width="3" height="3" fill="#EAF4F7" />
    <rect x="22" y="22" width="3" height="3" fill="#EAF4F7" />
    <rect x="18" y="22" width="3" height="3" fill="#EAF4F7" opacity=".6" />
  </svg>
</span>
