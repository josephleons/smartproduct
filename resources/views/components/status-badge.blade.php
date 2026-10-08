@props(['status'])
@php
  $status = $status instanceof \App\Enums\ProductStatus ? $status : \App\Enums\ProductStatus::from($status);
@endphp
<span {{ $attributes->merge(['class' => 'badge bg-label-' . $status->color()]) }}>{{ $status->label() }}</span>
