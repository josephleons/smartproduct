<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Staff confirm how much actually reached the shop (cash in hand or the Lipa Namba SMS). */
class ApproveSaleRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('received_amount'))) {
            $this->merge(['received_amount' => preg_replace('/[^\d]/', '', $this->input('received_amount'))]);
        }
    }

    public function rules(): array
    {
        return [
            'received_amount' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return ['received_amount' => __('amount received')];
    }
}
