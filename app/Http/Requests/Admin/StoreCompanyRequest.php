<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreCompanyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'branch_name' => ['required', 'string', 'max:150'],
            'branch_location' => ['nullable', 'string', 'max:255'],
            'owner_name' => ['required', 'string', 'max:150'],
            'owner_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'owner_password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    public function attributes(): array
    {
        return [
            'branch_name' => __('first branch'),
            'owner_name' => __('owner name'),
            'owner_email' => __('owner email'),
            'owner_password' => __('owner password'),
        ];
    }
}
