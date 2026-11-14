<?php

namespace App\Http\Requests;

use App\Enums\SaleMethod;
use App\Services\LipaNamba;
use App\Services\PhoneNumber;
use App\Services\PublicProductLookup;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** The customer's "I have paid" form on the QR page. */
class PaySaleRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('amount'))) {
            $this->merge(['amount' => preg_replace('/[^\d]/', '', $this->input('amount'))]);
        }
    }

    public function rules(): array
    {
        $product = PublicProductLookup::find((string) $this->route('token'));
        $networks = $product ? array_keys(LipaNamba::for($product->company, $product->branch)) : [];

        return [
            'method' => ['required', Rule::enum(SaleMethod::class)],
            'network' => ['nullable', 'required_if:method,mobile', Rule::in($networks)],
            'transaction_code' => ['nullable', 'required_if:method,mobile', 'string', 'regex:/^[A-Za-z0-9]{6,20}$/'],
            'amount' => ['required', 'integer', 'min:1', 'max:4294967295'],
            'customer_name' => ['required', 'string', 'min:2', 'max:100'],
            'customer_phone' => ['required', 'string', 'max:20', function (string $attribute, mixed $value, Closure $fail) {
                if (! preg_match('/^255[67]\d{8}$/', (string) PhoneNumber::international((string) $value))) {
                    $fail(__('Enter a Tanzanian mobile number, e.g. 0712 345 678.'));
                }
            }],
        ];
    }

    public function attributes(): array
    {
        return [
            'method' => __('payment method'),
            'network' => __('network'),
            'transaction_code' => __('transaction code'),
            'amount' => __('amount'),
            'customer_name' => __('your name'),
            'customer_phone' => __('your phone number'),
        ];
    }

    public function messages(): array
    {
        return [
            'network.required_if' => __('Choose the network you paid with.'),
            'transaction_code.required_if' => __('Enter the transaction code from your payment SMS.'),
            'transaction_code.regex' => __('The transaction code has 6 to 20 letters and numbers, e.g. QJK3X8Y2LM.'),
        ];
    }
}
