<?php

namespace App\Enums;

enum SaleStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Awaiting confirmation'),
            self::Approved => __('Approved'),
            self::Rejected => __('Rejected'),
            self::Expired => __('Expired'),
        };
    }

    /** Bootstrap contextual colour for badges. */
    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
            self::Expired => 'secondary',
        };
    }
}
