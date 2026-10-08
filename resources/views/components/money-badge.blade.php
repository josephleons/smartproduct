@props(['status'])
<span {{ $attributes->merge(['class' => 'badge bg-label-' . $status->color()]) }}>{{ $status->label() }}</span>
