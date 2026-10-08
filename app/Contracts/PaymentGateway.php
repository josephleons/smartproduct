<?php

namespace App\Contracts;

use App\Enums\PaymentStatus;
use App\Models\Payment;

interface PaymentGateway
{
    /**
     * Ask the provider about a pending payment. Return Pending when nothing is
     * known yet, Confirmed when the money arrived, Failed when it never will.
     */
    public function check(Payment $payment): PaymentStatus;
}
