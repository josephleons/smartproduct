@php
  $customizerHidden = 'customizer-hide';
@endphp

@extends('layouts/blankLayout')

@section('title', __('Reset password'))

@section('page-style')
  @vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
@endsection

@section('auth-card')
  <h4 class="mb-6">{{ __('Choose a new password') }}</h4>

  <form action="{{ route('password.update') }}" method="POST">
    @csrf
    <input type="hidden" name="token" value="{{ $request->route('token') }}">
    <div class="mb-4">
      <label for="email" class="form-label">{{ __('Email') }}</label>
      <input type="email" id="email" name="email" value="{{ old('email', $request->email) }}"
        class="form-control @error('email') is-invalid @enderror" required>
      @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="mb-4">
      <label for="password" class="form-label">{{ __('New password') }}</label>
      <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror"
        autocomplete="new-password" required>
      @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="mb-6">
      <label for="password_confirmation" class="form-label">{{ __('Confirm password') }}</label>
      <input type="password" id="password_confirmation" name="password_confirmation" class="form-control"
        autocomplete="new-password" required>
    </div>
    <button class="btn btn-primary d-grid w-100" type="submit">{{ __('Reset password') }}</button>
  </form>
@endsection

@section('content')
  @include('auth._shell')
@endsection
