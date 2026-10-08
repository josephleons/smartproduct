@extends('layouts/layoutMaster')

@section('title', __('Plans'))

@section('content')
  <div class="d-flex justify-content-between align-items-center mb-6">
    <div>
      <h4 class="mb-1">{{ __('Plans') }}</h4>
      <p class="mb-0 text-body-secondary">{{ __('Monthly price and scope of usage for each subscription plan.') }}</p>
    </div>
    <a href="{{ route('admin.plans.create') }}" class="btn btn-primary"><i class="icon-base ti tabler-plus me-1"></i>{{ __('Add plan') }}</a>
  </div>

  <div class="row g-6">
    @foreach ($plans as $plan)
      <div class="col-md-6 col-xl-4">
        <div class="card h-100">
          <div class="card-header d-flex justify-content-between align-items-start">
            <div>
              <h5 class="mb-1">{{ $plan->name }}</h5>
              <small class="text-body-secondary">{{ $plan->description }}</small>
            </div>
            <span class="badge bg-label-{{ $plan->is_active ? 'success' : 'secondary' }}">{{ $plan->is_active ? __('Active') : __('Hidden') }}</span>
          </div>
          <div class="card-body">
            <h3 class="text-primary mb-4">{{ money($plan->monthly_price) }} <small class="fs-6 text-body-secondary">/ {{ __('month') }}</small></h3>
            <ul class="list-unstyled mb-4">
              <li class="mb-1"><i class="icon-base ti tabler-devices me-2"></i>{{ $plan->max_products ? __(':count products', ['count' => number_format($plan->max_products)]) : __('Unlimited products') }}</li>
              <li class="mb-1"><i class="icon-base ti tabler-building-store me-2"></i>{{ $plan->max_branches ? trans_choice(':count branch|:count branches', $plan->max_branches) : __('Unlimited branches') }}</li>
              <li class="mb-1"><i class="icon-base ti tabler-users me-2"></i>{{ $plan->max_users ? trans_choice(':count user|:count users', $plan->max_users) : __('Unlimited users') }}</li>
            </ul>
            @foreach (App\Enums\PlanFeature::cases() as $feature)
              <div class="small mb-1 {{ $plan->hasFeature($feature) ? '' : 'text-body-secondary text-decoration-line-through' }}">
                <i class="icon-base ti {{ $plan->hasFeature($feature) ? 'tabler-check text-success' : 'tabler-x' }} me-1"></i>{{ $feature->label() }}
                @if ($feature->comingSoon())<span class="badge bg-label-info ms-1">{{ __('Soon') }}</span>@endif
              </div>
            @endforeach
          </div>
          <div class="card-footer d-flex justify-content-between align-items-center">
            <small class="text-body-secondary">{{ trans_choice(':count company|:count companies', $plan->subscriptions_count) }}</small>
            <a href="{{ route('admin.plans.edit', $plan) }}" class="btn btn-sm btn-label-primary">{{ __('Edit') }}</a>
          </div>
        </div>
      </div>
    @endforeach
  </div>
@endsection
