@extends('layouts/layoutMaster')

@section('title', __('Users'))

@section('content')
  <div class="d-flex justify-content-between align-items-center mb-6">
    <h4 class="mb-0">{{ __('Users') }}</h4>
    <a href="{{ route('users.create') }}" class="btn btn-primary"><i class="icon-base ti tabler-user-plus me-1"></i>{{ __('Add user') }}</a>
  </div>
  <div class="card">
    <div class="table-responsive">
      <table class="table">
        <thead><tr><th>{{ __('Name') }}</th><th>{{ __('Role') }}</th><th>{{ __('Branch') }}</th><th></th></tr></thead>
        <tbody>
          @foreach ($users as $user)
            <tr>
              <td><span class="fw-medium text-heading">{{ $user->name }}</span><br><small class="text-body-secondary">{{ $user->email }}</small></td>
              <td><span class="badge bg-label-primary">{{ $user->role->label() }}</span></td>
              <td>{{ $user->branch?->name ?? __('All branches') }}</td>
              <td class="text-end text-nowrap">
                <a href="{{ route('users.edit', $user) }}" class="btn btn-icon btn-text-secondary rounded-pill" title="{{ __('Edit') }}"><i class="icon-base ti tabler-edit"></i></a>
                @can('delete', $user)
                  <form method="POST" action="{{ route('users.destroy', $user) }}" class="d-inline" onsubmit="return confirm(@js(__('Remove this user?')))">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-icon btn-text-danger rounded-pill" title="{{ __('Remove') }}"><i class="icon-base ti tabler-trash"></i></button>
                  </form>
                @endcan
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div class="card-body">{{ $users->links() }}</div>
  </div>
@endsection
