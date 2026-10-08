@php
  $customizerHidden = 'customizer-hide';
@endphp

@extends('layouts/blankLayout')

@section('title', __('Log in'))

@section('page-style')
  @vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
@endsection

@section('auth-card')
  <h4 class="mb-1">{{ __('Welcome back') }}</h4>
  <p class="mb-6">{{ __('Log in to manage your products and labels.') }}</p>

  @if (session('status'))
    <div class="alert alert-success" role="alert">{{ session('status') }}</div>
  @endif

  <form action="{{ route('login.store') }}" method="POST" class="mb-4">
    @csrf
    <div class="mb-4">
      <label for="email" class="form-label">{{ __('Email') }}</label>
      <input type="email" id="email" name="email" value="{{ old('email') }}"
        class="form-control @error('email') is-invalid @enderror" autocomplete="email" autofocus required>
      @error('email')
        <div class="invalid-feedback">{{ $message }}</div>
      @enderror
    </div>

    <div class="mb-4 form-password-toggle">
      <div class="d-flex justify-content-between">
        <label class="form-label" for="password">{{ __('Password') }}</label>
        <a href="{{ route('password.request') }}"><small>{{ __('Forgot password?') }}</small></a>
      </div>
      <div class="input-group input-group-merge">
        <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror"
          autocomplete="current-password" required>
        <span class="input-group-text cursor-pointer"><i class="icon-base ti tabler-eye-off"></i></span>
      </div>
      @error('password')
        <div class="invalid-feedback d-block">{{ $message }}</div>
      @enderror
    </div>

    <div class="mb-6 form-check">
      <input class="form-check-input" type="checkbox" id="remember" name="remember" value="1" @checked(old('remember'))>
      <label class="form-check-label" for="remember">{{ __('Keep me signed in') }}</label>
    </div>

    <button class="btn btn-primary d-grid w-100" type="submit">{{ __('Log in') }}</button>
  </form>

  <p class="text-center text-body-secondary small mb-0">{{ __('Need SmartLabel for your shop? Contact us to open a company account.') }}</p>
@endsection

@section('content')
  @include('auth._shell')
@endsection
