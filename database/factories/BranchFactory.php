<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Branch> */
class BranchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->city(),
            'location' => fake()->streetAddress(),
            'phone' => '07'.fake()->numerify('########'),
            'whatsapp' => '07'.fake()->numerify('########'),
            'is_active' => true,
        ];
    }
}
