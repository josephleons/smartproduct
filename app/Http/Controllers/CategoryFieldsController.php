<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CategorySpecField;
use Illuminate\Http\JsonResponse;

/** Spec template for a category, loaded by the Add Product wizard when the category changes. */
class CategoryFieldsController extends Controller
{
    public function __invoke(Category $category): JsonResponse
    {
        $this->authorize('view', $category);

        return response()->json([
            'fields' => $category->specFields->map(fn (CategorySpecField $field) => [
                'key' => $field->key,
                'label' => __($field->label),
                'type' => $field->type->value,
                'options' => $field->options ?? [],
                'unit' => $field->unit ? __($field->unit) : null,
                'required' => $field->is_required,
            ]),
        ]);
    }
}
