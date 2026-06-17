@php
    $siteName = config('marketing.site_name', 'TipiDV');
    $seo = config('marketing.seo', []);
    $sectionTitle = trim($__env->yieldContent('title'));
    $pageTitle = $sectionTitle !== '' ? "{$sectionTitle} — {$siteName}" : ($seo['title'] ?? $siteName);
    $description = trim($__env->yieldContent('meta_description')) ?: ($seo['description'] ?? '');
    $canonical = url()->current();
    $ogImage = asset('images/tipidv-logo.png');
@endphp
<!DOCTYPE html>
<html lang="es-CO">
<head>
    <script src="{{ asset('js/site-theme.js') }}"></script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $description }}">
    @if(!empty($seo['keywords']))
        <meta name="keywords" content="{{ $seo['keywords'] }}">
    @endif
    <meta name="author" content="{{ config('marketing.author') }}">
    <link rel="canonical" href="{{ $canonical }}">
    <meta name="robots" content="index, follow">

    <meta property="og:type" content="website">
    <meta property="og:locale" content="es_CO">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="{{ $ogImage }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $description }}">

    <link rel="icon" href="{{ asset('images/tipidv-logo.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('images/tipidv-logo.png') }}">
    <link rel="stylesheet" href="{{ asset('css/site.css') }}">
    @stack('head')
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => $siteName,
        'applicationCategory' => 'BusinessApplication',
        'operatingSystem' => 'Windows',
        'description' => $description,
        'url' => url('/'),
        'offers' => [
            '@type' => 'Offer',
            'priceCurrency' => 'COP',
            'availability' => 'https://schema.org/InStock',
            'url' => url('/comprar'),
        ],
        'author' => [
            '@type' => 'Person',
            'name' => config('marketing.author'),
            'url' => config('marketing.author_url'),
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
</head>
<body>
    <header class="site-header">
        <div class="container inner">
            <a href="{{ url('/') }}" class="logo" aria-label="TipiDV inicio">
                <img src="{{ asset('images/tipidv-logo.png') }}" alt="" class="logo-mark" width="44" height="44">
                <span class="logo-wordmark">Tipi<span>DV</span></span>
            </a>
            <nav class="nav" aria-label="Principal">
                <a href="{{ url('/#funciones') }}">Funciones</a>
                <a href="{{ url('/#precios') }}">Precios</a>
                <a href="{{ url('/#faq') }}">FAQ</a>
                @if(!empty($downloadUrl))
                    <a href="{{ $downloadUrl }}" rel="noopener">Descargar</a>
                @endif
                <button type="button" class="theme-toggle" id="theme-toggle" aria-label="Cambiar tema claro u oscuro" title="Tema claro / oscuro">
                    <span class="icon-sun" aria-hidden="true">☀️</span>
                    <span class="icon-moon" aria-hidden="true">🌙</span>
                </button>
                <a href="{{ url('/comprar') }}" class="btn btn-primary">Comprar licencia</a>
            </nav>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="container footer-grid">
            <div>
                <a href="{{ url('/') }}" class="logo" aria-label="TipiDV inicio">
                    <img src="{{ asset('images/tipidv-logo.png') }}" alt="" class="logo-mark" width="44" height="44">
                    <span class="logo-wordmark">Tipi<span>DV</span></span>
                </a>
                <p style="margin:12px 0 0">{{ config('marketing.tagline') }}</p>
                <p style="margin:8px 0 0;font-size:.85rem">
                    Por <a href="{{ config('marketing.author_url') }}" target="_blank" rel="noopener me">{{ config('marketing.author') }}</a>
                </p>
            </div>
            <div>
                <strong class="footer-heading">Producto</strong>
                <a href="{{ url('/comprar') }}">Comprar</a><br>
                <a href="{{ url('/#funciones') }}">Características</a><br>
                @if(!empty($downloadUrl))
                    <a href="{{ $downloadUrl }}">Instalador Windows</a>
                @endif
            </div>
            <div>
                <strong class="footer-heading">Contacto</strong>
                <a href="mailto:{{ config('marketing.contact_email') }}">{{ config('marketing.contact_email') }}</a><br>
                <a href="tel:{{ preg_replace('/\s+/', '', config('marketing.contact_phone')) }}">{{ config('marketing.contact_phone') }}</a><br>
                @if(config('marketing.whatsapp'))
                    <a href="https://wa.me/{{ config('marketing.whatsapp') }}" target="_blank" rel="noopener">WhatsApp</a>
                @endif
            </div>
        </div>
        <div class="container footer-bottom">
            &copy; {{ date('Y') }} {{ $siteName }} ·
            <a href="{{ config('marketing.author_url') }}">{{ config('marketing.author') }}</a>
            · Colombia
        </div>
    </footer>
    @stack('scripts')
</body>
</html>
