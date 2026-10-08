<?php

namespace App\Enums;

enum ProductStatus: string
{
    case Available = 'available';
    case LowStock = 'low_stock';
    case Sold = 'sold';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Available => __('Available'),
            self::LowStock => __('Low stock'),
            self::Sold => __('Sold'),
            self::Inactive => __('Inactive'),
        };
    }

    /** Bootstrap contextual colour used by <x-status-badge>. */
    public function color(): string
    {
        return match ($this) {
            self::Available => 'success',
            self::LowStock => 'warning',
            self::Sold => 'danger',
            self::Inactive => 'secondary',
        };
    }

    /** Sold and inactive products keep their public page but hide the price. */
    public function showsPrice(): bool
    {
        return in_array($this, [self::Available, self::LowStock], true);
    }
}
