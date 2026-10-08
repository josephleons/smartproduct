<div class="row g-3 align-items-end border-bottom pb-4 mb-4" data-field-row>
  <div class="col-md-3">
    <label class="form-label">{{ __('Label') }}</label>
    <input type="text" name="fields[{{ $i }}][label]" value="{{ $field['label'] ?? '' }}" class="form-control @error("fields.$i.label") is-invalid @enderror" placeholder="RAM" required>
    @error("fields.$i.label") <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>
  <div class="col-md-2">
    <label class="form-label">{{ __('Type') }}</label>
    <select name="fields[{{ $i }}][type]" class="form-select">
      @foreach (App\Enums\SpecFieldType::cases() as $type)
        <option value="{{ $type->value }}" @selected(($field['type'] ?? 'text') === $type->value)>{{ $type->label() }}</option>
      @endforeach
    </select>
  </div>
  <div class="col-md-1">
    <label class="form-label">{{ __('Unit') }}</label>
    <input type="text" name="fields[{{ $i }}][unit]" value="{{ $field['unit'] ?? '' }}" class="form-control" placeholder="GB">
  </div>
  <div class="col-md-3">
    <label class="form-label">{{ __('Options (dropdown)') }}</label>
    <input type="text" name="fields[{{ $i }}][options]" value="{{ is_array($field['options'] ?? null) ? implode(', ', $field['options']) : ($field['options'] ?? '') }}" class="form-control @error("fields.$i.options") is-invalid @enderror" placeholder="4G, 5G">
    @error("fields.$i.options") <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>
  <div class="col-md-2">
    <div class="form-check">
      <input type="hidden" name="fields[{{ $i }}][is_required]" value="0">
      <input class="form-check-input" type="checkbox" name="fields[{{ $i }}][is_required]" value="1" id="req_{{ $i }}" @checked($field['is_required'] ?? false)>
      <label class="form-check-label" for="req_{{ $i }}">{{ __('Required') }}</label>
    </div>
    <div class="form-check">
      <input type="hidden" name="fields[{{ $i }}][show_on_label]" value="0">
      <input class="form-check-input" type="checkbox" name="fields[{{ $i }}][show_on_label]" value="1" id="lbl_{{ $i }}" @checked($field['show_on_label'] ?? false)>
      <label class="form-check-label" for="lbl_{{ $i }}">{{ __('On label') }}</label>
    </div>
  </div>
  <div class="col-md-1 text-end">
    <button type="button" class="btn btn-icon btn-label-danger" data-remove-field title="{{ __('Remove') }}"><i class="icon-base ti tabler-trash"></i></button>
  </div>
</div>
