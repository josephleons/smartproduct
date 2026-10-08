<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\QrCode;
use App\Services\QrTokenGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<QrCode> */
class QrCodeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'token' => app(QrTokenGenerator::class)->random(),
            'scans_count' => 0,
        ];
    }
}
