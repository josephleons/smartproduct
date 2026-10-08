<?php

namespace Database\Factories;

use App\Enums\PlanFeature;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Plan> */
class PlanFactory extends Factory
{
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->word()).' Plan';

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'monthly_price' => 30000,
            'max_branches' => null,
            'max_users' => null,
            'max_products' => null,
            'features' => array_map(fn (PlanFeature $f) => $f->value, PlanFeature::cases()),
            'is_active' => true,
        ];
    }
}
