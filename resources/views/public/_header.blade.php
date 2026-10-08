<header class="top">
  <div class="wrap">
    @if ($company->logoUrl())
      <img src="{{ $company->logoUrl() }}" alt="" loading="lazy">
    @endif
    <div>
      <b>{{ $company->name }}</b>
      @isset($branch)
        <small>{{ $branch->name }}@if ($branch->location) · {{ $branch->location }}@endif</small>
      @endisset
    </div>
  </div>
</header>
