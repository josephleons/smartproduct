<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;

class PublicProductCache
{
    public const TTL_SECONDS = 300;

    public static function key(string $token): string
    {
        return 'public-product:'.$token;
    }

    public static function remember(string $token, Closure $callback): mixed
    {
        return Cache::remember(self::key($token), self::TTL_SECONDS, $callback);
    }

    public static function forget(?string $token): void
    {
        if ($token) {
            Cache::forget(self::key($token));
        }
    }
}
