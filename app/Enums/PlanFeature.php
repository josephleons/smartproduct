<?php

namespace App\Enums;

/** Features a plan can include. Superadmin ticks them per plan in Admin > Plans. */
enum PlanFeature: string
{
    case Analytics = 'analytics';
    case CustomCategories = 'custom_categories';
    case SmsNotifications = 'sms_notifications';
    case WhatsappOrders = 'whatsapp_orders';

    public function label(): string
    {
        return match ($this) {
            self::Analytics => __('Scan analytics'),
            self::CustomCategories => __('Custom categories & spec templates'),
            self::SmsNotifications => __('SMS to customers'),
            self::WhatsappOrders => __('Orders through WhatsApp'),
        };
    }

    /** Not built yet: can be sold in a plan, but nothing is unlocked. */
    public function comingSoon(): bool
    {
        return in_array($this, [self::SmsNotifications, self::WhatsappOrders], true);
    }
}
