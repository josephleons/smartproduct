<?php

namespace App\Http\Requests\Admin;

use App\Enums\PlanFeature;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlanRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $merge = ['is_active' => $this->boolean('is_active'), 'features' => $this->input('features', [])];

        if (is_string($this->input('monthly_price'))) {
            $merge['monthly_price'] = preg_replace('/[^\d]/', '', $this->input('monthly_price'));
        }

        // Empty limit = unlimited.
        foreach (['max_branches', 'max_users', 'max_products'] as $limit) {
            if ($this->input($limit) === '') {
                $merge[$limit] = null;
            }
        }

        $this->merge($merge);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('plans', 'name')->ignore($this->route('plan'))],
            'description' => ['nullable', 'string', 'max:255'],
            'monthly_price' => ['required', 'integer', 'min:0', 'max:100000000'],
            'max_branches' => ['nullable', 'integer', 'min:1'],
            'max_users' => ['nullable', 'integer', 'min:1'],
            'max_products' => ['nullable', 'integer', 'min:1'],
            'features' => ['array'],
            'features.*' => [Rule::enum(PlanFeature::class)],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ];
    }
}
