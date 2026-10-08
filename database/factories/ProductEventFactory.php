<?php

namespace Database\Factories;

use App\Enums\DeviceType;
use App\Enums\EventType;
use App\Models\Product;
use App\Models\ProductEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProductEvent> */
class ProductEventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'company_id' => fn (array $a) => Product::withoutGlobalScopes()->find($a['product_id'])->company_id,
            'branch_id' => fn (array $a) => Product::withoutGlobalScopes()->find($a['product_id'])->branch_id,
            'type' => EventType::Scan,
            'ip_hash' => hash('sha256', fake()->ipv4()),
            'device_type' => DeviceType::Mobile,
            'is_unique' => true,
            'created_at' => now(),
        ];
    }
}
