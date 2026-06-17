@extends('site.layout')

@section('title', 'Comprar licencia')
@section('meta_description', 'Compra tu licencia TipiDV en línea. Pago seguro con Wompi. Recibe tu clave por correo y activa en tu PC Windows.')

@php
    $fmt = fn (float $n) => '$' . number_format($n, 0, ',', '.');
    $defaultPlan = request('plan', 'annual');
    $plansJson = json_encode($plans, JSON_UNESCAPED_UNICODE);
@endphp

@section('content')
<section style="padding:48px 0 64px">
    <div class="container" style="max-width:720px">
        <div class="section-title" style="margin-bottom:28px">
            <h2>Comprar licencia TipiDV</h2>
            <p>Completa tus datos y serás redirigido al pago seguro de Wompi.</p>
        </div>

        <div class="form-card">
            <div id="checkout-error" class="alert-error" style="display:none" role="alert"></div>

            <div class="tabs" role="tablist">
                @foreach($plans as $plan)
                    <button type="button" class="tab {{ ($plan['period'] ?? '') === $defaultPlan ? 'active' : '' }}"
                        data-period="{{ $plan['period'] }}"
                        data-uuid="{{ $plan['uuid'] ?? '' }}"
                        data-price="{{ $fmt((float) ($plan['value_cop'] ?? 0)) }}">
                        {{ $plan['name'] ?? $plan['period'] }}
                    </button>
                @endforeach
            </div>

            <p id="selected-price" style="font-size:1.1rem;font-weight:700;margin:0 0 20px"></p>

            <form id="checkout-form" novalidate>
                <div class="field">
                    <label for="purchase_type">Tipo de compra</label>
                    <select id="purchase_type" name="purchase_type" required>
                        <option value="new_license">Licencia nueva (primer equipo)</option>
                        <option value="renewal">Renovar vigencia (mismo equipo)</option>
                        <option value="new_equipment">Equipo adicional (mismo correo)</option>
                    </select>
                </div>

                <div class="form-grid">
                    <div class="field">
                        <label for="full_name">Nombre completo *</label>
                        <input type="text" id="full_name" name="full_name" required autocomplete="name" maxlength="255">
                    </div>
                    <div class="field">
                        <label for="email">Correo electrónico *</label>
                        <input type="email" id="email" name="email" required autocomplete="email" maxlength="255">
                        <small style="color:var(--muted)">Aquí llegará tu clave TDV-…</small>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="field">
                        <label for="type_id">Tipo documento</label>
                        <select id="type_id" name="type_id">
                            <option value="CC">Cédula (CC)</option>
                            <option value="NIT">NIT (empresa/hospital)</option>
                            <option value="CE">Cédula extranjería</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="number_id">Número documento</label>
                        <input type="text" id="number_id" name="number_id" maxlength="32">
                    </div>
                </div>

                <div class="field" id="org-field" style="display:none">
                    <label for="organization_name">Nombre hospital / empresa</label>
                    <input type="text" id="organization_name" name="organization_name" maxlength="255">
                </div>

                <div class="field">
                    <label for="phone_number">Teléfono / WhatsApp</label>
                    <input type="tel" id="phone_number" name="phone_number" placeholder="300 123 4567" maxlength="32">
                </div>

                <button type="submit" class="btn btn-primary" id="submit-btn" style="width:100%;margin-top:8px">
                    Ir a pagar con Wompi
                </button>
                <p style="text-align:center;font-size:.8rem;color:var(--muted);margin:16px 0 0">
                    Al continuar aceptas que procesemos tu pago de forma segura con Wompi.
                    <a href="{{ url('/') }}">Volver al inicio</a>
                </p>
            </form>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
(function () {
    const plans = {!! $plansJson !!};
    const tabs = document.querySelectorAll('.tab');
    const errorEl = document.getElementById('checkout-error');
    const priceEl = document.getElementById('selected-price');
    const form = document.getElementById('checkout-form');
    const typeId = document.getElementById('type_id');
    const orgField = document.getElementById('org-field');
    let selectedUuid = '';

    function planForPeriod(period) {
        return plans.find(p => p.period === period) || plans[0];
    }

    function selectTab(btn) {
        tabs.forEach(t => t.classList.remove('active'));
        btn.classList.add('active');
        selectedUuid = btn.dataset.uuid || '';
        priceEl.textContent = 'Total: ' + (btn.dataset.price || '') + ' COP';
    }

    const initial = document.querySelector('.tab.active') || tabs[0];
    if (initial) selectTab(initial);

    tabs.forEach(btn => btn.addEventListener('click', () => selectTab(btn)));

    typeId.addEventListener('change', () => {
        orgField.style.display = typeId.value === 'NIT' ? 'block' : 'none';
    });

    function paymentUrl(data) {
        return data.payment_link_url
            || data.checkout_url
            || data.url
            || (data.data && (data.data.payment_link_url || data.data.checkout_url));
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        errorEl.style.display = 'none';
        const btn = document.getElementById('submit-btn');
        btn.disabled = true;
        btn.textContent = 'Procesando…';

        const activeTab = document.querySelector('.tab.active');
        const plan = planForPeriod(activeTab?.dataset.period);
        const productId = selectedUuid || plan?.uuid;

        if (!productId) {
            errorEl.textContent = 'El plan no está disponible en este momento. Escríbenos por WhatsApp o correo.';
            errorEl.style.display = 'block';
            btn.disabled = false;
            btn.textContent = 'Ir a pagar con Wompi';
            return;
        }

        const payload = {
            product_id: productId,
            purchase_type: document.getElementById('purchase_type').value,
            organization_name: document.getElementById('organization_name').value || null,
            return_url: '{{ url('/gracias') }}',
            customer: {
                email: document.getElementById('email').value.trim(),
                full_name: document.getElementById('full_name').value.trim(),
                phone_number: document.getElementById('phone_number').value.trim() || null,
                type_id: typeId.value,
                number_id: document.getElementById('number_id').value.trim() || null,
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
                throw new Error(data.message || data.detail || 'No se pudo iniciar el pago');
            }
            const url = paymentUrl(data);
            if (!url) throw new Error('Respuesta de pago sin URL de checkout');
            window.location.href = url;
        } catch (err) {
            errorEl.textContent = err.message || 'Error de conexión. Intenta de nuevo.';
            errorEl.style.display = 'block';
            btn.disabled = false;
            btn.textContent = 'Ir a pagar con Wompi';
        }
    });
})();
</script>
@endpush
