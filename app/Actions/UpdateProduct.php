<?php

namespace App\Actions;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class UpdateProduct
{
    public function __construct(private SyncProductSpecs $syncSpecs) {}

    /**
     * Update a product. The QR token is never touched so printed stickers keep working.
     *
     * @param  array<string, mixed>  $data  validated UpdateProductRequest data
     */
    public function handle(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $product->update(collect($data)->except(['specs', 'sku', 'company_id'])->all());

            if (array_key_exists('specs', $data)) {
                $category = Category::visibleTo($product->company_id)->with('specFields')->findOrFail($product->category_id);
                $this->syncSpecs->handle($product, $category, $data['specs'] ?? []);
                // Spec rows changed without touching the product: touch it so the observer busts the public cache.
                $product->touch();
            }

            return $product;
        });
    }
}
