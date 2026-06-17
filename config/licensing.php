<?php

declare(strict_types=1);

return [
    'offline_grace_days' => (int) env('OFFLINE_GRACE_DAYS', 14),

    'portal_url' => env('PORTAL_URL', 'https://gridpos.co/tipidv'),

    /** Slug en transacciones GridPay (no es tenant GridPOS). */
    'gridpay_slug' => env('GRIDPAY_SLUG', 'tipidv'),

    'products' => [
        'monthly' => env('PRODUCT_MONTHLY_UUID'),
        'annual' => env('PRODUCT_ANNUAL_UUID'),
    ],

    'gridpay' => [
        'url' => rtrim((string) env('GRIDPAY_URL', ''), '/'),
        'key' => (string) env('GRIDPAY_API_KEY', ''),
    ],
];
