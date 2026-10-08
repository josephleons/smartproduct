<?php

namespace App\Http\Requests;

use App\Enums\SpecFieldType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        // Options arrive as one comma-separated string per field.
        $fields = collect($this->input('fields', []))->map(function ($field) {
            $field['options'] = collect(explode(',', (string) ($field['options'] ?? '')))
                ->map(fn ($o) => trim($o))->filter()->values()->all();
            $field['is_required'] = filter_var($field['is_required'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $field['show_on_label'] = filter_var($field['show_on_label'] ?? false, FILTER_VALIDATE_BOOLEAN);

            return $field;
        })->values()->all();

        $this->merge(['fields' => $fields]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'icon' => ['nullable', 'string', 'max:50'],
            'fields' => ['array', 'max:30'],
            'fields.*.label' => ['required', 'string', 'max:100'],
            'fields.*.type' => ['required', Rule::enum(SpecFieldType::class)],
            'fields.*.unit' => ['nullable', 'string', 'max:20'],
            'fields.*.options' => ['array'],
            'fields.*.options.*' => ['string', 'max:100'],
            'fields.*.is_required' => ['boolean'],
            'fields.*.show_on_label' => ['boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            foreach ($this->input('fields', []) as $i => $field) {
                if (($field['type'] ?? null) === SpecFieldType::Select->value && empty($field['options'])) {
                    $validator->errors()->add("fields.$i.options", __('Dropdown fields need at least one option.'));
                }
            }
        });
    }
}
