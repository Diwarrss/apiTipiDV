@extends('site.layout')

@section('seo_page', 'home')
@section('meta_description', config('marketing.seo.description'))

@php
    $fmt = fn(float $n) => '$' . number_format($n, 0, ',', '.');
    $defaultPlan = request('plan', 'annual');
    $plansJson = json_encode($plans, JSON_UNESCAPED_UNICODE);
    $discountsJson = json_encode($volumeDiscounts, JSON_UNESCAPED_UNICODE);
    $featureGroups = config('marketing.feature_groups', []);
@endphp

@section('content')
    <section class="hero">
        <div class="container hero-grid">
            <div>
                <span class="hero-eyebrow">Tipificador PDF · Windows</span>
                <h1>Clasifica lotes PDF <em>y exporta por tipo</em></h1>
                <p class="hero-lead">
                    {{ config('marketing.tagline') }}.
                    Ideal para <strong>soportes MinSalud</strong> (FEV, HEV, EPI…), y también para cualquier flujo donde
                    debas
                    separar páginas escaneadas en archivos nombrados y carpetas por factura o radicado.
                </p>
                <div class="hero-actions">
                    <a href="{{ url('/comprar') }}" class="btn btn-primary">Comprar licencia</a>
                    @if (!empty($downloadUrl))
                        <a href="{{ $downloadUrl }}" class="btn btn-outline">Descargar instalador</a>
                    @else
                        <a href="{{ url('/#descargar') }}" class="btn btn-outline">Descargar</a>
                    @endif
                    <a href="https://wa.me/{{ config('marketing.whatsapp') }}?text={{ rawurlencode('Hola, quiero información sobre TipiDV') }}"
                        class="btn btn-outline" target="_blank" rel="noopener">Contactar</a>
                </div>
                <div class="hero-badges">
                    <span class="badge">📋 MinSalud · FEV · RIPS</span>
                    <span class="badge">🏷️ Tipos personalizables</span>
                    <span class="badge">🖥️ Windows 10/11</span>
                    <span class="badge">💳 Pago Wompi</span>
                </div>
            </div>
            <div class="hero-flow">
                <div class="hero-flow-header">
                    <span class="hero-flow-tag">Así de simple</span>
                    <h3 class="hero-flow-title">De lote PDF a archivos listos</h3>
                </div>
                <ol class="flow-steps">
                    <li class="flow-step">
                        <div class="flow-step-track" aria-hidden="true">
                            <span class="flow-step-num">1</span>
                            <span class="flow-step-line"></span>
                        </div>
                        <div class="flow-step-card">
                            <span class="flow-step-icon" aria-hidden="true">📥</span>
                            <div class="flow-step-text">
                                <strong>Carga o escanea</strong>
                                <p>Abre el lote de soportes PDF o escanea directo desde TipiDV.</p>
                            </div>
                        </div>
                    </li>
                    <li class="flow-step">
                        <div class="flow-step-track" aria-hidden="true">
                            <span class="flow-step-num">2</span>
                            <span class="flow-step-line"></span>
                        </div>
                        <div class="flow-step-card">
                            <span class="flow-step-icon" aria-hidden="true">🏷️</span>
                            <div class="flow-step-text">
                                <strong>Tipifica cada página</strong>
                                <p>Marca el tipo de cada página — MinSalud o los tuyos — con colores y miniaturas.</p>
                            </div>
                        </div>
                    </li>
                    <li class="flow-step">
                        <div class="flow-step-track" aria-hidden="true">
                            <span class="flow-step-num">3</span>
                            <span class="flow-step-line"></span>
                        </div>
                        <div class="flow-step-card">
                            <span class="flow-step-icon" aria-hidden="true">⚡</span>
                            <div class="flow-step-text">
                                <strong>Procesa la factura</strong>
                                <p>Prefijo → genera un PDF por tipo en la carpeta de salida.</p>
                            </div>
                        </div>
                    </li>
                    <li class="flow-step flow-step--done">
                        <div class="flow-step-track" aria-hidden="true">
                            <span class="flow-step-num flow-step-num--done">✓</span>
                        </div>
                        <div class="flow-step-card flow-step-card--done">
                            <span class="flow-step-icon" aria-hidden="true">🚀</span>
                            <div class="flow-step-text">
                                <strong>Sube a tu sistema</strong>
                                <p>Archivos renombrados y organizados, listos para cargar o archivar.</p>
                                <span class="flow-step-badge">Listo para usar</span>
                            </div>
                        </div>
                    </li>
                </ol>
            </div>
        </div>
    </section>

    @include('site.partials.use-cases')

    @include('site.partials.minsalud-soportes')

    <section id="funciones" class="section-band">
        <div class="container">
            <header class="section-head">
                <span class="section-eyebrow">Funciones</span>
                <h2>Todo lo que hace TipiDV</h2>
                <p>Desde la digitalización hasta la carpeta de salida — conforme a tu plantilla de tipos.</p>
            </header>

            <div class="feature-groups">
                @foreach ($featureGroups as $group)
                    <div class="feature-group">
                        <h3 class="feature-group-title">{{ $group['title'] ?? '' }}</h3>
                        <div class="features features--compact">
                            @foreach ($group['items'] ?? [] as $feature)
                                <article class="feature">
                                    <div class="feature-icon">{{ $feature['icon'] ?? '✓' }}</div>
                                    <h4>{{ $feature['title'] ?? '' }}</h4>
                                    <p>{{ $feature['desc'] ?? '' }}</p>
                                </article>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    @include('site.partials.download')

    <section id="precios" class="section-band section-band--alt">
        <div class="container">
            <header class="section-head">
                <span class="section-eyebrow">Precios</span>
                <h2>Licencia por equipo, paquetes claros</h2>
                <p>Pesos colombianos (COP). Simula tu paquete antes de pagar con Wompi.</p>
            </header>

            <div class="form-card pricing-preview">
                <div class="pricing-preview-grid">
                    <div class="pricing-preview-controls">
                        <p class="pricing-plan-hint" style="margin-top:0;font-weight:600;color:var(--text-strong)">Elige el
                            plan</p>
                        <div class="plan-picker" role="tablist" id="home-plan-tabs" aria-label="Plan">
                            @foreach ($plans as $plan)
                                @php
                                    $isAnnual =
                                        ($plan['period'] ?? '') === 'annual' ||
                                        (int) ($plan['billing_months'] ?? 0) >= 12;
                                    $isActive = ($plan['period'] ?? '') === $defaultPlan;
                                @endphp
                                <button type="button" class="plan-option tab {{ $isActive ? 'active' : '' }}"
                                    data-period="{{ $plan['period'] }}"
                                    data-unit="{{ (float) ($plan['value_cop'] ?? 0) }}"
                                    data-billing="{{ (int) ($plan['billing_months'] ?? 1) }}">
                                    <span class="plan-option-name">{{ $plan['name'] ?? $plan['period'] }}</span>
                                    <span class="plan-option-price">{{ $fmt((float) ($plan['value_cop'] ?? 0)) }} / equipo
                                        / {{ $isAnnual ? 'año' : 'mes' }}</span>
                                    @if ($isAnnual)
                                        <span class="plan-option-badge">Recomendado</span>
                                    @endif
                                </button>
                            @endforeach
                        </div>

                        <div class="field" style="margin-bottom:8px">
                            <label for="home-quantity">Cantidad de equipos (PCs)</label>
                            <div class="qty-stepper">
                                <button type="button" class="qty-btn" id="home-qty-minus" aria-label="Menos">−</button>
                                <input type="number" id="home-quantity" value="1" min="1"
                                    max="{{ $maxQuantity }}" inputmode="numeric">
                                <button type="button" class="qty-btn" id="home-qty-plus" aria-label="Más">+</button>
                            </div>
                            <small>1 clave TDV · máx. {{ $maxQuantity }} equipos</small>
                        </div>

                        @if (count($volumeDiscounts) > 0)
                            <div class="discount-badges" style="margin-bottom:0">
                                @foreach (array_reverse($volumeDiscounts) as $tier)
                                    <span class="discount-badge">{{ $tier['label'] ?? '' }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="pricing-preview-summary">
                        <p class="pricing-summary-label">Tu cotización</p>
                        <div id="home-price-lines"></div>
                        <p id="home-price-total" class="price-total">—</p>
                        <p id="home-price-per-unit" class="price-per-unit"></p>
                        <p class="pricing-preview-note">
                            Activación por correo · soporte · pago seguro Wompi.
                        </p>
                        <a href="{{ url('/comprar') }}" id="home-checkout-link" class="btn btn-primary"
                            style="width:100%">
                            Ir a comprar
                        </a>
                    </div>
                </div>
            </div>

            <div class="pricing-grid">
                @foreach ($plans as $plan)
                    @php
                        $isAnnual = ($plan['period'] ?? '') === 'annual' || ($plan['billing_months'] ?? 0) >= 12;
                        $featured = $isAnnual || !empty($plan['featured']);
                        $unit = (float) ($plan['value_cop'] ?? 0);
                    @endphp
                    <article class="price-card {{ $featured ? 'featured' : '' }}">
                        @if ($featured)
                            <span class="tag">Recomendado</span>
                        @endif
                        <h3>{{ $plan['name'] ?? 'Plan' }}</h3>
                        <p style="margin:0;color:var(--muted);font-size:.9rem">
                            {{ $isAnnual ? '12 meses' : '1 mes' }} · por equipo
                        </p>
                        <div class="amount">
                            {{ $fmt($unit) }}
                            <small>COP / equipo / {{ $isAnnual ? 'año' : 'mes' }}</small>
                        </div>
                        <ul>
                            <li>1 a {{ $maxQuantity }} equipos por paquete</li>
                            <li>1 clave para todo el paquete</li>
                            <li>Descuentos por volumen</li>
                            <li>Soporte WhatsApp y email</li>
                        </ul>
                        <a href="{{ url('/comprar') }}?plan={{ $plan['period'] ?? 'annual' }}"
                            class="btn {{ $featured ? 'btn-primary' : 'btn-outline' }}" style="width:100%">
                            Elegir plan
                        </a>
                    </article>
                @endforeach
            </div>
            <p class="section-footnote">
                ¿Más de {{ $maxQuantity }} equipos?
                <a href="https://wa.me/{{ config('marketing.whatsapp') }}">Escríbenos por WhatsApp</a>.
            </p>
        </div>
    </section>

    <section id="faq" class="section-band">
        <div class="container">
            <header class="section-head">
                <span class="section-eyebrow">FAQ</span>
                <h2>Preguntas frecuentes</h2>
            </header>
            <div class="faq">
                @foreach(config('marketing.faq', []) as $item)
                    <details>
                        <summary>{{ $item['question'] ?? '' }}</summary>
                        @if(($item['template'] ?? '') === 'download')
                            <p>Descarga <strong>TipiDV-Setup.exe</strong> en <a href="{{ url('/#descargar') }}">Descargar</a> o en
                                el correo tras comprar. Windows 10/11, 64 bits.</p>
                        @elseif(($item['template'] ?? '') === 'offline')
                            <p>Sí, con validación periódica y {{ config('licensing.offline_grace_days', 14) }} días de gracia sin
                                conexión.</p>
                        @else
                            <p>{{ $item['answer'] ?? '' }}</p>
                        @endif
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    <section class="cta-band">
        <div class="container cta-band-inner">
            <h2>¿Listo para ordenar tus PDFs?</h2>
            <p>Instala TipiDV, activa tu licencia y exporta con la plantilla MinSalud o con tus propios tipos.</p>
            <a href="{{ url('/comprar') }}" class="btn btn-cta">Comprar ahora</a>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        (function() {
            const plans = {!! $plansJson !!};
            const volumeDiscounts = {!! $discountsJson !!};
            const maxQuantity = {{ (int) $maxQuantity }};
            const comprarBase = @json(url('/comprar'));
            const fmtCop = (n) => '$' + Math.round(n).toLocaleString('es-CO');

            const tabs = document.querySelectorAll('#home-plan-tabs .plan-option, #home-plan-tabs .tab');
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
                return {
                    qty,
                    unit,
                    subtotal,
                    pct,
                    discount,
                    total: subtotal - discount
                };
            }

            function clampQty() {
                let v = parseInt(qtyInput.value, 10);
                if (isNaN(v) || v < 1) v = 1;
                if (v > maxQuantity) v = maxQuantity;
                qtyInput.value = v;
                return v;
            }

            function activeTab() {
                return document.querySelector('#home-plan-tabs .plan-option.active') ||
                    document.querySelector('#home-plan-tabs .tab.active') ||
                    tabs[0];
            }

            function refresh() {
                const tab = activeTab();
                if (!tab) return;

                const unit = parseFloat(tab.dataset.unit || '0');
                const billing = parseInt(tab.dataset.billing || '12', 10);
                const period = tab.dataset.period || 'annual';
                const isAnnual = billing >= 12;
                const q = quote(unit, clampQty());

                let html =
                    `<div class="price-line">${q.qty} equipo(s) × ${fmtCop(q.unit)} = <strong>${fmtCop(q.subtotal)}</strong></div>`;
                if (q.discount > 0) {
                    html += `<div class="price-line price-discount">Descuento ${q.pct}%: −${fmtCop(q.discount)}</div>`;
                }
                linesEl.innerHTML = html;
                totalEl.textContent = fmtCop(q.total) + ' COP';
                const perUnit = Math.round(q.total / q.qty);
                perUnitEl.innerHTML =
                    `Equivale a <strong>${fmtCop(perUnit)} COP</strong> / equipo / ${isAnnual ? 'año' : 'mes'}`;

                checkoutLink.href = comprarBase + '?' + new URLSearchParams({
                    plan: period,
                    quantity: String(q.qty)
                });
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
