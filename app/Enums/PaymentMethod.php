<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Mpesa = 'mpesa';
    case TigoPesa = 'tigopesa';
    case AirtelMoney = 'airtel_money';
    case HaloPesa = 'halopesa';
    case Bank = 'bank';
    case Cash = 'cash';

    public function label(): string
    {
        return match ($this) {
            self::Mpesa => 'M-Pesa',
            self::TigoPesa => 'Mixx by Yas (Tigo Pesa)',
            self::AirtelMoney => 'Airtel Money',
            self::HaloPesa => 'HaloPesa',
            self::Bank => __('Bank transfer'),
            self::Cash => __('Cash'),
        };
    }
}
