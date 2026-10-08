<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BillingSettingsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'trial_days' => ['required', 'integer', 'min:0', 'max:365'],
            'grace_days' => ['required', 'integer', 'min:0', 'max:90'],
            'invoice_days_before' => ['required', 'integer', 'min:0', 'max:28'],
            'gateway' => ['required', Rule::in(array_keys(config('billing.gateways')))],
            'payment_instructions' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
