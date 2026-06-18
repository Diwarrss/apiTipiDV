@extends('site.layout')

{{-- Título SEO principal viene de config marketing.seo.title --}}

@php
    $fmt = fn (float $n) => '$' . number_format($n, 0, ',', '.');
    $defaultPlan = request('plan', 'annual');
    $plansJson = json_encode($plans, JSON_UNESCAPED_UNICODE);
    $discountsJson = json_encode($volumeDiscounts, JSON_UNESCAPED_UNICODE);
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
                <li><span class="step-num">1</span> Armas tu paquete en línea (plan + cantidad de PCs)</li>
                <li><span class="step-num">2</span> Recibes una clave <code>TDV-XXXX-…</code> por email</li>
                <li><span class="step-num">3</span> Instalas TipiDV en cada equipo de digitalización</li>
                <li><span class="step-num">4</span> Activas con el mismo correo + clave en cada PC</li>
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
                <p>Paga por cantidad de PCs. Una clave institucional puede cubrir varios equipos del hospital.</p>
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
            <p>Licencia por computador. Precios en pesos colombianos (COP). Simula tu paquete antes de pagar.</p>
        </div>

        <div class="form-card pricing-preview">
            <div class="pricing-preview-grid">
                <div class="pricing-preview-controls">
                    <p class="pricing-plan-hint" style="margin-top:0;font-weight:600;color:var(--text-strong)">Elige el plan</p>
                    <div class="tabs" role="tablist" id="home-plan-tabs">
                        @foreach($plans as $plan)
                            @php $isAnnual = ($plan['period'] ?? '') === 'annual'; @endphp
                            <button type="button"
                                class="tab {{ ($plan['period'] ?? '') === $defaultPlan ? 'active' : '' }}"
                                data-period="{{ $plan['period'] }}"
                                data-unit="{{ (float) ($plan['value_cop'] ?? 0) }}"
                                data-billing="{{ (int) ($plan['billing_months'] ?? 1) }}">
                                {{ $plan['name'] ?? $plan['period'] }}
                                @if($isAnnual)
                                    <span style="display:block;font-size:.7rem;font-weight:500;opacity:.9">Recomendado</span>
                                @endif
                            </button>
                        @endforeach
                    </div>

                    <div class="field" style="margin-bottom:8px">
                        <label for="home-quantity">Cantidad de equipos (PCs)</label>
                        <div class="qty-stepper">
                            <button type="button" class="qty-btn" id="home-qty-minus" aria-label="Menos">−</button>
                            <input type="number" id="home-quantity" value="1" min="1" max="{{ $maxQuantity }}" inputmode="numeric">
                            <button type="button" class="qty-btn" id="home-qty-plus" aria-label="Más">+</button>
                        </div>
                        <small>1 clave TDV para todos los equipos del paquete · máx. {{ $maxQuantity }}</small>
                    </div>

                    @if(count($volumeDiscounts) > 0)
                        <div class="discount-badges" style="margin-bottom:0">
                            @foreach(array_reverse($volumeDiscounts) as $tier)
                                <span class="discount-badge">{{ $tier['label'] ?? '' }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="pricing-preview-summary">
                    <p style="margin:0 0 8px;font-size:.85rem;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.04em">Tu cotización</p>
                    <div id="home-price-lines"></div>
                    <p id="home-price-total" class="price-total">—</p>
                    <p id="home-price-per-unit" class="price-per-unit"></p>
                    <p class="pricing-preview-note">
                        Incluye activación por correo, soporte y pago seguro con Wompi.
                        El total final se confirma al crear el pago (calculado en servidor).
                    </p>
                    <a href="{{ url('/comprar') }}" id="home-checkout-link" class="btn btn-primary" style="width:100%">
                        Ir a comprar con este paquete
                    </a>
                </div>
            </div>
        </div>

        <div class="pricing-grid">
            @foreach($plans as $plan)
                @php
                    $isAnnual = ($plan['period'] ?? '') === 'annual' || ($plan['billing_months'] ?? 0) >= 12;
                    $featured = $isAnnual || !empty($plan['featured']);
                    $unit = (float) ($plan['value_cop'] ?? 0);
                @endphp
                <article class="price-card {{ $featured ? 'featured' : '' }}">
                    @if($featured)
                        <span class="tag">Recomendado</span>
                    @endif
                    <h3>{{ $plan['name'] ?? 'Plan' }}</h3>
                    <p style="margin:0;color:var(--muted);font-size:.9rem">
                        {{ $isAnnual ? '12 meses' : '1 mes' }} · precio por equipo
                    </p>
                    <div class="amount">
                        {{ $fmt($unit) }}
                        <small>COP / equipo / {{ $isAnnual ? 'año' : 'mes' }}</small>
                    </div>
                    <ul>
                        <li>Paquetes de 1 a {{ $maxQuantity }} equipos</li>
                        <li>1 clave para todo el paquete</li>
                        <li>Descuentos automáticos por volumen</li>
                        <li>Soporte por WhatsApp y email</li>
                        @if($isAnnual)
                            <li>Mejor valor vs plan mensual</li>
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
            ¿Necesitas más de {{ $maxQuantity }} equipos o facturación especial?
            <a href="https://wa.me/{{ config('marketing.whatsapp') }}">Escríbenos por WhatsApp</a>.
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
                <p>Armas un paquete de 1 a {{ $maxQuantity }} PCs. Recibes <strong>una clave TDV</strong> válida para todos los equipos pagados. En cada PC usas el mismo correo y la misma clave. Si necesitas más equipos después, puedes agregarlos desde el checkout.</p>
            </details>
            <details>
                <summary>¿Cómo recibo la clave de activación?</summary>
                <p>Tras el pago aprobado en Wompi, enviamos un correo con tu clave <code>TDV-XXXX-XXXX-XXXX</code> y cuántos equipos incluye. Actívala en cada PC con el mismo correo.</p>
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

@push('scripts')
<script>
(function () {
    const plans = {!! $plansJson !!};
    const volumeDiscounts = {!! $discountsJson !!};
    const maxQuantity = {{ (int) $maxQuantity }};
    const comprarBase = @json(url('/comprar'));
    const fmtCop = (n) => '$' + Math.round(n).toLocaleString('es-CO');

    const tabs = document.querySelectorAll('#home-plan-tabs .tab');
    const qtyInput = document.getElementById('home-quantity');
    const linesEl = document.getElementById('home-price-lines');
    const totalEl = document.getElementById('home-price-total');
    const perUnitEl = document.getElementById('home-price-per-unit');
    const checkoutLink = document.getElementById('home-checkout-link');

    function discountPercent(qty) {
        for (const tier of volumeDiscounts) {
            if (qty >= tier.min_quantity) return tier.percent;
        }
        return 0;
    }

    function quote(unit, qty) {
        qty = Math.max(1, Math.min(maxQuantity, qty));
        const subtotal = unit * qty;
        const pct = discountPercent(qty);
        const discount = Math.round(subtotal * (pct / 100));
        return { qty, unit, subtotal, pct, discount, total: subtotal - discount };
    }

    function clampQty() {
        let v = parseInt(qtyInput.value, 10);
        if (isNaN(v) || v < 1) v = 1;
        if (v > maxQuantity) v = maxQuantity;
        qtyInput.value = v;
        return v;
    }

    function activeTab() {
        return document.querySelector('#home-plan-tabs .tab.active') || tabs[0];
    }

    function refresh() {
        const tab = activeTab();
        if (!tab) return;

        const unit = parseFloat(tab.dataset.unit || '0');
        const billing = parseInt(tab.dataset.billing || '12', 10);
        const period = tab.dataset.period || 'annual';
        const isAnnual = billing >= 12;
        const q = quote(unit, clampQty());

        let html = `<div class="price-line">${q.qty} equipo(s) × ${fmtCop(q.unit)} = <strong>${fmtCop(q.subtotal)}</strong></div>`;
        if (q.discount > 0) {
            html += `<div class="price-line price-discount">Descuento ${q.pct}%: −${fmtCop(q.discount)}</div>`;
        }
        linesEl.innerHTML = html;
        totalEl.textContent = fmtCop(q.total) + ' COP';
        const perUnit = Math.round(q.total / q.qty);
        perUnitEl.innerHTML = `Equivale a <strong>${fmtCop(perUnit)} COP</strong> por equipo / ${isAnnual ? 'año' : 'mes'}`;

        const params = new URLSearchParams({ plan: period, quantity: String(q.qty) });
        checkoutLink.href = comprarBase + '?' + params.toString();
    }

    tabs.forEach(btn => btn.addEventListener('click', () => {
        tabs.forEach(t => t.classList.remove('active'));
        btn.classList.add('active');
        refresh();
    }));

    qtyInput.addEventListener('input', refresh);
    qtyInput.addEventListener('change', refresh);
    document.getElementById('home-qty-minus').addEventListener('click', () => {
        qtyInput.value = Math.max(1, clampQty() - 1);
        refresh();
    });
    document.getElementById('home-qty-plus').addEventListener('click', () => {
        qtyInput.value = Math.min(maxQuantity, clampQty() + 1);
        refresh();
    });

    refresh();
})();
</script>
@endpush
