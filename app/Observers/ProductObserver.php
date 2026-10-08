<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\PublicProductCache;

class ProductObserver
{
    public function saved(Product $product): void
    {
        $this->bust($product);
    }

    public function deleted(Product $product): void
    {
        $this->bust($product);
    }

    public function restored(Product $product): void
    {
        $this->bust($product);
    }

    private function bust(Product $product): void
    {
        PublicProductCache::forget($product->qrCode()->value('token'));
    }
}
