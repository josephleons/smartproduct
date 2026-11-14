<?php

namespace App\Http\Requests;

use App\Models\CategorySpecField;
use App\Services\ProductRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;

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
        return ProductRules::rules($this->user(), $this->specFields());
    }

    public function attributes(): array
    {
        return ProductRules::attributes($this->specFields());
    }

    /** @return Collection<int, CategorySpecField> */
    private function specFields(): Collection
    {
        return once(fn () => ProductRules::specFields($this->user(), $this->integer('category_id')));
    }
}
