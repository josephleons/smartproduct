<?php

namespace Database\Seeders;

use App\Enums\PlanFeature;
use App\Models\Plan;
use Illuminate\Database\Seeder;

/**
 * Starting plans. Prices and limits are placeholders (0 = free, null = unlimited):
 * the superadmin sets the real values at Admin > Plans. Existing plans are never overwritten.
 */
class PlanSeeder extends Seeder
{
    public function run(): void
    {
        Plan::firstOrCreate(['slug' => 'normal'], [
            'name' => 'Normal',
            'description' => 'Product pages, QR labels and scan analytics.',
            'monthly_price' => 0,
            'features' => [PlanFeature::Analytics->value, PlanFeature::CustomCategories->value],
            'sort_order' => 1,
        ]);

        Plan::firstOrCreate(['slug' => 'premium'], [
            'name' => 'Premium',
            'description' => 'Everything in Normal, plus SMS to customers and WhatsApp orders.',
            'monthly_price' => 0,
            'features' => array_map(fn (PlanFeature $f) => $f->value, PlanFeature::cases()),
            'sort_order' => 2,
        ]);
    }
}
