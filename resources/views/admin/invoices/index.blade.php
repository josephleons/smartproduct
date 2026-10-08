@extends('layouts/layoutMaster')

@section('title', __('Invoices'))

@section('content')
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-4 mb-6">
    <h4 class="mb-0">{{ __('Invoices') }}</h4>
    <div class="d-flex flex-wrap gap-2">
      <a href="{{ route('admin.invoices.index', ['awaiting' => 1]) }}" class="btn btn-sm {{ request('awaiting') ? 'btn-warning' : 'btn-label-warning' }}">
        {{ __('Awaiting confirmation') }} <span class="badge bg-white text-warning ms-1">{{ $awaiting }}</span>
      </a>
      <a href="{{ route('admin.invoices.index') }}" class="btn btn-sm {{ !request()->hasAny(['status', 'awaiting']) ? 'btn-primary' : 'btn-label-secondary' }}">{{ __('All') }}</a>
      @foreach ($statuses as $status)
        <a href="{{ route('admin.invoices.index', ['status' => $status->value]) }}" class="btn btn-sm {{ request('status') === $status->value ? 'btn-primary' : 'btn-label-secondary' }}">{{ $status->label() }}</a>
      @endforeach
    </div>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>{{ __('Bill') }}</th>
            <th>{{ __('Company') }}</th>
            <th>{{ __('Period') }}</th>
            <th>{{ __('Due') }}</th>
            <th class="text-end">{{ __('Amount') }}</th>
            <th>{{ __('Status') }}</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @forelse ($invoices as $invoice)
            <tr>
              <td><a href="{{ route('admin.invoices.show', $invoice->id) }}" class="fw-medium">{{ $invoice->number }}</a><br><small class="text-body-secondary">{{ $invoice->plan_name }}</small></td>
              <td>{{ $invoice->company->name }}</td>
              <td class="text-nowrap">{{ $invoice->period_start->format('d M') }} – {{ $invoice->period_end->format('d M Y') }}</td>
              <td class="text-nowrap">{{ $invoice->due_date->format('d M Y') }}</td>
              <td class="text-end text-nowrap">{{ money($invoice->amount) }}</td>
              <td><x-money-badge :status="$invoice->status" /></td>
              <td>
                @if ($invoice->pending_payments_count)
                  <a href="{{ route('admin.invoices.show', $invoice->id) }}" class="badge bg-warning">{{ __('Payment to confirm') }}</a>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center text-body-secondary py-6">{{ __('No invoices.') }}</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="card-body">{{ $invoices->links() }}</div>
  </div>
@endsection
