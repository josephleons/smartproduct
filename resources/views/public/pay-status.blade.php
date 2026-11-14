@extends('public.layout')

@section('title', __('Payment :reference', ['reference' => $sale->reference]))

@section('head')
  @if ($sale->isOpen())
    {{-- Refresh while waiting so the customer sees the shop's decision without reloading. --}}
    <meta http-equiv="refresh" content="20">
  @endif
@endsection

@section('body')
  @include('public._header', ['company' => $product->company, 'branch' => $product->branch])

  <main class="wrap">
    <section class="card" style="text-align:center">
      @if ($sale->status === App\Enums\SaleStatus::Approved)
        <div class="alert ok">{{ __('Payment approved. Thank you for your purchase!') }}</div>
      @elseif ($sale->isOpen())
        <p class="muted">{{ __('Show this code to the shop assistant') }}</p>
        <div class="ref">{{ $sale->reference }}</div>
        <p>{{ __('Your payment is waiting for the shop to confirm it. This page updates by itself.') }}</p>
      @elseif ($sale->status === App\Enums\SaleStatus::Rejected)
        <div class="alert">{{ __('The shop could not confirm this payment. Please talk to the shop assistant.') }}</div>
      @else
        <div class="alert">{{ __('This payment request has expired. Please talk to the shop assistant.') }}</div>
      @endif

      <dl class="specs" style="margin-top:16px; text-align:left">
        <div><dt>{{ __('Product') }}</dt><dd>{{ $product->name() }}</dd></div>
        <div><dt>{{ __('Reference') }}</dt><dd>{{ $sale->reference }}</dd></div>
        <div><dt>{{ __('Paid with') }}</dt><dd>{{ $sale->networkName() ?? $sale->method->label() }}</dd></div>
        <div><dt>{{ __('Amount') }}</dt><dd>{{ money($sale->amount) }}</dd></div>
      </dl>

      <div class="btns">
        <a class="btn btn-ghost" href="{{ route('public.product.show', $token) }}">{{ __('Back to product') }}</a>
      </div>
    </section>
  </main>
@endsection
