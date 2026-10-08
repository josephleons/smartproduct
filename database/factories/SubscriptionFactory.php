<?php

namespace Database\Factories;

use App\Enums\SubscriptionStatus;
use App\Models\Company;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Subscription> */
class SubscriptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            // CompanyFactory already attaches a subscription; drop it so this one is the company's only one.
            'company_id' => fn () => tap(Company::factory()->create(), fn (Company $c) => $c->subscription()->delete())->id,
            'plan_id' => Plan::factory(),
            'status' => SubscriptionStatus::Active,
            'paid_until' => now()->addMonth()->toDateString(),
        ];
    }
}
