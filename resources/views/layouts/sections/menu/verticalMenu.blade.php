@php
  use Illuminate\Support\Facades\Route;

  $configData = Helper::appClasses();
  $userRole = auth()->user()?->role?->value;
  $currentRouteName = (string) Route::currentRouteName();

  $canView = fn ($item) => empty($item->roles) || in_array($userRole, (array) $item->roles, true);
  $isActive = fn ($slug) => collect((array) $slug)->contains(
      fn ($s) => $currentRouteName === $s || str_starts_with($currentRouteName, $s . '.')
  );
@endphp

<aside id="layout-menu" class="layout-menu menu-vertical menu"
  @foreach ($configData['menuAttributes'] as $attribute => $value) {{ $attribute }}="{{ $value }}" @endforeach>

  @if (!isset($navbarFull))
    <div class="app-brand demo">
      <a href="{{ route('dashboard') }}" class="app-brand-link">
        <x-app-logo />
        <span class="app-brand-text demo menu-text fw-bold ms-2">{{ config('variables.templateName') }}</span>
      </a>

      <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
        <i class="icon-base ti menu-toggle-icon d-none d-xl-block"></i>
        <i class="icon-base ti tabler-x d-block d-xl-none"></i>
      </a>
    </div>
  @endif

  <div class="menu-inner-shadow"></div>

  <ul class="menu-inner py-1">
    @foreach ($menuData[0]->menu as $menu)
      @continue(!$canView($menu))

      @php
        $active = $isActive($menu->slug) ? (isset($menu->submenu) ? 'active open' : 'active') : '';
      @endphp

      <li class="menu-item {{ $active }}">
        <a href="{{ isset($menu->url) ? url($menu->url) : 'javascript:void(0);' }}"
          class="{{ isset($menu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}">
          @isset($menu->icon)
            <i class="{{ $menu->icon }}"></i>
          @endisset
          <div>{{ __($menu->name) }}</div>
        </a>

        @isset($menu->submenu)
          <ul class="menu-sub">
            @foreach ($menu->submenu as $submenu)
              @continue(!$canView($submenu))
              <li class="menu-item {{ $isActive($submenu->slug) ? 'active' : '' }}">
                <a href="{{ url($submenu->url) }}" class="menu-link">
                  <div>{{ __($submenu->name) }}</div>
                </a>
              </li>
            @endforeach
          </ul>
        @endisset
      </li>
    @endforeach
  </ul>
</aside>
