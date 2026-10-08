@extends('layouts/layoutMaster')

@php
  use App\Enums\SpecFieldType;

  $fields = old('fields', $category->exists
      ? $category->specFields->map(fn ($f) => [
          'label' => $f->label,
          'type' => $f->type->value,
          'unit' => $f->unit,
          'options' => implode(', ', $f->options ?? []),
          'is_required' => $f->is_required,
          'show_on_label' => $f->show_on_label,
      ])->all()
      : []);
@endphp

@section('title', $category->exists ? __('Edit category') : __('Add category'))

@section('content')
  <form method="POST" action="{{ $category->exists ? route('categories.update', $category) : route('categories.store') }}">
    @csrf
    @if ($category->exists)
      @method('PUT')
    @endif

    <div class="card mb-6">
      <div class="card-header"><h5 class="card-title mb-0">{{ $category->exists ? __('Edit category') : __('Add category') }}</h5></div>
      <div class="card-body">
        <div class="row g-4">
          <div class="col-md-6">
            <label class="form-label" for="name">{{ __('Name') }} *</label>
            <input type="text" id="name" name="name" value="{{ old('name', $category->name) }}" class="form-control @error('name') is-invalid @enderror" placeholder="Tablet" required>
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
          </div>
        </div>
        <p class="text-body-secondary small mt-4 mb-0">{{ __('Brand, model, condition, warranty and price are on every product, so you do not need to add them here.') }}</p>
      </div>
    </div>

    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">{{ __('Spec fields') }}</h5>
        <button type="button" class="btn btn-sm btn-label-primary" data-add-field><i class="icon-base ti tabler-plus me-1"></i>{{ __('Add field') }}</button>
      </div>
      <div class="card-body" id="fieldRows">
        @foreach ($fields as $i => $field)
          @include('categories._field', ['i' => $i, 'field' => $field])
        @endforeach
      </div>
      <div class="card-body border-top d-flex gap-2">
        <button class="btn btn-primary" type="submit">{{ __('Save') }}</button>
        <a href="{{ route('categories.index') }}" class="btn btn-label-secondary">{{ __('Cancel') }}</a>
      </div>
    </div>
  </form>

  <template id="fieldTemplate">
    @include('categories._field', ['i' => '__I__', 'field' => ['type' => 'text']])
  </template>
@endsection

@section('page-script')
  <script>
    (() => {
      const rows = document.getElementById('fieldRows');
      const template = document.getElementById('fieldTemplate').innerHTML;
      let next = {{ count($fields) }};
      document.querySelector('[data-add-field]').addEventListener('click', () => {
        rows.insertAdjacentHTML('beforeend', template.replaceAll('__I__', next++));
      });
      rows.addEventListener('click', e => {
        const btn = e.target.closest('[data-remove-field]');
        if (btn) btn.closest('[data-field-row]').remove();
      });
    })();
  </script>
@endsection
