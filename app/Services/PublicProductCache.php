<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;

class PublicProductCache
{
    public const TTL_SECONDS = 300;

    /** The page is rendered in the visitor's language, so each locale has its own entry. */
    public static function key(string $token, ?string $locale = null): string
    {
        return 'public-product:'.$token.':'.($locale ?? app()->getLocale());
    }

    public static function remember(string $token, Closure $callback): mixed
    {
        return Cache::remember(self::key($token), self::TTL_SECONDS, $callback);
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
}
