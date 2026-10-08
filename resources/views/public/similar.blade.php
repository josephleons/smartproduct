@extends('public.layout')

@section('title', __('Similar products'))

@section('body')
  @include('public._header', ['company' => $product->company])

  <main class="wrap">
    <section class="card">
      <h2>{{ __('Similar to :name', ['name' => $product->name()]) }}</h2>
      @if ($similar->isEmpty())
        <p class="muted">{{ __('No similar products are available right now.') }}</p>
      @else
        <div class="list">
          @foreach ($similar as $item)
            <a href="{{ route('public.product.show', $item->qrCode->token) }}">
              <span>{{ $item->name() }}<br><span class="muted">{{ $item->condition->label() }} · {{ $item->branch?->name }}</span></span>
              <b style="white-space:nowrap">{{ money($item->price) }}</b>
            </a>
          @endforeach
        </div>
      @endif
    </section>
    <p class="foot"><a href="{{ route('public.product.show', $token) }}">← {{ __('Back to product') }}</a></p>
  </main>
@endsection
