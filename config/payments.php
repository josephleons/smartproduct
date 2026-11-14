<?php

/*
| Customer payments from the QR page ("Lipa"). SmartLabel never moves money:
| the customer pays cash at the counter or to the shop's Lipa Namba, confirms
| it on the product page, and staff approve it in the Payments screen.
|
| Each company enters its own Lipa Namba per network in Settings > Company
| profile (a branch can override them). This file lists the networks offered.
*/

return [
    // Mobile money networks shown as cards on the checkout form, in this order.
    // Keys match App\Enums\PaymentMethod so billing and sales use the same names.
    'networks' => [
        'mpesa' => ['name' => 'M-Pesa', 'color' => '#E60000', 'text' => '#FFFFFF'],
        'tigopesa' => ['name' => 'Mixx by Yas', 'color' => '#FFD200', 'text' => '#1F2D33'],
        'airtel_money' => ['name' => 'Airtel Money', 'color' => '#ED1C24', 'text' => '#FFFFFF'],
        'halopesa' => ['name' => 'HaloPesa', 'color' => '#F7941D', 'text' => '#FFFFFF'],
    ],

    // A payment that staff neither approve nor reject is marked expired after this many hours.
    'pending_expiry_hours' => 24,

    // Payment confirmations one visitor (hashed IP) may send per hour, to stop spam.
    'max_requests_per_hour' => 10,
];
