@php
  $customizerHidden = 'customizer-hide';
@endphp

@extends('layouts/blankLayout')

@section('title', __('Forgot password'))

@section('page-style')
  @vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
@endsection

@section('auth-card')
  <h4 class="mb-1">{{ __('Forgot your password?') }}</h4>
  <p class="mb-6">{{ __('Enter your email and we will send you a reset link.') }}</p>

  @if (session('status'))
    <div class="alert alert-success" role="alert">{{ session('status') }}</div>
  @endif

  <form action="{{ route('password.email') }}" method="POST" class="mb-4">
    @csrf
    <div class="mb-6">
      <label for="email" class="form-label">{{ __('Email') }}</label>
      <input type="email" id="email" name="email" value="{{ old('email') }}"
        class="form-control @error('email') is-invalid @enderror" required autofocus>
      @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <button class="btn btn-primary d-grid w-100" type="submit">{{ __('Send reset link') }}</button>
  </form>

  <div class="text-center">
    <a href="{{ route('login') }}" class="d-flex justify-content-center">
      <i class="icon-base ti tabler-chevron-left scaleX-n1-rtl me-1_5"></i>{{ __('Back to login') }}
    </a>
  </div>
@endsection

@section('content')
  @include('auth._shell')
@endsection
