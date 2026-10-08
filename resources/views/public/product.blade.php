@extends('public.layout')

@section('title', $product->name())

@section('body')
  @include('public._header', ['company' => $product->company, 'branch' => $product->branch])

  <main class="wrap">
    <section class="card">
      <span class="badge b-{{ $product->status->value }}">{{ $product->status->label() }}</span>
      <h1>{{ $product->name() }}</h1>
      <div class="muted">{{ $product->category->name }}</div>

      @if ($showPrice)
        <p class="price">{{ money($product->price) }}</p>
      @else
        <p class="muted" style="margin-top:8px">{{ __('This item is no longer available. Contact the shop for similar products.') }}</p>
      @endif

      <div class="chips">
        <span class="chip">{{ $product->condition->label() }}</span>
        <span class="chip">
          @if ($product->warranty_months)
            {{ trans_choice(':count month warranty|:count months warranty', $product->warranty_months) }}
          @else
            {{ __('No warranty') }}
          @endif
        </span>
      </div>

      <div class="btns">
        @if ($whatsapp)
          <a class="btn btn-wa" href="{{ route('public.product.go', [$token, 'whatsapp']) }}" rel="nofollow">{{ __('Chat on WhatsApp') }}</a>
        @endif
        @if ($phone)
          <a class="btn btn-call" href="{{ route('public.product.go', [$token, 'call']) }}" rel="nofollow">{{ __('Call the shop') }}</a>
        @endif
        <a class="btn btn-ghost" href="{{ route('public.product.similar', $token) }}" rel="nofollow">{{ __('View similar products') }}</a>
      </div>
    </section>

    @if ($product->specs->isNotEmpty())
      <section class="card">
        <h2>{{ __('Specifications') }}</h2>
        <dl class="specs">
          @foreach ($product->specs as $spec)
            <div><dt>{{ $spec->label }}</dt><dd>{{ $spec->displayValue() }}</dd></div>
          @endforeach
        </dl>
      </section>
    @endif

    @if ($product->description)
      <section class="card">
        <h2>{{ __('Notes') }}</h2>
        <div>{{ $product->description }}</div>
      </section>
    @endif

    <p class="foot muted">
      {{ __('Product ID') }}: {{ $product->sku }}<br>
      {{ __('Last updated') }}: {{ $product->updated_at->timezone(config('app.timezone'))->format('d M Y, H:i') }}<br>
      {{ __('Powered by') }} {{ config('variables.templateName') }}
    </p>
  </main>
@endsection
