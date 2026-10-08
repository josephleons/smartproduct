<?php

namespace App\Actions;

use App\Enums\SpecFieldType;
use App\Models\Category;
use App\Models\Product;

class SyncProductSpecs
{
    /**
     * Replace a product's specs with values for its category's template.
     * key, label and unit are copied from the template so later template edits
     * never alter existing products.
     *
     * @param  array<string, mixed>  $values  keyed by spec field key
     */
    public function handle(Product $product, Category $category, array $values): void
    {
        $product->specs()->delete();

        foreach ($category->specFields as $field) {
            $value = $values[$field->key] ?? null;

            if ($field->type === SpecFieldType::Boolean) {
                if ($value === null || $value === '') {
                    continue;
                }
                // Stored in English whatever the seller's language; ProductSpec::displayValue() translates it.
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'Yes' : 'No';
            }

            if ($value === null || trim((string) $value) === '') {
                continue;
            }

            $product->specs()->create([
                'spec_field_id' => $field->id,
                'key' => $field->key,
                'label' => $field->label,
                'value' => trim((string) $value),
                'unit' => $field->type === SpecFieldType::Number ? $field->unit : null,
                'show_on_label' => $field->show_on_label,
                'sort_order' => $field->sort_order,
            ]);
        }
    }
}
