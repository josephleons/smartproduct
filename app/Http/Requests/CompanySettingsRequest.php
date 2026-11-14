<?php

namespace App\Http\Requests;

use App\Services\LipaNamba;
use Illuminate\Foundation\Http\FormRequest;

class CompanySettingsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:1024'],
            'remove_logo' => ['nullable', 'boolean'],
            ...LipaNamba::rules(),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['payment_numbers' => LipaNamba::clean($this->input('payment_numbers'))]);
    }
}
