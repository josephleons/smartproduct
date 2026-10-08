<?php

namespace App\Services\Billing\Gateways;

use App\Contracts\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Models\Payment;

/** No provider to ask: a superadmin confirms or rejects the payment by hand. */
class ManualGateway implements PaymentGateway
{
    public function check(Payment $payment): PaymentStatus
    {
        return PaymentStatus::Pending;
    }
}
