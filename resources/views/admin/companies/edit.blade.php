@extends('layouts/layoutMaster')

@section('title', __('Edit company'))

@section('content')
  <div class="row g-6">
    <div class="col-lg-7">
      <div class="card">
        <div class="card-header"><h5 class="card-title mb-0">{{ $company->name }}</h5></div>
        <div class="card-body">
          <form method="POST" action="{{ route('admin.companies.update', $company) }}">
            @csrf
            @method('PUT')
            <div class="row g-4">
              @include('admin.companies._company-fields', ['company' => $company])
              <div class="col-12">
                <label class="form-label" for="plan_id">{{ __('Subscription plan') }}</label>
                <select id="plan_id" name="plan_id" class="form-select @error('plan_id') is-invalid @enderror">
                  @foreach ($plans as $plan)
                    <option value="{{ $plan->id }}" @selected(old('plan_id', $company->subscription?->plan_id) == $plan->id)>{{ $plan->name }} · {{ money($plan->monthly_price) }}</option>
                  @endforeach
                </select>
                <small class="text-body-secondary">{{ __('Limits apply immediately; the new price applies from the next bill.') }}</small>
              </div>
              <div class="col-12">
                <div class="form-check form-switch">
                  <input type="hidden" name="is_active" value="0">
                  <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $company->is_active))>
                  <label class="form-check-label" for="is_active">{{ __('Active') }}</label>
                </div>
                <small class="text-body-secondary">{{ __('Users of an inactive company cannot log in to the dashboard. Printed labels keep working.') }}</small>
              </div>
              <div class="col-12 d-flex gap-2">
                <button class="btn btn-primary" type="submit">{{ __('Save') }}</button>
                <a href="{{ route('admin.companies.index') }}" class="btn btn-label-secondary">{{ __('Cancel') }}</a>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
    <div class="col-lg-5">
      @php($subscription = $company->subscription)
      <div class="card mb-6">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0">{{ __('Subscription') }}</h5>
          <x-subscription-badge :status="$subscription?->status" />
        </div>
        @if ($subscription)
          <div class="card-body">
            <div class="d-flex justify-content-between mb-2"><span>{{ __('Plan') }}</span><strong>{{ $subscription->plan->name }}</strong></div>
            <div class="d-flex justify-content-between mb-2"><span>{{ __('Paid until') }}</span><span>{{ $subscription->paid_until->format('d M Y') }}</span></div>
            @if ($subscription->grace_ends_at)
              <div class="d-flex justify-content-between mb-2"><span>{{ __('Grace ends') }}</span><span>{{ $subscription->grace_ends_at->format('d M Y') }}</span></div>
            @endif
            <hr>
            @foreach ($usage as $resource => $row)
              <div class="d-flex justify-content-between mb-1"><span>{{ __(ucfirst($resource)) }}</span><span>{{ $row['used'] }} / {{ $row['limit'] ?? '∞' }}</span></div>
            @endforeach
            <a href="{{ route('admin.invoices.index', ['company' => $company->id]) }}" class="btn btn-sm btn-label-primary mt-4">{{ __('View bills') }}</a>
          </div>
        @endif
      </div>
      <div class="card">
        <div class="card-header"><h5 class="card-title mb-0">{{ __('Users') }}</h5></div>
        <ul class="list-group list-group-flush">
          @foreach ($company->users as $user)
            <li class="list-group-item d-flex justify-content-between">
              <span>{{ $user->name }}<br><small class="text-body-secondary">{{ $user->email }}</small></span>
              <span class="badge bg-label-primary align-self-center">{{ $user->role->label() }}</span>
            </li>
          @endforeach
        </ul>
      </div>
    </div>
  </div>
@endsection
