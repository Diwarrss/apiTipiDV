@extends('site.layout')

{{-- Título SEO principal viene de config marketing.seo.title --}}

@php
    $fmt = fn (float $n) => '$' . number_format($n, 0, ',', '.');
    $annual = collect($plans)->firstWhere('period', 'annual') ?? $plans[1] ?? $plans[0] ?? null;
    $monthly = collect($plans)->firstWhere('period', 'monthly') ?? $plans[0] ?? null;
@endphp

@section('content')
<section class="hero">
    <div class="container hero-grid">
        <div>
            <h1>Tipifica tus soportes PDF <em>en minutos</em>, no en horas</h1>
            <p class="hero-lead">
                {{ config('marketing.tagline') }}.
                Clasifica FEV, HEV, EPI y más con colores, exporta por tipo y divide archivos pesados.
                Licencia por equipo — ideal para facturación hospitalaria en Colombia.
            </p>
            <div class="hero-actions">
                <a href="{{ url('/comprar') }}" class="btn btn-primary">Comprar licencia</a>
                @if(!empty($downloadUrl))
                    <a href="{{ $downloadUrl }}" class="btn btn-outline" rel="noopener">Descargar prueba</a>
                @else
                    <a href="https://wa.me/{{ config('marketing.whatsapp') }}?text=Hola,%20quiero%20información%20sobre%20TipiDV" class="btn btn-outline" target="_blank" rel="noopener">Solicitar demo</a>
                @endif
            </div>
            <div class="hero-badges">
                <span class="badge">🖥️ Windows 10/11</span>
                <span class="badge">🏥 Hospitales y clínicas</span>
                <span class="badge">💳 Pago seguro Wompi</span>
                <span class="badge">📧 Activación por correo</span>
            </div>
        </div>
        <div class="hero-card">
            <h3>Así funciona</h3>
            <ol class="mini-flow">
                <li><span class="step-num">1</span> Compras la licencia en línea (1 PC por licencia)</li>
                <li><span class="step-num">2</span> Recibes tu clave <code>TDV-XXXX-…</code> por email</li>
                <li><span class="step-num">3</span> Instalas TipiDV en el equipo de digitalización</li>
                <li><span class="step-num">4</span> Activas con correo + clave y empiezas a tipificar</li>
            </ol>
        </div>
    </div>
</section>

<section id="funciones" class="section-alt">
    <div class="container">
        <div class="section-title">
            <h2>Todo lo que necesitas para digitalizar bien</h2>
            <p>Reemplaza flujos manuales y digitalizadores lentos con una herramienta pensada para el día a día del hospital.</p>
        </div>
        <div class="features">
            <article class="feature">
                <div class="feature-icon">📑</div>
                <h3>Tipificación visual</h3>
                <p>Panel de tipos con colores, miniaturas y vista previa. Marca cada página sin perder el contexto del lote.</p>
            </article>
            <article class="feature">
                <div class="feature-icon">📤</div>
                <h3>Exportación inteligente</h3>
                <p>Genera PDFs separados por tipo, con prefijo de factura y división automática cuando el archivo supera el límite en MB.</p>
            </article>
            <article class="feature">
                <div class="feature-icon">⚙️</div>
                <h3>Tipos configurables</h3>
                <p>Define nombres, colores y orden de tus tipificaciones. Adapta TipiDV a tu protocolo interno o a SYC.</p>
            </article>
            <article class="feature">
                <div class="feature-icon">🔐</div>
                <h3>Licencia por equipo</h3>
                <p>Una activación por PC. Hospitales pueden sumar equipos con el mismo correo institucional.</p>
            </article>
            <article class="feature">
                <div class="feature-icon">🌐</div>
                <h3>Modo offline</h3>
                <p>Validación en línea periódica con gracia de {{ config('licensing.offline_grace_days', 14) }} días sin internet.</p>
            </article>
            <article class="feature">
                <div class="feature-icon">💼</div>
                <h3>Para hospitales</h3>
                <p>Facturación con NIT, varios puestos de trabajo y renovación anual sencilla para el área de sistemas.</p>
            </article>
        </div>
    </div>
</section>

