<div class="form-card">
    @if($checkoutError)
        <div class="alert-error" role="alert">{{ $checkoutError }}</div>
    @endif

    <div class="checkout-wizard">
        <ol class="wizard-steps" aria-label="Pasos del checkout">
            @foreach([1 => 'Paquete', 2 => 'Datos', 3 => 'Pago'] as $n => $label)
                <li @class([
                    'wizard-step',
                    'active' => $step === $n,
                    'done' => $step > $n,
                ])>
                    <span class="wizard-step-dot">{{ $step > $n ? '✓' : $n }}</span>
                    <span class="wizard-step-label">{{ $label }}</span>
                </li>
            @endforeach
        </ol>

        <p class="wompi-trust-inline" aria-hidden="true">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" style="color:#6200ea"><path d="M7 11V8a5 5 0 0 1 10 0v3" stroke="currentColor" stroke-width="2"/><rect x="5" y="11" width="14" height="10" rx="2" stroke="currentColor" stroke-width="2"/></svg>
            Pago con <span class="wompi-logo">wompi</span> · PSE · Tarjeta · Nequi
        </p>

        <div class="wizard-summary-bar" wire:key="summary-{{ $planPeriod }}-{{ $quantity }}-{{ $purchaseType }}" aria-live="polite">
            <div class="wizard-summary-left">
                <div>{{ $this->selectedPlan['name'] ?? 'Plan' }}</div>
                <div class="wizard-summary-detail">{{ $this->summaryDetail }}</div>
                @if($this->quote['discount_cop'] > 0)
                    <div class="wizard-summary-discount">
                        Descuento {{ $this->quote['discount_percent'] }}%: −{{ $this->formatCop($this->quote['discount_cop']) }}
                    </div>
                @endif
            </div>
            <div class="wizard-summary-prices">
                @if($this->quote['discount_cop'] > 0)
                    <span class="wizard-summary-subtotal">{{ $this->formatCop($this->quote['subtotal_cop']) }}</span>
                @endif
                <strong>{{ $this->formatCop($this->quote['total_cop']) }} COP</strong>
            </div>
        </div>

        {{-- Paso 1 --}}
        @if($step === 1)
            <div class="wizard-panel is-active">
                <h3 class="wizard-panel-title">Arma tu paquete</h3>

                <div class="field">
                    <span class="field-label">Plan</span>
                    <div class="plan-picker" role="tablist" aria-label="Plan">
                        @foreach($plans as $plan)
                            @php
                                $isAnnual = ($plan['period'] ?? '') === 'annual' || (int) ($plan['billing_months'] ?? 0) >= 12;
                                $isActive = ($plan['period'] ?? '') === $planPeriod;
                            @endphp
                            <button
                                type="button"
                                wire:click="selectPlan('{{ $plan['period'] }}')"
                                @class(['plan-option', 'tab', 'active' => $isActive])
                            >
                                <span class="plan-option-name">{{ $plan['name'] ?? $plan['period'] }}</span>
                                <span class="plan-option-price">{{ $this->formatCop((float) ($plan['value_cop'] ?? 0)) }}/equipo/{{ $isAnnual ? 'año' : 'mes' }}</span>
                                @if($isAnnual)
                                    <span class="plan-option-badge">Recomendado</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>

                @if($purchaseType !== 'renewal')
                    <div class="field">
                        <label for="quantity">
                            {{ $purchaseType === 'new_equipment' ? 'Equipos adicionales' : 'Equipos (PCs)' }}
                        </label>
                        <div class="qty-stepper">
                            <button type="button" class="qty-btn" wire:click="decrementQuantity" aria-label="Menos">−</button>
                            <input
                                type="number"
                                id="quantity"
                                wire:model.live="quantity"
                                min="1"
                                max="{{ $maxQuantity }}"
                                required
                                inputmode="numeric"
                                aria-describedby="quantity-hint quantity-error"
                            >
                            <button type="button" class="qty-btn" wire:click="incrementQuantity" aria-label="Más">+</button>
                        </div>
                        <small id="quantity-hint">1 clave · máx. {{ $maxQuantity }} · descuentos auto.</small>
                        @error('quantity')
                            <span class="field-error" id="quantity-error" role="alert">{{ $message }}</span>
                        @enderror
                    </div>
                @endif

                @if(count($volumeDiscounts) > 0)
                    <div class="discount-badges">
                        @foreach(array_reverse($volumeDiscounts) as $tier)
                            <span class="discount-badge">{{ $tier['label'] ?? '' }}</span>
                        @endforeach
                    </div>
                @endif

                <details class="form-optional">
                    <summary>¿Ya tienes licencia?</summary>
                    <div class="form-optional-body">
                        <div class="choice-group" role="radiogroup" aria-label="Tipo de compra">
                            <label class="choice-card">
                                <input type="radio" wire:model.live="purchaseType" value="new_license">
                                <span class="choice-card-body">
                                    <strong>Primera compra</strong>
                                    <small>Nuevo paquete</small>
                                </span>
                            </label>
                            <label class="choice-card">
                                <input type="radio" wire:model.live="purchaseType" value="renewal">
                                <span class="choice-card-body">
                                    <strong>Renovar</strong>
                                    <small>Mismo # equipos</small>
                                </span>
                            </label>
                            <label class="choice-card">
                                <input type="radio" wire:model.live="purchaseType" value="new_equipment">
                                <span class="choice-card-body">
                                    <strong>Agregar PCs</strong>
                                    <small>A tu clave actual</small>
                                </span>
                            </label>
                        </div>
                    </div>
                </details>
            </div>
        @endif

        {{-- Paso 2 --}}
        @if($step === 2)
            <div class="wizard-panel is-active">
                <h3 class="wizard-panel-title">Tus datos</h3>
                <p class="wizard-panel-desc">Para enviarte la clave y el comprobante.</p>

                <div class="field">
                    <label for="full_name">Nombre <span class="req">*</span></label>
                    <input
                        type="text"
                        id="full_name"
                        wire:model.blur="fullName"
                        autocomplete="name"
                        maxlength="255"
                        placeholder="María González"
                        @class(['input-invalid' => $errors->has('fullName')])
                        aria-describedby="full_name-error"
                    >
                    @error('fullName')
                        <span class="field-error" id="full_name-error" role="alert">{{ $message }}</span>
                    @enderror
                </div>

                <div class="field">
                    <label for="email">Correo <span class="req">*</span></label>
                    <input
                        type="email"
                        id="email"
                        wire:model.blur="email"
                        autocomplete="email"
                        maxlength="255"
                        placeholder="correo@hospital.gov.co"
                        @class(['input-invalid' => $errors->has('email')])
                        aria-describedby="email-error"
                    >
                    <small id="email-hint">Recibirás la clave TDV aquí.</small>
                    @error('email')
                        <span class="field-error" id="email-error" role="alert">{{ $message }}</span>
                    @enderror
                </div>

                <div class="field">
                    <label for="phone_number">Teléfono</label>
                    <input
                        type="tel"
                        id="phone_number"
                        wire:model.blur="phoneNumber"
                        placeholder="300 123 4567"
                        maxlength="32"
                        autocomplete="tel"
                        inputmode="tel"
                        @class(['input-invalid' => $errors->has('phoneNumber')])
                    >
                    @error('phoneNumber')
                        <span class="field-error" role="alert">{{ $message }}</span>
                    @enderror
                </div>

                <details class="form-optional">
                    <summary>Facturación (opcional)</summary>
                    <div class="form-optional-body">
                        <div class="form-grid">
                            <div class="field">
                                <label for="type_id">Tipo doc.</label>
                                <select id="type_id" wire:model.live="typeId">
                                    <option value="CC">CC</option>
                                    <option value="NIT">NIT</option>
                                    <option value="CE">CE</option>
                                </select>
                            </div>
                            <div class="field">
                                <label for="number_id">Número</label>
                                <input
                                    type="text"
                                    id="number_id"
                                    wire:model.blur="numberId"
                                    maxlength="32"
                                    placeholder="Sin puntos"
                                    inputmode="numeric"
                                    @class(['input-invalid' => $errors->has('numberId')])
                                >
                                @error('numberId')
                                    <span class="field-error" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        @if($typeId === 'NIT')
                            <div class="field">
                                <label for="organization_name">Hospital / empresa <span class="req">*</span></label>
                                <input
                                    type="text"
                                    id="organization_name"
                                    wire:model.blur="organizationName"
                                    maxlength="255"
                                    placeholder="Hospital Regional"
                                    @class(['input-invalid' => $errors->has('organizationName')])
                                >
                                @error('organizationName')
                                    <span class="field-error" role="alert">{{ $message }}</span>
                                @enderror
                            </div>
                        @endif
                    </div>
                </details>
            </div>
        @endif

        {{-- Paso 3 --}}
        @if($step === 3)
            <div class="wizard-panel is-active">
                <h3 class="wizard-panel-title">Confirmar y pagar</h3>

                <div class="wizard-review">
                    <div class="wizard-review-row">
                        <span>Plan</span>
                        <span>{{ $this->selectedPlan['name'] ?? 'Plan' }}</span>
                    </div>
                    <div class="wizard-review-row">
                        <span>Tipo</span>
                        <span>{{ $this->purchaseLabel }}</span>
                    </div>
                    <div class="wizard-review-row">
                        <span>Equipos</span>
                        <span>{{ $purchaseType === 'renewal' ? 'Renovación' : $this->quote['quantity'] }}</span>
                    </div>
                    <div class="wizard-review-row">
                        <span>Contacto</span>
                        <span>{{ $email }}</span>
                    </div>
                    @if($this->quote['discount_cop'] > 0)
                        <div class="wizard-review-row">
                            <span>Subtotal</span>
                            <span>{{ $this->formatCop($this->quote['subtotal_cop']) }}</span>
                        </div>
                        <div class="wizard-review-row">
                            <span>Descuento</span>
                            <span>{{ $this->quote['discount_percent'] }}% (−{{ $this->formatCop($this->quote['discount_cop']) }})</span>
                        </div>
                    @endif
                    <div class="wizard-review-row">
                        <span>Total</span>
                        <span>{{ $this->formatCop($this->quote['total_cop']) }} COP</span>
                    </div>
                </div>

                @include('site.partials.wompi-trust')

                <button
                    type="button"
                    class="btn btn-primary"
                    wire:click="submitCheckout"
                    wire:loading.attr="disabled"
                    wire:target="submitCheckout"
                    style="width:100%;margin-top:12px"
                >
                    <span wire:loading.remove wire:target="submitCheckout">Ir a pagar con Wompi</span>
                    <span wire:loading wire:target="submitCheckout">Preparando pago…</span>
                </button>
                <p class="checkout-footnote">
                    Serás redirigido al checkout de Wompi · <a href="{{ url('/') }}">Inicio</a>
                </p>
            </div>
        @endif

        <div class="wizard-nav">
            @if($step > 1)
                <button type="button" class="btn btn-ghost" wire:click="previousStep">Atrás</button>
            @endif
            @if($step < 3)
                <button type="button" class="btn btn-primary" wire:click="nextStep" wire:loading.attr="disabled" wire:target="nextStep,submitCheckout">
                    {{ $step === 2 ? 'Revisar pago' : 'Continuar' }}
                </button>
            @endif
        </div>
    </div>
</div>
