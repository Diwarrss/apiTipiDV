<?php

declare(strict_types=1);

return [
    'site_name' => env('MARKETING_SITE_NAME', 'TipiDV'),
    'tagline' => env('MARKETING_TAGLINE', 'Soportes PDF nombrados como exige el Ministerio de Salud'),
    'contact_email' => env('MARKETING_CONTACT_EMAIL', 'dialvar30@gmail.com'),
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
            'TipiDV tipifica y exporta soportes PDF con las abreviaturas del MinSalud (FEV, HEV, EPI, PDX…). Instalador Windows, licencia por equipo, pago con Wompi. Para facturación hospitalaria en Colombia.'
        ),
        'keywords' => env(
            'MARKETING_SEO_KEYWORDS',
            'soportes MinSalud, tipificador PDF, FEV HEV EPI, hospital, facturación hospitalaria, digitalizador, TipiDV, Colombia'
        ),
    ],

    /**
     * Tabla de nombramiento de soportes (MinSalud) — vigente desde jun 2026.
     * TipiDV exporta un PDF por abreviatura: FEV.pdf, HEV.pdf, etc.
     */
    'support_types_effective' => '1 de junio de 2026',
    'support_types' => [
        ['code' => 'FEV', 'name' => 'Factura'],
        ['code' => 'HEV', 'name' => 'Historia clínica ambulatoria, hospitalización u observación'],
        ['code' => 'EPI', 'name' => 'Epicrisis'],
        ['code' => 'PDX', 'name' => 'Laboratorio, rayos X, espirometrías, ecos y resultados ambulatorios u hospitalarios'],
        ['code' => 'DQX', 'name' => 'Descripción quirúrgica'],
        ['code' => 'RAN', 'name' => 'Record de anestesia'],
        ['code' => 'CRC', 'name' => 'Firma del usuario'],
        ['code' => 'TAP', 'name' => 'Traslado asistencial de pacientes'],
        ['code' => 'FAT', 'name' => 'Factura SOAT / ADRES con división de cuentas'],
        ['code' => 'FMO', 'name' => 'Factura de material de osteosíntesis'],
        ['code' => 'OPF', 'name' => 'Orden médica'],
        ['code' => 'HAU', 'name' => 'Historia clínica de urgencias'],
        ['code' => 'HAO', 'name' => 'Evolución odontología u odontograma'],
        ['code' => 'HAM', 'name' => 'Hoja de administración de medicamentos'],
        ['code' => 'PDE', 'name' => 'Autorización ambulatoria o ingreso médico'],
    ],

    /** Grupos de funciones en la landing (#funciones). */
    'feature_groups' => [
        [
            'title' => 'Captura',
            'items' => [
                ['icon' => '📄', 'title' => 'PDFs en lote', 'desc' => 'Carga uno o varios archivos; cada página queda como miniatura en orden.'],
                ['icon' => '🖨️', 'title' => 'Escaneo directo', 'desc' => 'Escáner WIA/TWAIN (HP, Canon, Epson…) sin salir de la app.'],
            ],
        ],
        [
            'title' => 'Tipificación',
            'items' => [
                ['icon' => '🎨', 'title' => 'Panel visual', 'desc' => 'Tipos con color, vista previa con zoom y miniaturas etiquetadas.'],
                ['icon' => '🏷️', 'title' => 'Tipos MinSalud', 'desc' => 'FEV, HEV, EPI, PDX… configurables: nombre, color, orden y activo/inactivo.'],
                ['icon' => '⚠️', 'title' => 'Sin tipificar', 'desc' => 'Bloquea la exportación si quedan páginas sin clasificar.'],
            ],
        ],
        [
            'title' => 'Exportación',
            'items' => [
                ['icon' => '📤', 'title' => 'Un PDF por soporte', 'desc' => 'Genera FEV.pdf, HEV.pdf… fusionando páginas en orden de carga.'],
                ['icon' => '📁', 'title' => 'Carpeta por factura', 'desc' => 'Prefijo de factura → Documentos\\SALIDA\\{prefijo}\\ listo para cargar.'],
                ['icon' => '✂️', 'title' => 'División por MB', 'desc' => 'Parte archivos grandes cuando superan el límite del sistema.'],
            ],
        ],
        [
            'title' => 'Instalación y licencia',
            'items' => [
                ['icon' => '💻', 'title' => 'Instalador Setup.exe', 'desc' => 'Windows 10/11 · instalación guiada en pocos clics.'],
                ['icon' => '🔐', 'title' => 'Multi-equipo', 'desc' => 'Una clave TDV para varios PCs del hospital con el mismo correo.'],
                ['icon' => '🌐', 'title' => 'Gracia offline', 'desc' => 'Validación en línea periódica; sigues trabajando si cae la red.'],
            ],
        ],
    ],
];
