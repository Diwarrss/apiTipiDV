<?php

declare(strict_types=1);

return [
    'site_name' => env('MARKETING_SITE_NAME', 'TipiDV'),
    'tagline' => env('MARKETING_TAGLINE', 'Tipificador visual de PDFs en lote: clasifica páginas, renombra y exporta por tipo'),
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
        'title' => env('MARKETING_SEO_TITLE', 'TipiDV — Tipificador PDF para lotes escaneados'),
        'description' => env(
            'MARKETING_SEO_DESCRIPTION',
            'TipiDV clasifica PDFs en lote y exporta un archivo por tipo. Plantilla MinSalud (FEV, HEV, EPI, RIPS). Windows. Licencia por equipo. Pago Wompi.'
        ),
        'keywords' => env(
            'MARKETING_SEO_KEYWORDS',
            'tipificador PDF, clasificar PDF, soportes MinSalud, FEV, HEV, EPI, RIPS, factura electrónica salud, hospital, IPS, facturación hospitalaria, TipiDV, Colombia'
        ),
        'og_image' => env('MARKETING_SEO_OG_IMAGE', 'images/tipidv-og.png'),
        'og_image_alt' => 'TipiDV — Tipificador PDF para lotes escaneados y soportes MinSalud',
        'logo' => 'images/tipidv-logo.png',
        'theme_color' => '#f26c20',
        'twitter_site' => env('MARKETING_TWITTER_SITE', ''),
        'pages' => [
            'comprar' => [
                'title' => 'Comprar licencia TipiDV',
                'description' => 'Compra licencia TipiDV en línea. Paquetes de 1 a 50 equipos, descuentos por volumen. Pago seguro con Wompi. Activación por correo.',
            ],
            'gracias' => [
                'title' => 'Gracias por tu compra',
                'description' => 'Tu pago TipiDV está en proceso. Revisa tu correo para la clave TDV y el enlace de descarga.',
            ],
        ],
    ],

    /** FAQ — mismo contenido en la landing y en schema FAQPage. */
    'faq' => [
        [
            'question' => '¿Qué es TipiDV?',
            'answer' => 'Aplicación Windows para tipificar PDFs en lote: clasificas cada página, exportas un archivo por tipo y organizas carpetas por factura o prefijo. Incluye plantilla MinSalud (FEV, HEV, EPI…) y tipos personalizables para otros documentos.',
        ],
        [
            'question' => '¿Solo sirve para hospitales y MinSalud?',
            'answer' => 'No. El caso principal en Colombia es la facturación en salud (RIPS, FEV, abreviaturas del MinSalud), pero puedes crear tus propios tipos en Configuración: contratos, recibos, anexos legales, etc.',
        ],
        [
            'question' => '¿Cumple con el nombramiento del MinSalud?',
            'answer' => 'Sí. TipiDV incluye la plantilla con las 15 abreviaturas (FEV, HEV, EPI, PDX, DQX, RAN, CRC, TAP, FAT, FMO, OPF, HAU, HAO, HAM, PDE) y genera archivos como FEV.pdf, HEV.pdf, etc. Puedes ajustar tipos desde Configuración.',
        ],
        [
            'question' => '¿Cuántos equipos cubre una licencia?',
            'answer' => 'De 1 a 50 PCs con una clave TDV. Mismo correo y clave en cada equipo. Puedes agregar equipos después desde el checkout.',
        ],
        [
            'question' => '¿Cómo descargo e instalo?',
            'answer' => 'Descarga TipiDV-Setup.exe en la sección Descargar del sitio o en el correo tras comprar. Windows 10/11, 64 bits.',
            'template' => 'download',
        ],
        [
            'question' => '¿Funciona sin internet?',
            'answer' => 'Sí, con validación periódica y días de gracia sin conexión.',
            'template' => 'offline',
        ],
    ],

    /**
     * Tabla de nombramiento de soportes (MinSalud) — vigente desde jun 2026.
     * TipiDV exporta un PDF por abreviatura: FEV.pdf, HEV.pdf, etc.
     */
    'support_types_effective' => '1 de junio de 2026',

    /** Resoluciones MinSalud citadas en la landing (RIPS / FEV). */
    'minsalud_norms' => [
        [
            'number' => '2275 de 2023',
            'date' => '28 dic 2023',
            'summary' => 'Reglamenta el RIPS como soporte de la Factura Electrónica de Venta (FEV) en salud.',
        ],
        [
            'number' => '000948 de 2026',
            'date' => '14 may 2026',
            'summary' => 'Actualiza el RIPS como soporte de la FEV; deroga resoluciones anteriores y unifica criterios.',
        ],
    ],

    /** Casos de uso en la landing — salud es el principal, no el único. */
    'use_cases' => [
        [
            'icon' => '🏥',
            'title' => 'Facturación en salud',
            'desc' => 'Soportes MinSalud (FEV, HEV, EPI, PDX…) listos para RIPS y cargue a tu ERP o validador.',
            'featured' => true,
        ],
        [
            'icon' => '🏷️',
            'title' => 'Tipos a tu medida',
            'desc' => 'Crea, renombra, ordena y desactiva tipos desde Configuración — no estás limitado a la plantilla de salud.',
        ],
        [
            'icon' => '📂',
            'title' => 'Archivo y trámites',
            'desc' => 'Contratos, anexos, recibos, actas… cualquier lote escaneado que deba separarse en PDFs por categoría.',
        ],
        [
            'icon' => '⚖️',
            'title' => 'Contabilidad y legal',
            'desc' => 'Facturas, soportes de gasto, documentos de cliente: un PDF por tipo y carpeta por radicado o prefijo.',
        ],
    ],

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
                ['icon' => '🏷️', 'title' => 'Tipos personalizables', 'desc' => 'Plantilla MinSalud incluida (FEV, HEV, EPI…). Edita nombre, color, orden y activo/inactivo para tu flujo.'],
                ['icon' => '⚠️', 'title' => 'Sin tipificar', 'desc' => 'Bloquea la exportación si quedan páginas sin clasificar.'],
            ],
        ],
        [
            'title' => 'Exportación',
            'items' => [
                ['icon' => '📤', 'title' => 'Un PDF por tipo', 'desc' => 'Fusiona páginas del mismo tipo en un solo archivo con el nombre que definas (ej. FEV.pdf, Contrato.pdf).'],
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
