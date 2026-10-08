<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\CategorySpecField;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CategorySpecField> */
class CategorySpecFieldFactory extends Factory
{
    public function definition(): array
    {
        $label = fake()->unique()->word();

        return [
            'category_id' => Category::factory(),
            'key' => strtolower($label),
            'label' => ucfirst($label),
            'type' => 'text',
            'options' => null,
            'unit' => null,
            'is_required' => false,
            'show_on_label' => false,
            'sort_order' => 0,
        ];
    }
}
