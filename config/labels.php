<?php

/*
| Label (sticker) templates. Sizes are in millimetres. The QR is never
| smaller than 20 mm so stickers stay scannable when printed small.
*/

return [
    'default' => 'standard_70x45',

    'templates' => [
        'standard_70x45' => [
            'name' => 'Standard 70 × 45 mm',
            'width' => 70,
            'height' => 45,
            'qr_size' => 24,
            'sheet' => ['paper' => 'a4', 'columns' => 2, 'rows' => 4, 'gap' => 6],
        ],
        'compact_50x30' => [
            'name' => 'Compact 50 × 30 mm',
            'width' => 50,
            'height' => 30,
            'qr_size' => 21,
            'sheet' => ['paper' => 'a4', 'columns' => 3, 'rows' => 7, 'gap' => 5],
        ],
        'large_100x60' => [
            'name' => 'Large 100 × 60 mm',
            'width' => 100,
            'height' => 60,
            'qr_size' => 30,
            'sheet' => ['paper' => 'a4', 'columns' => 2, 'rows' => 4, 'gap' => 2],
        ],
    ],

    // Fields a seller can toggle. QR and the "Scan for specs" hint always print.
    'fields' => ['company', 'specs', 'price', 'branch', 'sku'],

    'max_copies' => 64,
];
