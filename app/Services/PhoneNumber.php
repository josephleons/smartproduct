<?php

namespace App\Services;

class PhoneNumber
{
    /** Normalise a Tanzanian number to international digits, e.g. "0712 345 678" -> "255712345678". */
    public static function international(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            return '255'.substr($digits, 1);
        }

        if (strlen($digits) === 9) {
            return '255'.$digits;
        }

        return $digits;
    }
}
