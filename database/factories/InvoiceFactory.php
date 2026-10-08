<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Invoice> */
class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'subscription_id' => fn () => Company::factory()->create()->subscription->id,
            'company_id' => fn (array $a) => Subscription::withoutGlobalScopes()->find($a['subscription_id'])->company_id,
            'plan_id' => fn (array $a) => Subscription::withoutGlobalScopes()->find($a['subscription_id'])->plan_id,
            'plan_name' => 'Normal',
            'amount' => 30000,
            'period_start' => now()->toDateString(),
            'period_end' => now()->addMonth()->subDay()->toDateString(),
            'due_date' => now()->toDateString(),
            'status' => InvoiceStatus::Pending,
        ];
    }
}
