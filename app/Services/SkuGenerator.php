<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\Scopes\CompanyScope;
use Illuminate\Support\Str;

class SkuGenerator
{
    /** e.g. "TZ-LAT5420-024": category prefix, model digits, per-company sequence. */
    public function generate(int $companyId, Category $category, string $model): string
    {
        $prefix = Str::upper(Str::substr(preg_replace('/[^A-Za-z]/', '', $category->name), 0, 3)) ?: 'GEN';

        preg_match('/\d+/', $model, $digits);
        $modelPart = Str::substr($digits[0] ?? Str::upper(preg_replace('/[^A-Za-z0-9]/', '', $model)), 0, 4);

        // Count across the whole company (including deleted products) so SKUs are never reused.
        $query = Product::withoutGlobalScope(CompanyScope::class)->withTrashed()->where('company_id', $companyId);
        $sequence = $query->count() + 1;

        do {
            $sku = sprintf('TZ-%s%s-%03d', $prefix, $modelPart, $sequence++);
        } while ((clone $query)->where('sku', $sku)->exists());

        return $sku;
    }
}
