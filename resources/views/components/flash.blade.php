@foreach (['success' => 'success', 'error' => 'danger'] as $key => $type)
  @if (session($key))
    <div class="alert alert-{{ $type }} alert-dismissible" role="alert">
      {{ session($key) }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Close') }}"></button>
    </div>
  @endif
@endforeach
