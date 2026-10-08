@extends('layouts/layoutMaster')

@section('title', __('Companies'))

@section('content')
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-4 mb-6">
    <div>
      <h4 class="mb-1">{{ __('Companies') }}</h4>
      <p class="mb-0 text-body-secondary">{{ __('Shops using SmartLabel. New companies are created here.') }}</p>
    </div>
    <a href="{{ route('admin.companies.create') }}" class="btn btn-primary"><i class="icon-base ti tabler-plus me-1"></i>{{ __('Add company') }}</a>
  </div>

  <div class="card">
    <div class="card-body border-bottom">
      <form method="GET" class="row g-3">
        <div class="col-md-6">
          <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="{{ __('Search name, email or phone') }}">
        </div>
        <div class="col-md-3">
          <select name="status" class="form-select">
            <option value="">{{ __('Any subscription status') }}</option>
            @foreach (App\Enums\SubscriptionStatus::cases() as $status)
              <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2"><button class="btn btn-label-primary w-100" type="submit">{{ __('Search') }}</button></div>
      </form>
    </div>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>{{ __('Company') }}</th>
            <th>{{ __('Owner') }}</th>
            <th>{{ __('Plan') }}</th>
            <th>{{ __('Subscription') }}</th>
            <th class="text-end">{{ __('Branches') }}</th>
            <th class="text-end">{{ __('Users') }}</th>
            <th class="text-end">{{ __('Products') }}</th>
            <th>{{ __('Status') }}</th>
            <th>{{ __('Created') }}</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @forelse ($companies as $company)
            @php($owner = $company->users->first())
            <tr>
              <td><span class="fw-medium text-heading">{{ $company->name }}</span><br><small class="text-body-secondary">{{ $company->phone }}</small></td>
              <td>{{ $owner?->name ?? '—' }}<br><small class="text-body-secondary">{{ $owner?->email }}</small></td>
              <td>{{ $company->subscription?->plan?->name ?? '—' }}</td>
              <td><x-subscription-badge :status="$company->subscription?->status" />
                @if ($company->subscription)<br><small class="text-body-secondary">{{ __('until :date', ['date' => $company->subscription->paid_until->format('d M Y')]) }}</small>@endif
              </td>
              <td class="text-end">{{ $company->branches_count }}</td>
              <td class="text-end">{{ $company->users_count }}</td>
              <td class="text-end">{{ $company->products_count }}</td>
              <td><span class="badge bg-label-{{ $company->is_active ? 'success' : 'secondary' }}">{{ $company->is_active ? __('Active') : __('Inactive') }}</span></td>
              <td class="text-nowrap">{{ $company->created_at->format('d M Y') }}</td>
              <td class="text-end">
                <a href="{{ route('admin.companies.edit', $company) }}" class="btn btn-icon btn-text-secondary rounded-pill" title="{{ __('Edit') }}"><i class="icon-base ti tabler-edit"></i></a>
              </td>
            </tr>
          @empty
            <tr><td colspan="10" class="text-center text-body-secondary py-6">{{ __('No companies yet.') }}</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="card-body">{{ $companies->links() }}</div>
  </div>
@endsection
