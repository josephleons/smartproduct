<?php

namespace App\Actions;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\QrTokenGenerator;
use App\Services\SkuGenerator;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class CreateProduct
{
    /** Attempts when a concurrent save takes the same SKU or QR token first. */
    public const MAX_ATTEMPTS = 3;

    public function __construct(
        private SkuGenerator $skus,
        private QrTokenGenerator $tokens,
        private SyncProductSpecs $syncSpecs,
    ) {}

    /** @param  array<string, mixed>  $data  validated StoreProductRequest data */
    public function handle(User $user, array $data): Product
    {
        // SKU and token are picked by checking what exists, then inserted. Two staff saving
        // at the same moment can pick the same value; the unique index rejects the second
        // insert, the transaction rolls back, and we try again with freshly generated values.
        for ($attempt = 1; ; $attempt++) {
            try {
                return $this->create($user, $data);
            } catch (UniqueConstraintViolationException $e) {
                if ($attempt >= self::MAX_ATTEMPTS) {
                    throw $e;
                }
            }
        }
    }

    private function create(User $user, array $data): Product
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
