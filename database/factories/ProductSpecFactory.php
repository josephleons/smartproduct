<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductSpec;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProductSpec> */
class ProductSpecFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'key' => 'ram',
            'label' => 'RAM',
            'value' => '16',
            'unit' => 'GB',
            'show_on_label' => true,
            'sort_order' => 1,
        ];
    }
}
