<?php

declare(strict_types=1);

return [
    'offline_grace_days' => (int) env('OFFLINE_GRACE_DAYS', 14),

    'portal_url' => env('PORTAL_URL', 'https://tipidv.gridsoft.co'),

    'wompi' => [
        'api_url' => rtrim((string) env('WOMPI_API_URL', 'https://production.wompi.co/v1'), '/'),
        'public_key' => (string) env('WOMPI_PUBLIC_KEY', ''),
        'private_key' => (string) env('WOMPI_PRIVATE_KEY', ''),
        'events_secret' => (string) env('WOMPI_EVENTS_SECRET', ''),
        'integrity_secret' => (string) env('WOMPI_INTEGRITY_SECRET', ''),
    ],
];
