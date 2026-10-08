<?php

namespace App\Services;

use App\Models\QrCode;
use RuntimeException;

class QrTokenGenerator
{
    /** Uppercase alphabet without ambiguous characters (0/O, 1/I/L). */
    public const ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    public const LENGTH = 7;

    public function generate(): string
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $token = $this->random();

            if (! QrCode::where('token', $token)->exists()) {
                return $token;
            }
        }

        throw new RuntimeException('Could not generate a unique QR token.');
    }

    public function random(): string
    {
        $max = strlen(self::ALPHABET) - 1;
        $token = '';

        for ($i = 0; $i < self::LENGTH; $i++) {
            $token .= self::ALPHABET[random_int(0, $max)];
        }

        return $token;
    }
}
