<?php

namespace App\Http\Requests;

use App\Enums\ProductCondition;
use App\Enums\ProductStatus;
use App\Enums\SpecFieldType;
use App\Models\Category;
use App\Models\CategorySpecField;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/** Shared rules for creating and editing a product, including the category's spec template. */
class ProductRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        // Accept "850,000" or "850 000" from the price input.
        if (is_string($this->input('price'))) {
            $this->merge(['price' => preg_replace('/[^\d]/', '', $this->input('price'))]);
        }
    }

    public function rules(): array
    {
        $user = $this->user();

        return [
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where(
                fn (Builder $q) => $q->where(fn (Builder $w) => $w->whereNull('company_id')->orWhere('company_id', $user->company_id))
            )],
            'branch_id' => array_filter([
                'required', 'integer',
                Rule::exists('branches', 'id')->where('company_id', $user->company_id)->where('is_active', true),
                $user->branch_id ? Rule::in([$user->branch_id]) : null,
            ]),
            'brand' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'condition' => ['required', Rule::enum(ProductCondition::class)],
            'price' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'quantity' => ['required', 'integer', 'min:0', 'max:100000'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'warranty_months' => ['nullable', 'integer', 'min:0', 'max:120'],
            'status' => ['required', Rule::enum(ProductStatus::class)],
            'specs' => ['nullable', 'array'],
            ...$this->specRules(),
        ];
    }

    public function attributes(): array
    {
        $attributes = ['category_id' => __('category'), 'branch_id' => __('branch')];

        foreach ($this->specFields() as $field) {
            $attributes['specs.'.$field->key] = __($field->label);
        }

        return $attributes;
    }

    /** @return array<string, array<int, mixed>> */
    private function specRules(): array
    {
        $rules = [];

        foreach ($this->specFields() as $field) {
            $rules['specs.'.$field->key] = [
                $field->is_required ? 'required' : 'nullable',
                ...match ($field->type) {
                    SpecFieldType::Number => ['numeric', 'min:0'],
                    SpecFieldType::Select => [Rule::in($field->options ?? [])],
                    SpecFieldType::Boolean => ['boolean'],
                    SpecFieldType::Text => ['string', 'max:191'],
                },
            ];
        }

        return $rules;
    }

    /** @return Collection<int, CategorySpecField> */
    private function specFields()
    {
        return once(fn () => Category::visibleTo($this->user()->company_id)
            ->find($this->integer('category_id'))
            ?->specFields ?? collect());
    }
}
