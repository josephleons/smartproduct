@php
  $user = auth()->user();
  $initials = collect(explode(' ', trim($user?->name ?? '')))
      ->filter()->take(2)
      ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
      ->implode('');
@endphp

@if (!isset($navbarHideToggle))
  <div class="layout-menu-toggle navbar-nav align-items-xl-center me-4 me-xl-0 d-xl-none">
    <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0);" aria-label="{{ __('Toggle navigation menu') }}">
      <i class="icon-base ti tabler-menu-2 icon-md"></i>
    </a>
  </div>
@endif

<div class="navbar-nav-right d-flex align-items-center justify-content-end" id="navbar-collapse">
  <div class="navbar-nav align-items-center me-auto">
    <div class="nav-item d-flex flex-column">
      <span class="fw-semibold text-heading">{{ $user?->isSuperAdmin() ? __('Platform admin') : $user?->company?->name }}</span>
      @if ($user?->branch)
        <small class="text-body-secondary">{{ $user->branch->name }}</small>
      @endif
    </div>
  </div>

  <ul class="navbar-nav flex-row align-items-center ms-auto">
    <li class="nav-item me-2 d-none d-sm-block">
      @if ($user?->isSuperAdmin())
        <a href="{{ route('admin.companies.create') }}" class="btn btn-sm btn-primary">
          <i class="icon-base ti tabler-plus icon-16px me-1"></i>{{ __('Add company') }}
        </a>
      @else
        <a href="{{ route('products.create') }}" class="btn btn-sm btn-primary">
          <i class="icon-base ti tabler-plus icon-16px me-1"></i>{{ __('Add product') }}
        </a>
      @endif
    </li>

    @auth
      <li class="nav-item navbar-dropdown dropdown-user dropdown">
        <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown"
          aria-expanded="false" aria-label="{{ __('Open user menu') }}">
          <div class="avatar">
            <span class="avatar-initial rounded-circle bg-label-primary fw-semibold">{{ $initials ?: 'SL' }}</span>
          </div>
        </a>
        <ul class="dropdown-menu dropdown-menu-end">
          <li>
            <div class="dropdown-item-text">
              <h6 class="mb-0 text-truncate">{{ $user->name }}</h6>
              <small class="text-body-secondary d-block text-truncate">{{ $user->email }}</small>
              <span class="badge bg-label-primary mt-1">{{ $user->role->label() }}</span>
            </div>
          </li>
          <li><div class="dropdown-divider my-1 mx-n2"></div></li>
          @if ($user->company && $user->can('update', $user->company))
            <li>
              <a class="dropdown-item" href="{{ route('settings.company.edit') }}">
                <i class="icon-base ti tabler-building me-3 icon-md"></i>{{ __('Company profile') }}
              </a>
            </li>
          @endif
          <li>
            <form method="POST" action="{{ route('logout') }}" class="m-0">
              @csrf
              <button type="submit" class="dropdown-item text-danger">
                <i class="icon-base ti tabler-logout me-3 icon-md"></i>{{ __('Log out') }}
              </button>
            </form>
          </li>
        </ul>
      </li>
    @endauth
  </ul>
</div>
