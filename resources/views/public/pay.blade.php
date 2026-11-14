@extends('public.layout')

@section('title', __('Pay for :product', ['product' => $product->name()]))

@php($method = old('method', 'cash'))

@section('body')
  @include('public._header', ['company' => $product->company, 'branch' => $product->branch])

  <main class="wrap">
    <section class="card">
      <a href="{{ route('public.product.show', $token) }}" class="muted">&larr; {{ __('Back to product') }}</a>
      <h1>{{ $product->name() }}</h1>
      <p class="price">{{ money($product->price) }}</p>
      <p class="muted">{{ __('Pay the shop first, then confirm here. A shop assistant will check your payment and hand you the item.') }}</p>
    </section>

    <form method="POST" action="{{ route('public.sale.store', $token) }}" class="card" novalidate>
      @csrf
      <h2>{{ __('How are you paying?') }}</h2>

      {{-- Radios first so the CSS sibling selectors can show the matching section. --}}
      <input class="sr" type="radio" name="method" value="cash" id="m-cash" @checked($method === 'cash')>
      <input class="sr" type="radio" name="method" value="mobile" id="m-mobile" @checked($method === 'mobile') @disabled(!$networks)>
      <div class="opts">
        <label class="opt" for="m-cash">{{ __('Cash') }}<small>{{ __('At the counter') }}</small></label>
        <label class="opt" for="m-mobile" @if (!$networks) style="opacity:.5" @endif>{{ __('Mobile money') }}<small>{{ __('Lipa Namba') }}</small></label>
      </div>
      @error('method') <div class="err">{{ $message }}</div> @enderror

      <div class="pane pane-cash">
        <p class="muted" style="margin-top:14px">{{ __('Pay the shop assistant in cash. If you agreed a discount, enter the amount you are actually paying.') }}</p>
      </div>

      <div class="pane pane-mobile">
        @if ($networks)
          <p class="muted" style="margin-top:14px">{{ __('Send the money to one of these Lipa Namba, then choose the network you used and enter the transaction code from your SMS.') }}</p>
          @foreach ($networks as $key => $network)
            <input class="sr" type="radio" name="network" value="{{ $key }}" id="n-{{ $key }}" @checked(old('network') === $key)>
            <label class="opt net" for="n-{{ $key }}" style="border-left-color: {{ $network['color'] }}; margin-top:10px">
              <b>{{ $network['name'] }}</b>
              <span class="muted">{{ __('Lipa Namba') }}:</span> <span class="no">{{ $network['number'] }}</span>
              <small>{{ $product->company->name }}</small>
            </label>
          @endforeach
          @error('network') <div class="err">{{ $message }}</div> @enderror

          <div class="field">
            <label for="transaction_code">{{ __('Transaction code') }}</label>
            <input id="transaction_code" name="transaction_code" value="{{ old('transaction_code') }}" autocapitalize="characters" autocomplete="off" placeholder="QJK3X8Y2LM">
            @error('transaction_code') <div class="err">{{ $message }}</div> @enderror
          </div>
        @endif
      </div>

      <div class="field">
        <label for="amount">{{ __('Amount paid (TZS)') }}</label>
        <input id="amount" name="amount" inputmode="numeric" value="{{ old('amount', $product->price) }}">
        @error('amount') <div class="err">{{ $message }}</div> @enderror
      </div>
      <div class="field">
        <label for="customer_name">{{ __('Your name') }}</label>
        <input id="customer_name" name="customer_name" value="{{ old('customer_name') }}" autocomplete="name">
        @error('customer_name') <div class="err">{{ $message }}</div> @enderror
      </div>
      <div class="field">
        <label for="customer_phone">{{ __('Your phone number') }}</label>
        <input id="customer_phone" name="customer_phone" type="tel" value="{{ old('customer_phone') }}" autocomplete="tel" placeholder="0712 345 678">
        @error('customer_phone') <div class="err">{{ $message }}</div> @enderror
      </div>

      <div class="btns">
        <button type="submit" class="btn btn-pay">{{ __('Confirm payment') }}</button>
      </div>
    </form>
  </main>
@endsection
