<?php

namespace App\Http\Requests;

use App\Services\LipaNamba;
use Illuminate\Foundation\Http\FormRequest;

class BranchRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'location' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'is_active' => ['boolean'],
            ...LipaNamba::rules(),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'payment_numbers' => LipaNamba::clean($this->input('payment_numbers')),
        ]);
    }
}
