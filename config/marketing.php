<?php

declare(strict_types=1);

return [
    'site_name' => env('MARKETING_SITE_NAME', 'TipiDV'),
    'tagline' => env('MARKETING_TAGLINE', 'Tipificador de soportes PDF para hospitales'),
    'contact_email' => env('MARKETING_CONTACT_EMAIL', 'diego@gridsoft.co'),
    'contact_phone' => env('MARKETING_CONTACT_PHONE', '+57 313 245 8975'),
    'whatsapp' => env('MARKETING_WHATSAPP', '573132458975'),
    'author' => env('MARKETING_AUTHOR', 'Ing. Diego Vargas'),
    'author_url' => env('MARKETING_AUTHOR_URL', 'https://diego.gridsoft.co/'),

    /** URL del instalador Windows (fallback manual si no hay release en GitHub) */
    'download_url' => env('MARKETING_DOWNLOAD_URL', ''),

    /** Repo GitHub con releases (Diwarrss/appWindowsTipificadorPDF) */
    'github_repo' => env('MARKETING_GITHUB_REPO', 'Diwarrss/appWindowsTipificadorPDF'),
    'github_token' => env('MARKETING_GITHUB_TOKEN', ''),
    'setup_asset_name' => env('MARKETING_SETUP_ASSET', 'TipiDV-Setup.exe'),
    'portable_asset_name' => env('MARKETING_PORTABLE_ASSET', 'TipiDV-Portable.zip'),
    'github_release_webhook_secret' => env('GITHUB_RELEASE_WEBHOOK_SECRET', ''),

    /**
     * Precios mostrados si GridPay aún no está configurado.
     * Cuando hay productos en GridPay, prevalecen esos valores.
     */
    'fallback_prices' => [
        'annual_cop' => (int) env('MARKETING_PRICE_ANNUAL_COP', 198_000),
        'monthly_cop' => (int) env('MARKETING_PRICE_MONTHLY_COP', 29_000),
    ],

    /** Máximo de equipos por compra en el portal (validado en servidor). */
    'max_license_quantity' => (int) env('MARKETING_MAX_LICENSE_QUANTITY', 50),

    /**
     * Descuentos por volumen (aplicados en servidor al crear el link Wompi).
     * Una clave TDV sirve para N equipos (machine_slots = cantidad).
     */
    'volume_discounts' => [
        ['min_quantity' => 5, 'percent' => 10, 'label' => '10% de descuento desde 5 equipos'],
        ['min_quantity' => 10, 'percent' => 15, 'label' => '15% de descuento desde 10 equipos'],
    ],

    'seo' => [
        'title' => env('MARKETING_SEO_TITLE', 'TipiDV — Tipificador PDF para hospitales y clínicas'),
        'description' => env(
            'MARKETING_SEO_DESCRIPTION',
            'TipiDV clasifica y exporta soportes PDF por tipo (FEV, HEV, EPI…). Licencia por equipo, pago en línea con Wompi, activación en minutos. Ideal para facturación hospitalaria en Colombia.'
        ),
        'keywords' => env(
            'MARKETING_SEO_KEYWORDS',
            'tipificador PDF, hospital, soportes facturación, SYC, digitalizador, TipiDV, Colombia'
        ),
    ],
];
