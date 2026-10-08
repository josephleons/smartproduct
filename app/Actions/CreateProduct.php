<?php

namespace App\Actions;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\QrTokenGenerator;
use App\Services\SkuGenerator;
use Illuminate\Support\Facades\DB;

class CreateProduct
{
    public function __construct(
        private SkuGenerator $skus,
        private QrTokenGenerator $tokens,
        private SyncProductSpecs $syncSpecs,
    ) {}

    /** @param  array<string, mixed>  $data  validated StoreProductRequest data */
    public function handle(User $user, array $data): Product
    {
        return DB::transaction(function () use ($user, $data) {
            $category = Category::visibleTo($user->company_id)->with('specFields')->findOrFail($data['category_id']);

            $product = Product::create([
                ...collect($data)->except('specs')->all(),
                'company_id' => $user->company_id,
                'sku' => $this->skus->generate($user->company_id, $category, $data['model']),
            ]);

            $this->syncSpecs->handle($product, $category, $data['specs'] ?? []);

            // The token is permanent: it is created once here and never regenerated.
            $product->qrCode()->create(['token' => $this->tokens->generate()]);

            return $product;
        });
    }
}
