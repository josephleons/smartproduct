<?php

namespace App\Services;

use App\Enums\ProductCondition;
use App\Enums\ProductStatus;
use App\Enums\SpecFieldType;
use App\Models\Category;
use App\Models\CategorySpecField;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * Validation rules for a product and its category's spec template. Shared by the
 * Add/Edit Product form (ProductRequest) and bulk import, so both accept the same data.
 */
class ProductRules
{
    /** @return array<string, array<int, mixed>> */
    public static function rules(User $user, Collection $specFields): array
    {
        return [
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where(
                fn (Builder $q) => $q->where(fn (Builder $w) => $w->whereNull('company_id')->orWhere('company_id', $user->company_id))
            )],
            'branch_id' => array_values(array_filter([
                'required', 'integer',
                Rule::exists('branches', 'id')->where('company_id', $user->company_id)->where('is_active', true),
                $user->branch_id ? Rule::in([$user->branch_id]) : null,
            ])),
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
            ...self::specRules($specFields),
        ];
    }

    /** @return array<string, string> */
    public static function attributes(Collection $specFields): array
    {
        $attributes = ['category_id' => __('category'), 'branch_id' => __('branch')];

        foreach ($specFields as $field) {
            $attributes['specs.'.$field->key] = __($field->label);
        }

        return $attributes;
    }

    /** @return Collection<int, CategorySpecField> spec fields of a category the user may use, or none */
    public static function specFields(User $user, ?int $categoryId): Collection
    {
        return Category::visibleTo($user->company_id)->find($categoryId)?->specFields ?? collect();
    }

    /** @return array<string, array<int, mixed>> */
    private static function specRules(Collection $specFields): array
    {
        $rules = [];

        foreach ($specFields as $field) {
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
}