<section id="precios" class="section-alt">
    <div class="container">
        <div class="section-title">
            <h2>Precios claros, sin sorpresas</h2>
            <p>Licencia por computador. Precios en pesos colombianos (COP), actualizables según el plan elegido.</p>
        </div>
        <div class="pricing-grid">
            @foreach($plans as $plan)
                @php
                    $isAnnual = ($plan['period'] ?? '') === 'annual' || ($plan['billing_months'] ?? 0) >= 12;
                    $featured = $isAnnual || !empty($plan['featured']);
                @endphp
                <article class="price-card {{ $featured ? 'featured' : '' }}">
                    @if($featured)
                        <span class="tag">Recomendado</span>
                    @endif
                    <h3>{{ $plan['name'] ?? 'Plan' }}</h3>
                    <p style="margin:0;color:var(--muted);font-size:.9rem">
                        {{ $isAnnual ? '12 meses · 1 equipo' : '1 mes · 1 equipo' }}
                    </p>
                    <div class="amount">
                        {{ $fmt((float) ($plan['value_cop'] ?? 0)) }}
                        <small>COP / {{ $isAnnual ? 'año' : 'mes' }}</small>
                    </div>
                    <ul>
                        <li>1 PC activado por licencia</li>
                        <li>Clave por correo electrónico</li>
                        <li>Soporte por WhatsApp y email</li>
                        @if($isAnnual)
                            <li>Ahorro vs plan mensual</li>
                            <li>Renovación con mismo correo</li>
                        @else
                            <li>Ideal para probar o equipos temporales</li>
                        @endif
                    </ul>
                    <a href="{{ url('/comprar') }}?plan={{ $plan['period'] ?? 'annual' }}" class="btn {{ $featured ? 'btn-primary' : 'btn-outline' }}" style="width:100%">
                        Elegir plan
                    </a>
                </article>
            @endforeach
        </div>
        <p style="text-align:center;color:var(--muted);font-size:.9rem;margin-top:24px">
            ¿Varios equipos en el hospital? Compra licencias adicionales con el mismo correo institucional.
            <a href="https://wa.me/{{ config('marketing.whatsapp') }}">Escríbenos</a> si necesitas cotización.
        </p>
    </div>
</section>

<section id="faq">
    <div class="container">
        <div class="section-title">
            <h2>Preguntas frecuentes</h2>
        </div>
        <div class="faq">
            <details>
                <summary>¿Qué es TipiDV?</summary>
                <p>Es una aplicación Windows para clasificar (tipificar) soportes PDF de facturación hospitalaria: separa por tipo, exporta archivos listos para cargar y reduce el trabajo manual del digitalizador.</p>
            </details>
            <details>
                <summary>¿Cuántos equipos cubre una licencia?</summary>
                <p>Una licencia activa un solo PC. Para más equipos, compra licencias adicionales usando el mismo correo (tipo <em>nuevo equipo</em> en el checkout).</p>
            </details>
            <details>
                <summary>¿Cómo recibo la clave de activación?</summary>
                <p>Tras el pago aprobado en Wompi, enviamos automáticamente un correo con tu clave <code>TDV-XXXX-XXXX-XXXX</code>. La usas una vez en cada equipo.</p>
            </details>
            <details>
                <summary>¿Funciona sin internet?</summary>
                <p>Sí, con validación periódica. Si pierdes conexión, puedes seguir trabajando hasta {{ config('licensing.offline_grace_days', 14) }} días mientras se restablece la red.</p>
            </details>
            <details>
                <summary>¿Reemplaza SYC u otros digitalizadores?</summary>
                <p>TipiDV se enfoca en la tipificación y exportación de PDFs. Muchos hospitales lo usan junto a su flujo actual o como reemplazo del paso de clasificación manual.</p>
            </details>
        </div>
    </div>
</section>

<section class="cta-band">
    <div class="container" style="padding:48px 0">
        <h2>¿Listo para ordenar tus soportes?</h2>
        <p>Empieza hoy — activación en minutos después del pago.</p>
        <a href="{{ url('/comprar') }}" class="btn btn-cta">Comprar ahora</a>
    </div>
</section>
@endsection
