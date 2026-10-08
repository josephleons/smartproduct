<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Payment> */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'company_id' => fn (array $a) => Invoice::withoutGlobalScopes()->find($a['invoice_id'])->company_id,
            'amount' => fn (array $a) => Invoice::withoutGlobalScopes()->find($a['invoice_id'])->amount,
            'gateway' => 'manual',
            'method' => PaymentMethod::Mpesa,
            'reference' => strtoupper(fake()->bothify('??##??##??')),
            'status' => PaymentStatus::Pending,
        ];
    }
}
