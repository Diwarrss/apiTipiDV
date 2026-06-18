@php
    $siteName = config('marketing.site_name', 'TipiDV');
    $seo = config('marketing.seo', []);
    $sectionTitle = trim($__env->yieldContent('title'));
    $pageTitle = $sectionTitle !== '' ? "{$sectionTitle} — {$siteName}" : ($seo['title'] ?? $siteName);
    $description = trim($__env->yieldContent('meta_description')) ?: ($seo['description'] ?? '');
    $metaRobots = trim($__env->yieldContent('meta_robots')) ?: 'index, follow, max-image-preview:large';
    $canonical = trim($__env->yieldContent('canonical')) ?: url()->current();
    $seoPage = trim($__env->yieldContent('seo_page')) ?: match (request()->route()?->getName()) {
        'site.home' => 'home',
        'site.comprar' => 'comprar',
        default => 'other',
    };
    $seoService = app(\App\Services\SeoService::class);
    $ogImage = $seoService->ogImageUrl(trim($__env->yieldContent('og_image')) ?: null);
    $ogImageAlt = trim($__env->yieldContent('og_image_alt')) ?: ($seo['og_image_alt'] ?? 'TipiDV — Tipificador PDF para lotes escaneados');
    $structuredGraph = $seoService->graphForPage($seoPage, $description, $lowPriceCop ?? null);
    $whatsappUrl = 'https://wa.me/' . config('marketing.whatsapp') . '?text=' . rawurlencode('Hola, quiero información sobre TipiDV');
    $themeColor = $seo['theme_color'] ?? '#f26c20';
@endphp
<!DOCTYPE html>
<html lang="es-CO">
<head>
    <script src="{{ asset('js/site-theme.js') }}?v={{ file_exists(public_path('js/site-theme.js')) ? filemtime(public_path('js/site-theme.js')) : '1' }}"></script>
    <script src="{{ asset('js/site-nav.js') }}?v={{ file_exists(public_path('js/site-nav.js')) ? filemtime(public_path('js/site-nav.js')) : '1' }}"></script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $description }}">
    @if(!empty($seo['keywords']))
        <meta name="keywords" content="{{ $seo['keywords'] }}">
    @endif
    <meta name="author" content="{{ config('marketing.author') }}">
    <meta name="robots" content="{{ $metaRobots }}">
    <meta name="googlebot" content="{{ $metaRobots }}">
    <meta name="theme-color" content="{{ $themeColor }}">
    <meta name="geo.region" content="CO">
    <meta name="language" content="Spanish">
    <link rel="canonical" href="{{ $canonical }}">
    <link rel="alternate" hreflang="es-CO" href="{{ $canonical }}">
    <link rel="alternate" hreflang="x-default" href="{{ url('/') }}">

    <meta property="og:type" content="website">
    <meta property="og:locale" content="es_CO">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:image:secure_url" content="{{ $ogImage }}">
    <meta property="og:image:alt" content="{{ $ogImageAlt }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $description }}">
    <meta name="twitter:image" content="{{ $ogImage }}">
    <meta name="twitter:image:alt" content="{{ $ogImageAlt }}">
    @if(!empty($seo['twitter_site']))
        <meta name="twitter:site" content="{{ $seo['twitter_site'] }}">
    @endif

    <link rel="icon" href="{{ asset('images/tipidv-logo.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('images/tipidv-logo.png') }}">
    @vite(['resources/css/site.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('head')
    @if($structuredGraph !== [])
        <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@graph' => $structuredGraph,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) !!}
        </script>
    @endif
</head>
<body>
    <header class="site-header">
        <div class="container inner">
            <a href="{{ url('/') }}" class="logo" aria-label="TipiDV inicio">
                <img src="{{ asset('images/tipidv-logo.png') }}" alt="Logo TipiDV" class="logo-mark" width="44" height="44">
                <span class="logo-wordmark">Tipi<span>DV</span></span>
            </a>
            <button
                type="button"
                class="nav-toggle"
                aria-expanded="false"
                aria-controls="site-nav"
                aria-label="Abrir menú de navegación"
            >
                <span class="nav-toggle-icon" aria-hidden="true"></span>
            </button>
            <nav id="site-nav" class="nav" aria-label="Principal">
                <a href="{{ url('/#casos') }}">Casos de uso</a>
                <a href="{{ url('/#soportes') }}">MinSalud</a>
                <a href="{{ url('/#funciones') }}">Funciones</a>
                <a href="{{ url('/#descargar') }}">Descargar</a>
                <a href="{{ url('/#precios') }}">Precios</a>
                <a href="{{ url('/#faq') }}">FAQ</a>
                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener">Contacto</a>
                <button type="button" class="theme-toggle" id="theme-toggle" aria-label="Cambiar tema claro u oscuro" title="Tema claro / oscuro">
                    <span class="icon-sun" aria-hidden="true">☀️</span>
                    <span class="icon-moon" aria-hidden="true">🌙</span>
                </button>
                <a href="{{ url('/comprar') }}" class="btn btn-primary">Comprar licencia</a>
            </nav>
        </div>
    </header>

    <main id="contenido-principal">
        @if (session('status'))
            <div class="site-flash site-flash--success" role="alert">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="site-flash site-flash--error" role="alert">{{ session('error') }}</div>
        @endif
        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <a href="{{ url('/') }}" class="logo" aria-label="TipiDV inicio">
                        <img src="{{ asset('images/tipidv-logo.png') }}" alt="Logo TipiDV" class="logo-mark" width="44" height="44">
                        <span class="logo-wordmark">Tipi<span>DV</span></span>
                    </a>
                    <p class="footer-tagline">{{ config('marketing.tagline') }}</p>
                    <p class="footer-author">
                        Por <a href="{{ config('marketing.author_url') }}" target="_blank" rel="noopener me">{{ config('marketing.author') }}</a>
                    </p>
                </div>
                <div class="footer-cols">
                    <div class="footer-col">
                        <strong class="footer-heading">Producto</strong>
                        <ul class="footer-links">
                            <li><a href="{{ url('/comprar') }}">Comprar</a></li>
                            <li><a href="{{ url('/#casos') }}">Casos de uso</a></li>
                            <li><a href="{{ url('/#soportes') }}">MinSalud</a></li>
                            <li><a href="{{ url('/#descargar') }}">Descargar</a></li>
                            <li><a href="{{ url('/#precios') }}">Precios</a></li>
                        </ul>
                    </div>
                    <div class="footer-col">
                        <strong class="footer-heading">Contacto</strong>
                        <ul class="footer-links">
                            <li><a href="mailto:{{ config('marketing.contact_email') }}">{{ config('marketing.contact_email') }}</a></li>
                            <li><a href="tel:{{ preg_replace('/\s+/', '', config('marketing.contact_phone')) }}">{{ config('marketing.contact_phone') }}</a></li>
                            @if(config('marketing.whatsapp'))
                                <li><a href="{{ $whatsappUrl }}" target="_blank" rel="noopener">WhatsApp</a></li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p class="footer-copy">
                    &copy; {{ date('Y') }} {{ $siteName }} ·
                    <a href="{{ config('marketing.author_url') }}">{{ config('marketing.author') }}</a>
                    · Colombia
                </p>
            </div>
        </div>
    </footer>
    @livewireScripts
    @stack('scripts')
</body>
</html>