@extends('public.layout')

@section('title', __('Product not found'))

@section('body')
  <main class="wrap" style="padding-top:48px; text-align:center">
    <x-language-switcher class="muted" />
    <div class="card">
      <h1>{{ __('Product not found') }}</h1>
      <p class="muted">{{ __('This label is not linked to a product any more. Please ask a member of staff for help.') }}</p>
    </div>
  </main>
@endsection
