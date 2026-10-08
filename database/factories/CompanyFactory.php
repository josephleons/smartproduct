<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Company> */
class CompanyFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'phone' => '07'.fake()->numerify('########'),
            'whatsapp' => '07'.fake()->numerify('########'),
            'email' => fake()->companyEmail(),
            'address' => fake()->streetAddress(),
        ];
    }

    /** Companies come with an active, paid subscription on an unlimited plan unless a test says otherwise. */
    public function configure(): static
    {
        return $this->afterCreating(function (Company $company) {
            Subscription::withoutGlobalScopes()->create([
                'company_id' => $company->id,
                'plan_id' => Plan::factory()->create()->id,
                'status' => 'active',
                'paid_until' => now()->addMonth()->toDateString(),
            ]);
        });
    }
}
