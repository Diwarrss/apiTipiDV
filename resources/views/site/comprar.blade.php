@extends('site.layout')

@section('title', 'Comprar licencia')
@section('meta_description', 'Compra tu licencia TipiDV en línea. Arma tu paquete por cantidad de equipos. Pago seguro con Wompi.')

@php
    $defaultPlan = request('plan', 'annual');
    $defaultQuantity = max(1, min($maxQuantity, (int) request('quantity', 1)));
    $plansJson = json_encode($plans, JSON_UNESCAPED_UNICODE);
    $discountsJson = json_encode($volumeDiscounts, JSON_UNESCAPED_UNICODE);
@endphp

@section('content')
<section class="checkout-section">
    <div class="container checkout-container">
        <div class="section-title" style="margin-bottom:24px">
            <h2>Comprar licencia TipiDV</h2>
            <p class="checkout-lead">3 pasos: elige tu paquete, completa tus datos y paga en Wompi. Recibirás <strong>una clave</strong> para todos los equipos.</p>
        </div>

        <div class="form-card">
            <div id="checkout-error" class="alert-error" style="display:none" role="alert"></div>

            <form id="checkout-form" novalidate>
                {{-- Paso 1 --}}
                <fieldset class="form-step">
                    <legend class="form-step-title">
                        <span class="form-step-num">1</span>
                        Arma tu paquete
                    </legend>

                    <div class="field">
                        <span class="field-label">Plan de licencia</span>
                        <div class="tabs" role="tablist" aria-label="Plan">
                            @foreach($plans as $plan)
                                <button type="button" class="tab {{ ($plan['period'] ?? '') === $defaultPlan ? 'active' : '' }}"
                                    data-period="{{ $plan['period'] }}"
                                    data-uuid="{{ $plan['uuid'] ?? '' }}"
                                    data-unit="{{ (float) ($plan['value_cop'] ?? 0) }}">
                                    {{ $plan['name'] ?? $plan['period'] }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="field" id="quantity-field">
                        <label for="quantity" id="quantity-label">¿Cuántos equipos (PCs)?</label>
                        <div class="qty-stepper">
                            <button type="button" class="qty-btn" id="qty-minus" aria-label="Menos un equipo">−</button>
                            <input type="number" id="quantity" name="quantity" value="{{ $defaultQuantity }}" min="1" max="{{ $maxQuantity }}" required inputmode="numeric" aria-describedby="quantity-hint quantity-error">
                            <button type="button" class="qty-btn" id="qty-plus" aria-label="Más un equipo">+</button>
                        </div>
                        <small id="quantity-hint">1 clave para todos · máx. {{ $maxQuantity }} equipos · descuentos automáticos</small>
                        <span class="field-error" id="quantity-error" role="alert"></span>
                    </div>

                    <div id="price-panel" class="price-panel" aria-live="polite">
                        <div id="price-lines"></div>
                        <p id="selected-price" class="price-total">Total: — COP</p>
                    </div>

                    @if(count($volumeDiscounts) > 0)
                        <div class="discount-badges">
                            @foreach(array_reverse($volumeDiscounts) as $tier)
                                <span class="discount-badge">{{ $tier['label'] ?? '' }}</span>
                            @endforeach
                        </div>
                    @endif
                </fieldset>

                {{-- Paso 2 --}}
                <fieldset class="form-step">
                    <legend class="form-step-title">
                        <span class="form-step-num">2</span>
                        Tus datos de contacto
                    </legend>
                    <p class="form-step-desc">Obligatorios para enviarte la clave y el comprobante.</p>

                    <div class="field">
                        <label for="full_name">Nombre completo <span class="req">*</span></label>
                        <input type="text" id="full_name" name="full_name" required autocomplete="name" maxlength="255"
                            placeholder="Ej. María González" aria-describedby="full_name-error">
                        <span class="field-error" id="full_name-error" role="alert"></span>
                    </div>

                    <div class="field">
                        <label for="email">Correo electrónico <span class="req">*</span></label>
                        <input type="email" id="email" name="email" required autocomplete="email" maxlength="255"
                            placeholder="correo@hospital.gov.co" aria-describedby="email-hint email-error">
                        <small id="email-hint">Aquí llegará tu clave TDV. Usa el mismo correo al activar cada PC.</small>
                        <span class="field-error" id="email-error" role="alert"></span>
                    </div>

                    <div class="field">
                        <label for="phone_number">Teléfono / WhatsApp</label>
                        <input type="tel" id="phone_number" name="phone_number" placeholder="300 123 4567" maxlength="32"
                            autocomplete="tel" inputmode="tel" aria-describedby="phone_number-error">
                        <small>Opcional, pero útil si necesitamos contactarte por el pago.</small>
                        <span class="field-error" id="phone_number-error" role="alert"></span>
                    </div>
                </fieldset>

                {{-- Opcional: facturación --}}
                <details class="form-optional">
                    <summary>Datos de facturación (opcional)</summary>
                    <div class="form-optional-body">
                        <div class="form-grid">
                            <div class="field">
                                <label for="type_id">Tipo de documento</label>
                                <select id="type_id" name="type_id">
                                    <option value="CC">Cédula (CC)</option>
                                    <option value="NIT">NIT (empresa / hospital)</option>
                                    <option value="CE">Cédula extranjería</option>
                                </select>
                            </div>
                            <div class="field">
                                <label for="number_id">Número de documento</label>
                                <input type="text" id="number_id" name="number_id" maxlength="32" placeholder="Sin puntos ni espacios"
                                    inputmode="numeric" aria-describedby="number_id-error">
                                <span class="field-error" id="number_id-error" role="alert"></span>
                            </div>
                        </div>
                        <div class="field" id="org-field" hidden>
                            <label for="organization_name">Nombre hospital / empresa <span class="req">*</span></label>
                            <input type="text" id="organization_name" name="organization_name" maxlength="255"
                                placeholder="Ej. Hospital Regional de San Gil" aria-describedby="organization_name-error">
                            <span class="field-error" id="organization_name-error" role="alert"></span>
                        </div>
                    </div>
                </details>

                {{-- Opcional: ya cliente --}}
                <details class="form-optional" id="existing-client-panel">
                    <summary>¿Ya tienes licencia TipiDV?</summary>
                    <div class="form-optional-body">
                        <p class="form-step-desc" style="margin-top:0">Solo si compraste antes con el mismo correo.</p>
                        <div class="choice-group" role="radiogroup" aria-label="Tipo de compra">
                            <label class="choice-card">
                                <input type="radio" name="purchase_type" value="new_license" checked>
                                <span class="choice-card-body">
                                    <strong>Primera compra</strong>
                                    <small>Nuevo paquete de equipos</small>
                                </span>
                            </label>
                            <label class="choice-card">
                                <input type="radio" name="purchase_type" value="renewal">
                                <span class="choice-card-body">
                                    <strong>Renovar vigencia</strong>
                                    <small>Mismo número de equipos, más tiempo</small>
                                </span>
                            </label>
                            <label class="choice-card">
                                <input type="radio" name="purchase_type" value="new_equipment">
                                <span class="choice-card-body">
                                    <strong>Agregar equipos</strong>
                                    <small>Sumar PCs a tu clave actual</small>
                                </span>
                            </label>
                        </div>
                    </div>
                </details>

                {{-- Paso 3 --}}
                <fieldset class="form-step form-step-submit">
                    <legend class="form-step-title">
                        <span class="form-step-num">3</span>
                        Pagar con Wompi
                    </legend>
                    <p class="form-step-desc">Serás redirigido al checkout seguro. El total se confirma en nuestro servidor.</p>
                    <button type="submit" class="btn btn-primary" id="submit-btn">
                        Continuar al pago seguro
                    </button>
                    <p class="checkout-footnote">
                        Pago procesado por Wompi · <a href="{{ url('/') }}">Volver al inicio</a>
                    </p>
                </fieldset>
            </form>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
(function () {
    const plans = {!! $plansJson !!};
    const volumeDiscounts = {!! $discountsJson !!};
    const maxQuantity = {{ (int) $maxQuantity }};
    const fmtCop = (n) => '$' + Math.round(n).toLocaleString('es-CO');

    const tabs = document.querySelectorAll('.tab');
    const errorEl = document.getElementById('checkout-error');
    const priceLinesEl = document.getElementById('price-lines');
    const priceEl = document.getElementById('selected-price');
    const form = document.getElementById('checkout-form');
    const typeId = document.getElementById('type_id');
    const orgField = document.getElementById('org-field');
    const orgInput = document.getElementById('organization_name');
    const quantityField = document.getElementById('quantity-field');
    const quantityInput = document.getElementById('quantity');
    const quantityLabel = document.getElementById('quantity-label');
    const purchaseRadios = form.querySelectorAll('input[name="purchase_type"]');
    let selectedUuid = '';

    const fields = {
        full_name: document.getElementById('full_name'),
        email: document.getElementById('email'),
        phone_number: document.getElementById('phone_number'),
        number_id: document.getElementById('number_id'),
        organization_name: orgInput,
        quantity: quantityInput,
    };

    function getPurchaseType() {
        const checked = form.querySelector('input[name="purchase_type"]:checked');
        return checked ? checked.value : 'new_license';
    }

    function planForPeriod(period) {
        return plans.find(p => p.period === period) || plans[0];
    }

    function discountPercent(qty) {
        for (const tier of volumeDiscounts) {
            if (qty >= tier.min_quantity) return tier.percent;
        }
        return 0;
    }

    function quote(unit, qty, pType) {
        qty = Math.max(1, Math.min(maxQuantity, qty));
        if (pType === 'renewal') qty = 1;
        const subtotal = unit * qty;
        const pct = pType === 'renewal' ? 0 : discountPercent(qty);
        const discount = Math.round(subtotal * (pct / 100));
        return { qty, unit, subtotal, pct, discount, total: subtotal - discount };
    }

    function clampQty() {
        let v = parseInt(quantityInput.value, 10);
        if (isNaN(v) || v < 1) v = 1;
        if (v > maxQuantity) v = maxQuantity;
        quantityInput.value = v;
        return v;
    }

    function setFieldError(name, message) {
        const input = fields[name];
        const errEl = document.getElementById(name + '-error');
        if (!input || !errEl) return;
        if (message) {
            input.classList.add('input-invalid');
            input.setAttribute('aria-invalid', 'true');
            errEl.textContent = message;
        } else {
            input.classList.remove('input-invalid');
            input.setAttribute('aria-invalid', 'false');
            errEl.textContent = '';
        }
    }

    function validateField(name) {
        const el = fields[name];
        if (!el) return true;
        const v = (el.value || '').trim();
        const pType = getPurchaseType();

        switch (name) {
            case 'full_name':
                if (v.length < 3) {
                    setFieldError(name, 'Escribe tu nombre completo (mínimo 3 caracteres).');
                    return false;
                }
                break;
            case 'email':
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)) {
                    setFieldError(name, v === '' ? 'El correo es obligatorio.' : 'Ingresa un correo válido (ej. nombre@empresa.com).');
                    return false;
                }
                break;
            case 'phone_number':
                if (v !== '' && !/^[\d\s+\-()]{7,20}$/.test(v)) {
                    setFieldError(name, 'Usa solo números. Ej: 300 123 4567');
                    return false;
                }
                break;
            case 'number_id':
                if (v !== '' && !/^[\d.\-]{5,32}$/.test(v)) {
                    setFieldError(name, 'Documento no válido (solo números, puntos o guiones).');
                    return false;
                }
                break;
            case 'organization_name':
                if (typeId.value === 'NIT' && v.length < 2) {
                    setFieldError(name, 'Indica el nombre del hospital o empresa.');
                    return false;
                }
                break;
            case 'quantity':
                if (pType !== 'renewal') {
                    const q = parseInt(v, 10);
                    if (isNaN(q) || q < 1 || q > maxQuantity) {
                        setFieldError(name, `Indica entre 1 y ${maxQuantity} equipos.`);
                        return false;
                    }
                }
                break;
        }
        setFieldError(name, '');
        return true;
    }

    function validateForm() {
        let ok = true;
        ['full_name', 'email', 'phone_number', 'number_id', 'organization_name', 'quantity'].forEach((name) => {
            if (!validateField(name)) ok = false;
        });
        if (!ok) {
            const first = form.querySelector('.input-invalid');
            if (first) {
                first.focus();
                first.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
        return ok;
    }

    Object.keys(fields).forEach((name) => {
        const el = fields[name];
        if (!el) return;
        el.addEventListener('blur', () => validateField(name));
        el.addEventListener('input', () => {
            if (el.classList.contains('input-invalid')) validateField(name);
        });
    });

    function updatePurchaseUi() {
        const pType = getPurchaseType();
        if (pType === 'renewal') {
            quantityField.hidden = true;
            quantityInput.value = '1';
        } else {
            quantityField.hidden = false;
            quantityLabel.textContent = pType === 'new_equipment'
                ? '¿Cuántos equipos adicionales?'
                : '¿Cuántos equipos (PCs)?';
        }
        refreshPrice();
    }

    function refreshPrice() {
        const activeTab = document.querySelector('.tab.active');
        const plan = planForPeriod(activeTab?.dataset.period);
        const unit = parseFloat(activeTab?.dataset.unit || plan?.value_cop || 0);
        const q = quote(unit, clampQty(), getPurchaseType());

        const equipLabel = getPurchaseType() === 'new_equipment'
            ? `${q.qty} equipo(s) adicional(es)`
            : `${q.qty} equipo(s)`;

        let html = `<div class="price-line">${equipLabel} × ${fmtCop(q.unit)} = <strong>${fmtCop(q.subtotal)}</strong></div>`;
        if (q.discount > 0) {
            html += `<div class="price-line price-discount">Descuento ${q.pct}%: −${fmtCop(q.discount)}</div>`;
        }
        priceLinesEl.innerHTML = html;
        priceEl.textContent = 'Total a pagar: ' + fmtCop(q.total) + ' COP';
    }

    function selectTab(btn) {
        tabs.forEach(t => t.classList.remove('active'));
        btn.classList.add('active');
        selectedUuid = btn.dataset.uuid || '';
        refreshPrice();
    }

    const initial = document.querySelector('.tab.active') || tabs[0];
    if (initial) selectTab(initial);

    tabs.forEach(btn => btn.addEventListener('click', () => selectTab(btn)));
    purchaseRadios.forEach(r => r.addEventListener('change', updatePurchaseUi));
    quantityInput.addEventListener('input', refreshPrice);
    quantityInput.addEventListener('change', () => { clampQty(); refreshPrice(); validateField('quantity'); });

    document.getElementById('qty-minus').addEventListener('click', () => {
        quantityInput.value = Math.max(1, clampQty() - 1);
        refreshPrice();
    });
    document.getElementById('qty-plus').addEventListener('click', () => {
        quantityInput.value = Math.min(maxQuantity, clampQty() + 1);
        refreshPrice();
    });

    function syncOrgField() {
        const isNit = typeId.value === 'NIT';
        orgField.hidden = !isNit;
        if (!isNit) setFieldError('organization_name', '');
        else validateField('organization_name');
    }
    typeId.addEventListener('change', syncOrgField);
    syncOrgField();
    updatePurchaseUi();

    function paymentUrl(data) {
        return data.payment_link_url || data.checkout_url || data.url
            || (data.data && (data.data.payment_link_url || data.data.checkout_url));
    }

    function mapServerErrors(data) {
        if (!data.errors) return data.message || null;
        const map = {
            'customer.full_name': 'full_name',
            'customer.email': 'email',
            'customer.phone_number': 'phone_number',
            'customer.number_id': 'number_id',
            'organization_name': 'organization_name',
            'quantity': 'quantity',
        };
        let first = null;
        Object.entries(data.errors).forEach(([key, msgs]) => {
            const field = map[key];
            const msg = Array.isArray(msgs) ? msgs[0] : msgs;
            if (field) {
                setFieldError(field, msg);
                if (!first) first = field;
            }
        });
        if (first) {
            fields[first]?.focus();
            fields[first]?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        return data.message || 'Revisa los campos marcados en rojo.';
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        errorEl.style.display = 'none';
        if (!validateForm()) {
            errorEl.textContent = 'Completa los campos obligatorios antes de continuar.';
            errorEl.style.display = 'block';
            return;
        }

        const btn = document.getElementById('submit-btn');
        btn.disabled = true;
        btn.textContent = 'Preparando pago…';

        const activeTab = document.querySelector('.tab.active');
        const plan = planForPeriod(activeTab?.dataset.period);
        const productId = selectedUuid || plan?.uuid;
        const pType = getPurchaseType();
        const qty = pType === 'renewal' ? 1 : clampQty();

        if (!productId) {
            errorEl.textContent = 'El plan no está disponible. Escríbenos por WhatsApp.';
            errorEl.style.display = 'block';
            btn.disabled = false;
            btn.textContent = 'Continuar al pago seguro';
            return;
        }

        const payload = {
            product_id: productId,
            quantity: qty,
            purchase_type: pType,
            organization_name: orgInput.value.trim() || null,
            return_url: '{{ url('/gracias') }}',
            customer: {
                email: fields.email.value.trim(),
                full_name: fields.full_name.value.trim(),
                phone_number: fields.phone_number.value.trim() || null,
                type_id: typeId.value,
                number_id: fields.number_id.value.trim() || null,
            },
        };

        try {
            const res = await fetch('{{ url('/api/checkout') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(payload),
            });
            const data = await res.json();
            if (!res.ok) {
                const msg = mapServerErrors(data) || data.message || data.detail || 'No se pudo iniciar el pago';
                throw new Error(msg);
            }
            const url = paymentUrl(data);
            if (!url) throw new Error('Respuesta de pago sin URL de checkout');
            window.location.href = url;
        } catch (err) {
            errorEl.textContent = err.message || 'Error de conexión. Intenta de nuevo.';
            errorEl.style.display = 'block';
            btn.disabled = false;
            btn.textContent = 'Continuar al pago seguro';
        }
    });
})();
</script>
@endpush
