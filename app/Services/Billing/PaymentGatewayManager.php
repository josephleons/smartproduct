<?php

namespace App\Services\Billing;

use App\Contracts\PaymentGateway;
use InvalidArgumentException;

class PaymentGatewayManager
{
    public function driver(string $name): PaymentGateway
    {
        $class = config("billing.gateways.$name");

        if (! $class) {
            throw new InvalidArgumentException("Unknown payment gateway [$name].");
        }

        return app($class);
    }

    /** @return array<int, string> */
    public function available(): array
    {
        return array_keys(config('billing.gateways'));
    }
}
