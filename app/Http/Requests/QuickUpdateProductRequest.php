<?php

namespace App\Http\Requests;

use App\Enums\ProductStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Price and availability changes from the product page, without the full wizard. */
class QuickUpdateProductRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('price'))) {
            $this->merge(['price' => preg_replace('/[^\d]/', '', $this->input('price'))]);
        }
    }

    public function rules(): array
    {
        return [
            'price' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'quantity' => ['required', 'integer', 'min:0', 'max:100000'],
            'status' => ['required', Rule::enum(ProductStatus::class)],
        ];
    }
}
