<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Trial = 'trial';
    case Active = 'active';
    case PastDue = 'past_due';   // expired, inside the grace period: still full access
    case ReadOnly = 'read_only'; // grace over: view only, no new products, no labels

    public function label(): string
    {
        return match ($this) {
            self::Trial => __('Trial'),
            self::Active => __('Active'),
            self::PastDue => __('Payment overdue'),
            self::ReadOnly => __('Read-only (unpaid)'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Trial => 'info',
            self::Active => 'success',
            self::PastDue => 'warning',
            self::ReadOnly => 'danger',
        };
    }

    public function allowsWrites(): bool
    {
        return $this !== self::ReadOnly;
    }
}
