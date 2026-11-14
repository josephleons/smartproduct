<?php

namespace Database\Factories;

use App\Enums\SaleMethod;
use App\Enums\SaleStatus;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Sale> */
class SaleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'company_id' => fn (array $a) => Product::withoutGlobalScopes()->find($a['product_id'])->company_id,
            'branch_id' => fn (array $a) => Product::withoutGlobalScopes()->find($a['product_id'])->branch_id,
            'reference' => Str::upper(Str::random(6)),
            'method' => SaleMethod::Cash,
            'customer_name' => fake()->name(),
            'customer_phone' => '2557'.fake()->numerify('########'),
            'listed_price' => 850000,
            'amount' => 800000,
            'status' => SaleStatus::Pending,
            'ip_hash' => hash('sha256', fake()->ipv4()),
        ];
    }

    public function mobile(string $network = 'mpesa'): static
    {
        return $this->state(fn () => [
            'method' => SaleMethod::Mobile,
            'network' => $network,
            'payment_number' => '123456',
            'transaction_code' => Str::upper(Str::random(10)),
        ]);
    }
}
