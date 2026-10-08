<?php

namespace Database\Factories;

use App\Enums\ProductCondition;
use App\Enums\ProductStatus;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Services\QrTokenGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'company_id' => fn (array $attributes) => Branch::withoutGlobalScopes()->find($attributes['branch_id'])->company_id,
            'category_id' => Category::factory(),
            'brand' => fake()->randomElement(['Dell', 'HP', 'Lenovo', 'Samsung', 'Tecno']),
            'model' => fake()->bothify('Model ###?'),
            'condition' => ProductCondition::New,
            'price' => fake()->numberBetween(100, 2000) * 1000,
            'quantity' => 1,
            'warranty_months' => 6,
            'status' => ProductStatus::Available,
            'sku' => fake()->unique()->bothify('TZ-???####-###'),
        ];
    }

    /** Every product needs its permanent QR code. */
    public function configure(): static
    {
        return $this->afterCreating(function (Product $product) {
            $product->qrCode()->create(['token' => app(QrTokenGenerator::class)->generate()]);
        });
    }
}
