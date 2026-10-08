@extends('layouts/layoutMaster')

@section('title', __('Branches'))

@section('content')
  <div class="d-flex justify-content-between align-items-center mb-6">
    <h4 class="mb-0">{{ __('Branches') }}</h4>
    <a href="{{ route('branches.create') }}" class="btn btn-primary"><i class="icon-base ti tabler-plus me-1"></i>{{ __('Add branch') }}</a>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>{{ __('Branch') }}</th>
            <th>{{ __('Phone') }}</th>
            <th>{{ __('WhatsApp') }}</th>
            <th class="text-end">{{ __('Products') }}</th>
            <th class="text-end">{{ __('Users') }}</th>
            <th>{{ __('Status') }}</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @foreach ($branches as $branch)
            <tr>
              <td><span class="fw-medium text-heading">{{ $branch->name }}</span><br><small class="text-body-secondary">{{ $branch->location }}</small></td>
              <td>{{ $branch->phone ?: '—' }}</td>
              <td>{{ $branch->whatsapp ?: '—' }}</td>
              <td class="text-end">{{ $branch->products_count }}</td>
              <td class="text-end">{{ $branch->users_count }}</td>
              <td><span class="badge bg-label-{{ $branch->is_active ? 'success' : 'secondary' }}">{{ $branch->is_active ? __('Active') : __('Inactive') }}</span></td>
              <td class="text-end text-nowrap">
                <a href="{{ route('branches.edit', $branch) }}" class="btn btn-icon btn-text-secondary rounded-pill" title="{{ __('Edit') }}"><i class="icon-base ti tabler-edit"></i></a>
                <form method="POST" action="{{ route('branches.destroy', $branch) }}" class="d-inline" onsubmit="return confirm(@js(__('Delete this branch?')))">
                  @csrf
                  @method('DELETE')
                  <button class="btn btn-icon btn-text-danger rounded-pill" title="{{ __('Delete') }}"><i class="icon-base ti tabler-trash"></i></button>
                </form>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div class="card-body">{{ $branches->links() }}</div>
  </div>
@endsection
