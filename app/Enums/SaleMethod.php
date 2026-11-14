<?php

namespace App\Enums;

enum SaleMethod: string
{
    case Cash = 'cash';
    case Mobile = 'mobile';

    public function label(): string
    {
        return match ($this) {
            self::Cash => __('Cash'),
            self::Mobile => __('Mobile money'),
        };
    }
}
