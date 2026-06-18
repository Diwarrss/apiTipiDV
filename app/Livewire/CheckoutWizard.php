<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\LicensePricingService;
use App\Services\PlanCatalogService;
use App\Services\WompiCheckoutService;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class CheckoutWizard extends Component
{
    public int $step = 1;

    public string $planPeriod = 'annual';

    public int $quantity = 1;

    public string $purchaseType = 'new_license';

    public string $fullName = '';

    public string $email = '';

    public string $phoneNumber = '';

    public string $typeId = 'CC';

    public string $numberId = '';

    public string $organizationName = '';

    public ?string $checkoutError = null;

    public bool $processing = false;

    /** @var array<int, array<string, mixed>> */
    public array $plans = [];

    public int $maxQuantity = 50;

    /** @var array<int, array<string, mixed>> */
    public array $volumeDiscounts = [];

    public function mount(
        PlanCatalogService $planCatalog,
        LicensePricingService $pricing,
    ): void {
        $this->plans = $planCatalog->plans();
        $this->maxQuantity = $pricing->maxQuantity();
        $this->volumeDiscounts = $pricing->volumeDiscounts();

        $requestedPlan = (string) request('plan', 'annual');
        if ($this->findPlan($requestedPlan) !== null) {
            $this->planPeriod = $requestedPlan;
        }

        $this->quantity = max(1, min($this->maxQuantity, (int) request('quantity', 1)));
    }

    #[Computed]
    public function selectedPlan(): ?array
    {
        return $this->findPlan($this->planPeriod);
    }

    #[Computed]
    public function quote(): array
    {
        $plan = $this->selectedPlan;
        if ($plan === null) {
            return [
                'quantity' => 1,
                'unit_price_cop' => 0.0,
                'subtotal_cop' => 0.0,
                'discount_percent' => 0,
                'discount_cop' => 0.0,
                'total_cop' => 0.0,
                'purchase_type' => $this->purchaseType,
            ];
        }

        return app(LicensePricingService::class)->quote(
            $plan,
            $this->quantity,
            $this->purchaseType,
        );
    }

    #[Computed]
    public function purchaseLabel(): string
    {
        return match ($this->purchaseType) {
            'renewal' => 'Renovación',
            'new_equipment' => 'Equipos adicionales',
            default => 'Primera compra',
        };
    }

    #[Computed]
    public function summaryDetail(): string
    {
        if ($this->purchaseType === 'renewal') {
            return 'Renovación · 1 licencia';
        }

        $qty = (int) $this->quote['quantity'];

        return "{$qty} equipo(s) · {$this->purchaseLabel}";
    }

    public function selectPlan(string $period): void
    {
        if ($this->findPlan($period) === null) {
            return;
        }

        $this->planPeriod = $period;
        $this->checkoutError = null;
        unset($this->quote, $this->selectedPlan);
    }

    public function decrementQuantity(): void
    {
        $this->quantity = max(1, $this->quantity - 1);
        $this->checkoutError = null;
        unset($this->quote);
    }

    public function incrementQuantity(): void
    {
        $this->quantity = min($this->maxQuantity, $this->quantity + 1);
        $this->checkoutError = null;
        unset($this->quote);
    }

    public function updatedQuantity(): void
    {
        $this->quantity = max(1, min($this->maxQuantity, $this->quantity));
        unset($this->quote);
    }

    public function updatedPurchaseType(): void
    {
        if ($this->purchaseType === 'renewal') {
            $this->quantity = 1;
        }
        unset($this->quote);
    }

    public function nextStep(): void
    {
        $this->checkoutError = null;

        if ($this->step === 1) {
            $this->validateStepOne();

            if ($this->selectedPlan === null) {
                $this->checkoutError = 'Selecciona un plan válido.';

                return;
            }
        }

        if ($this->step === 2) {
            $this->validateStepTwo();
        }

        if ($this->step < 3) {
            $this->step++;
        }
    }

    public function previousStep(): void
    {
        $this->checkoutError = null;
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function submitCheckout(WompiCheckoutService $wompi): void
    {
        $this->checkoutError = null;

        try {
            $this->validateStepOne();
            $this->validateStepTwo();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $failed = array_keys($e->validator->errors()->messages());
            $this->step = in_array('quantity', $failed, true) ? 1 : 2;
            throw $e;
        }

        $plan = $this->selectedPlan;
        if ($plan === null) {
            $this->checkoutError = 'Plan no disponible. Escríbenos por WhatsApp.';
            $this->step = 1;

            return;
        }

        $this->processing = true;

        try {
            $customer = [
                'email' => strtolower(trim($this->email)),
                'full_name' => trim($this->fullName),
                'phone_number' => trim($this->phoneNumber) !== ''
                    ? preg_replace('/\s+/', ' ', trim($this->phoneNumber))
                    : null,
                'type_id' => $this->typeId,
                'number_id' => trim($this->numberId) !== '' ? trim($this->numberId) : null,
            ];

            $qty = $this->purchaseType === 'renewal' ? 1 : $this->quantity;
            $orgName = trim($this->organizationName);

            $link = $wompi->createPaymentLink(
                $plan,
                $customer,
                url('/gracias'),
                $this->purchaseType,
                $qty,
                $orgName !== '' ? $orgName : null,
            );

            $paymentUrl = $link['payment_link_url'] ?? null;
            if (! is_string($paymentUrl) || $paymentUrl === '') {
                throw new \RuntimeException('Respuesta de pago sin URL de checkout');
            }

            $this->redirect($paymentUrl, navigate: false);
        } catch (\Throwable $e) {
            $this->processing = false;
            $this->checkoutError = $e->getMessage() ?: 'No se pudo iniciar el pago. Intenta de nuevo.';
        }
    }

    public function formatCop(float|int $amount): string
    {
        return '$'.number_format((float) $amount, 0, ',', '.');
    }

    public function render()
    {
        return view('livewire.checkout-wizard');
    }

    private function validateStepOne(): void
    {
        if ($this->purchaseType === 'renewal') {
            return;
        }

        $this->validate([
            'quantity' => 'required|integer|min:1|max:'.$this->maxQuantity,
        ], [
            'quantity.max' => "La cantidad máxima por compra es {$this->maxQuantity} equipos.",
        ]);
    }

    private function validateStepTwo(): void
    {
        $rules = [
            'fullName' => 'required|string|min:3|max:255',
            'email' => 'required|email|max:255',
            'phoneNumber' => ['nullable', 'string', 'max:32', 'regex:/^[\d\s+\-()]{7,20}$/'],
            'numberId' => ['nullable', 'string', 'max:32', 'regex:/^[\d.\-]{5,32}$/'],
            'typeId' => 'nullable|string|in:CC,NIT,CE',
        ];

        if ($this->typeId === 'NIT') {
            $rules['organizationName'] = 'required|string|min:2|max:255';
        }

        $this->validate($rules, [
            'fullName.required' => 'El nombre completo es obligatorio.',
            'fullName.min' => 'El nombre debe tener al menos 3 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'phoneNumber.regex' => 'El teléfono solo puede contener números y símbolos + - ( ).',
            'numberId.regex' => 'El número de documento no es válido.',
            'organizationName.required' => 'Indica el nombre del hospital o empresa para facturar con NIT.',
        ]);
    }

    /** @return array<string, mixed>|null */
    private function findPlan(string $period): ?array
    {
        foreach ($this->plans as $plan) {
            if (($plan['period'] ?? '') === $period) {
                return $plan;
            }
        }

        return null;
    }
}
