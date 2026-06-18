@php
    $siteName = config('marketing.site_name', 'TipiDV');
    $seo = config('marketing.seo', []);
    $sectionTitle = trim($__env->yieldContent('title'));
    $pageTitle = $sectionTitle !== '' ? "{$sectionTitle} — {$siteName}" : ($seo['title'] ?? $siteName);
    $description = trim($__env->yieldContent('meta_description')) ?: ($seo['description'] ?? '');
    $canonical = url()->current();
    $ogImage = asset('images/tipidv-logo.png');
    $whatsappUrl = 'https://wa.me/' . config('marketing.whatsapp') . '?text=' . rawurlencode('Hola, quiero información sobre TipiDV');
@endphp
<!DOCTYPE html>
<html lang="es-CO">
<head>
    <script src="{{ asset('js/site-theme.js') }}?v={{ file_exists(public_path('js/site-theme.js')) ? filemtime(public_path('js/site-theme.js')) : '1' }}"></script>
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
    @vite(['resources/css/site.css', 'resources/js/app.js'])
    @livewireStyles
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
    <header class="site-header" x-data="{ menuOpen: false }" @keydown.escape.window="menuOpen = false">
        <div class="container inner">
            <a href="{{ url('/') }}" class="logo" aria-label="TipiDV inicio">
                <img src="{{ asset('images/tipidv-logo.png') }}" alt="" class="logo-mark" width="44" height="44">
                <span class="logo-wordmark">Tipi<span>DV</span></span>
            </a>
            <button
                type="button"
                class="nav-toggle"
                @click="menuOpen = !menuOpen"
                :aria-expanded="menuOpen.toString()"
                aria-controls="site-nav"
                aria-label="Abrir menú de navegación"
            >
                <span class="nav-toggle-icon" aria-hidden="true"></span>
            </button>
            <nav id="site-nav" class="nav" :class="{ 'is-open': menuOpen }" aria-label="Principal" @click.outside="menuOpen = false">
                <a href="{{ url('/#soportes') }}" @click="menuOpen = false">Soportes MinSalud</a>
                <a href="{{ url('/#funciones') }}" @click="menuOpen = false">Funciones</a>
                <a href="{{ url('/#descargar') }}" @click="menuOpen = false">Descargar</a>
                <a href="{{ url('/#precios') }}" @click="menuOpen = false">Precios</a>
                <a href="{{ url('/#faq') }}" @click="menuOpen = false">FAQ</a>
                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" @click="menuOpen = false">Contacto</a>
                <button type="button" class="theme-toggle" id="theme-toggle" aria-label="Cambiar tema claro u oscuro" title="Tema claro / oscuro">
                    <span class="icon-sun" aria-hidden="true">☀️</span>
                    <span class="icon-moon" aria-hidden="true">🌙</span>
                </button>
                <a href="{{ url('/comprar') }}" class="btn btn-primary" @click="menuOpen = false">Comprar licencia</a>
            </nav>
        </div>
    </header>

    <main>
        @if (session('status'))
            <div class="site-flash site-flash--success" role="alert">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="site-flash site-flash--error" role="alert">{{ session('error') }}</div>
        @endif
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
                <a href="{{ url('/#soportes') }}">Soportes MinSalud</a><br>
                <a href="{{ url('/#descargar') }}">Descargar</a><br>
                <a href="{{ url('/#precios') }}">Precios</a>
            </div>
            <div>
                <strong class="footer-heading">Contacto</strong>
                <a href="mailto:{{ config('marketing.contact_email') }}">{{ config('marketing.contact_email') }}</a><br>
                <a href="tel:{{ preg_replace('/\s+/', '', config('marketing.contact_phone')) }}">{{ config('marketing.contact_phone') }}</a><br>
                @if(config('marketing.whatsapp'))
                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener">WhatsApp</a>
                @endif
            </div>
        </div>
        <div class="container footer-bottom">
            &copy; {{ date('Y') }} {{ $siteName }} ·
            <a href="{{ config('marketing.author_url') }}">{{ config('marketing.author') }}</a>
            · Colombia
        </div>
    </footer>
    @livewireScripts
    @stack('scripts')
</body>
</html>
