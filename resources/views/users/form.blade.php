@extends('layouts/layoutMaster')

@section('title', $user->exists ? __('Edit user') : __('Add user'))

@section('content')
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-header"><h5 class="card-title mb-0">{{ $user->exists ? __('Edit user') : __('Add user') }}</h5></div>
        <div class="card-body">
          <form method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}">
            @csrf
            @if ($user->exists)
              @method('PUT')
            @endif
            <div class="row g-4">
              <div class="col-md-6">
                <label class="form-label" for="name">{{ __('Name') }} *</label>
                <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="col-md-6">
                <label class="form-label" for="email">{{ __('Email') }} *</label>
                <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" class="form-control @error('email') is-invalid @enderror" required>
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="col-md-6">
                <label class="form-label" for="role">{{ __('Role') }} *</label>
                <select id="role" name="role" class="form-select @error('role') is-invalid @enderror" @disabled($user->is(auth()->user()))>
                  @foreach ($roles as $role)
                    <option value="{{ $role->value }}" @selected(old('role', $user->role?->value) === $role->value)>{{ $role->label() }}</option>
                  @endforeach
                </select>
                @if ($user->is(auth()->user()))
                  <input type="hidden" name="role" value="{{ $user->role->value }}">
                @endif
                <small class="text-body-secondary">{{ __('Managers handle products, labels, branches and analytics. Staff add products and print labels.') }}</small>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="branch_id">{{ __('Branch access') }}</label>
                <select id="branch_id" name="branch_id" class="form-select @error('branch_id') is-invalid @enderror">
                  <option value="">{{ __('All branches') }}</option>
                  @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}" @selected(old('branch_id', $user->branch_id) == $branch->id)>{{ $branch->name }}</option>
                  @endforeach
                </select>
                @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="col-md-6">
                <label class="form-label" for="password">{{ $user->exists ? __('New password (optional)') : __('Password') . ' *' }}</label>
                <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password">
                @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
              </div>
              <div class="col-md-6">
                <label class="form-label" for="password_confirmation">{{ __('Confirm password') }}</label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" autocomplete="new-password">
              </div>
              <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary" type="submit">{{ __('Save') }}</button>
                <a href="{{ route('users.index') }}" class="btn btn-label-secondary">{{ __('Cancel') }}</a>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection
