<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Company;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Rendered HTML of the public product page, per token and locale.
 *
 * A cache hit needs no database at all: the entry carries the HTML and the
 * product id (for scan tracking). The page also shows company, branch and
 * category details, so each entry is stamped with their version values;
 * PublicPageDependencyObserver changes a version when that record is saved,
 * and an entry whose stamps no longer match is treated as a miss.
 * Product edits are cleared directly by ProductObserver via forget().
 *
 * @phpstan-type Entry array{html: string, product_id: int, deps: list<string>, versions: list<string|null>}
 */
class PublicProductCache
{
    public const TTL_SECONDS = 300;

    /** The page is rendered in the visitor's language, so each locale has its own entry. */
    public static function key(string $token, ?string $locale = null): string
    {
        return 'public-product:'.$token.':'.($locale ?? app()->getLocale());
    }

    /** @return Entry|null a still-valid entry for the current locale */
    public static function get(string $token): ?array
    {
        $entry = Cache::get(self::key($token));

        if (! is_array($entry) || ! isset($entry['html'], $entry['product_id'], $entry['deps'], $entry['versions'])) {
            return null;
        }

        return self::versions($entry['deps']) === $entry['versions'] ? $entry : null;
    }

    /**
     * Store a freshly rendered page. Versions are read after the product was loaded; a
     * dependency edited in that instant can at worst stay stale until the TTL expires.
     */
    public static function put(Product $product, string $html): void
    {
        $deps = [
            self::versionKey(Company::class, $product->company_id),
            self::versionKey(Branch::class, $product->branch_id),
            self::versionKey(Category::class, $product->category_id),
        ];

        Cache::put(self::key($product->qrCode->token), [
            'html' => $html,
            'product_id' => $product->id,
            'deps' => $deps,
            'versions' => self::versions($deps),
        ], self::TTL_SECONDS);
    }

    public static function forget(?string $token): void
    {
        if (! $token) {
            return;
        }

        foreach (array_keys(config('app.locales')) as $locale) {
            Cache::forget(self::key($token, $locale));
        }
    }

    /** Invalidate every cached page that shows this company, branch or category. */
    public static function bump(Model $model): void
    {
        Cache::forever(self::versionKey($model::class, $model->getKey()), Str::random(12));
    }

    private static function versionKey(string $class, int|string|null $id): string
    {
        return 'public-product-version:'.class_basename($class).':'.($id ?? 'none');
    }

    /**
     * One round trip for all stamps. A missing stamp reads as null, so an evicted
     * version key also invalidates entries stamped with its old value.
     *
     * @param  list<string>  $deps
     * @return list<string|null>
     */
    private static function versions(array $deps): array
    {
        return array_values(Cache::many($deps));
    }
}
