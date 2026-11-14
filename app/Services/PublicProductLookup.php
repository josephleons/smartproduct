<?php

namespace App\Services;

use App\Models\Product;
use App\Models\QrCode;
use App\Models\Scopes\CompanyScope;

/** Finds the product behind a sticker token for the public (no login) pages. */
class PublicProductLookup
{
    /** Uppercased token, or null when it cannot be a valid token. */
    public static function normalizeToken(string $token): ?string
    {
        $token = strtoupper(trim($token));

        return preg_match('/^['.QrTokenGenerator::ALPHABET.']{'.QrTokenGenerator::LENGTH.'}$/', $token) ? $token : null;
    }

    public static function find(string $token): ?Product
    {
        $token = self::normalizeToken($token);

        if (! $token) {
            return null;
        }

        $qr = QrCode::where('token', $token)->first();

        // Public token lookup: no authenticated tenant, and a logged-in seller from another
        // company must still see this page, so the tenant scope is bypassed on purpose.
        return $qr ? Product::withoutGlobalScope(CompanyScope::class)
            ->with([
                'qrCode',
                'company',
                'branch' => fn ($q) => $q->withoutGlobalScope(CompanyScope::class),
                'specs',
                'category',
            ])
            ->find($qr->product_id) : null;
    }
}
