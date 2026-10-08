@extends('layouts/layoutMaster')

@section('title', __('Categories & specs'))

@section('content')
  <div class="d-flex justify-content-between align-items-center mb-6">
    <div>
      <h4 class="mb-1">{{ __('Categories & specs') }}</h4>
      <p class="mb-0 text-body-secondary">{{ __('Each category has a spec template. The product form shows only these fields.') }}</p>
    </div>
    @can('create', App\Models\Category::class)
      <a href="{{ route('categories.create') }}" class="btn btn-primary"><i class="icon-base ti tabler-plus me-1"></i>{{ __('Add category') }}</a>
    @endcan
  </div>
  <div class="card">
    <div class="table-responsive">
      <table class="table">
        <thead><tr><th>{{ __('Category') }}</th><th class="text-end">{{ __('Spec fields') }}</th><th>{{ __('Type') }}</th><th></th></tr></thead>
        <tbody>
          @foreach ($categories as $category)
            <tr>
              <td class="fw-medium text-heading">{{ __($category->name) }}</td>
              <td class="text-end">{{ $category->spec_fields_count }}</td>
              <td>
                @if ($category->isSystem())
                  <span class="badge bg-label-secondary">{{ __('Built-in') }}</span>
                @else
                  <span class="badge bg-label-primary">{{ __('Custom') }}</span>
                @endif
              </td>
              <td class="text-end text-nowrap">
                @can('update', $category)
                  <a href="{{ route('categories.edit', $category) }}" class="btn btn-icon btn-text-secondary rounded-pill" title="{{ __('Edit') }}"><i class="icon-base ti tabler-edit"></i></a>
                  <form method="POST" action="{{ route('categories.destroy', $category) }}" class="d-inline" onsubmit="return confirm(@js(__('Delete this category?')))">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-icon btn-text-danger rounded-pill" title="{{ __('Delete') }}"><i class="icon-base ti tabler-trash"></i></button>
                  </form>
                @endcan
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div class="card-body">{{ $categories->links() }}</div>
  </div>
@endsection
