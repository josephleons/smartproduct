<?php

use App\Services\Billing\Gateways\ManualGateway;

/*
| Billing defaults. The superadmin overrides the "settings" values at
| Admin > Billing settings (stored in platform_settings). Prices, limits
| and features live on plans (Admin > Plans).
*/

return [
    'settings' => [
        'trial_days' => 14,          // free days for a newly created company (0 = bill immediately)
        'grace_days' => 7,           // full access after paid_until before going read-only
        'invoice_days_before' => 7,  // issue the next monthly bill this many days before it is due
        'gateway' => 'manual',       // driver used for payments companies submit
        'payment_instructions' => '',
    ],

    // Payment gateway drivers. "manual": the superadmin confirms each payment.
    // A mobile money driver (Selcom, AzamPay, ClickPesa…) implements App\Contracts\PaymentGateway.
    'gateways' => [
        'manual' => ManualGateway::class,
    ],
];
