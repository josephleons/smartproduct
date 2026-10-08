<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Pending = 'pending';
    case Overdue = 'overdue';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Pending'),
            self::Overdue => __('Overdue'),
            self::Paid => __('Paid'),
            self::Cancelled => __('Cancelled'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Overdue => 'danger',
            self::Paid => 'success',
            self::Cancelled => 'secondary',
        };
    }

    /** @return array<int, self> */
    public static function open(): array
    {
        return [self::Pending, self::Overdue];
    }
}
