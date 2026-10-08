@props(['status'])
@if ($status)
  <span {{ $attributes->merge(['class' => 'badge bg-label-' . $status->color()]) }}>{{ $status->label() }}</span>
@else
  <span class="badge bg-label-secondary">{{ __('No subscription') }}</span>
@endif
