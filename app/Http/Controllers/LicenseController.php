<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\LicensePricingService;
use App\Services\LicenseService;
use App\Services\PlanCatalogService;
use App\Services\WompiCheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class LicenseController extends Controller
{
    public function __construct(
        private readonly LicenseService $licenseService,
        private readonly PlanCatalogService $planCatalog,
        private readonly WompiCheckoutService $wompiCheckout,
        private readonly LicensePricingService $pricing,
    ) {
    }

    public function plans(): JsonResponse
    {
        return response()->json([
            'plans' => $this->planCatalog->plans(),
            'max_quantity' => $this->pricing->maxQuantity(),
            'volume_discounts' => $this->pricing->volumeDiscounts(),
            'portal_url' => config('licensing.portal_url'),
            'offline_grace_days' => (int) config('licensing.offline_grace_days', 14),
        ]);
    }

    public function checkout(Request $request): JsonResponse
    {
        $maxQty = $this->pricing->maxQuantity();

        $validated = $request->validate([
            'product_id' => 'required|string|max:64',
            'quantity' => 'nullable|integer|min:1|max:'.$maxQty,
            'customer' => 'required|array',
            'customer.email' => 'required|email|max:255',
            'customer.full_name' => 'required|string|min:3|max:255',
            'customer.phone_number' => ['nullable', 'string', 'max:32', 'regex:/^[\d\s+\-()]{7,20}$/'],
            'customer.type_id' => 'nullable|string|in:CC,NIT,CE',
            'customer.number_id' => ['nullable', 'string', 'max:32', 'regex:/^[\d.\-]{5,32}$/'],
            'purchase_type' => 'nullable|string|in:new_license,renewal,new_equipment',
            'organization_name' => 'nullable|string|max:255',
            'return_url' => 'nullable|url|max:500',
        ], [
            'customer.full_name.min' => 'El nombre debe tener al menos 3 caracteres.',
            'customer.full_name.required' => 'El nombre completo es obligatorio.',
            'customer.email.required' => 'El correo electrónico es obligatorio.',
            'customer.email.email' => 'Ingresa un correo electrónico válido.',
            'customer.phone_number.regex' => 'El teléfono solo puede contener números y símbolos + - ( ).',
            'customer.number_id.regex' => 'El número de documento no es válido.',
            'quantity.max' => "La cantidad máxima por compra es {$maxQty} equipos.",
        ]);

        $typeId = strtoupper(trim((string) ($validated['customer']['type_id'] ?? 'CC')));
        $orgName = trim((string) ($validated['organization_name'] ?? ''));

        if ($typeId === 'NIT' && $orgName === '') {
            throw ValidationException::withMessages([
                'organization_name' => ['Indica el nombre del hospital o empresa para facturar con NIT.'],
            ]);
        }

        $plan = $this->findPlan($validated['product_id']);
        if ($plan === null) {
            return response()->json(['message' => 'Plan no encontrado'], 404);
        }

        $purchaseType = $this->pricing->normalizePurchaseType($validated['purchase_type'] ?? 'new_license');
        $quantity = (int) ($validated['quantity'] ?? 1);

        if ($purchaseType === 'renewal' && $quantity !== 1) {
            return response()->json([
                'message' => 'La renovación extiende la vigencia; no modifica la cantidad de equipos.',
            ], 422);
        }

        $returnUrl = $validated['return_url']
            ?? rtrim((string) config('licensing.portal_url'), '/').'/gracias';

        $customer = $validated['customer'];
        $customer['email'] = strtolower(trim($customer['email']));
        $customer['full_name'] = trim($customer['full_name']);
        if (! empty($customer['phone_number'])) {
            $customer['phone_number'] = preg_replace('/\s+/', ' ', trim($customer['phone_number'])) ?? '';
        }

        try {
            $link = $this->wompiCheckout->createPaymentLink(
                $plan,
                $customer,
                $returnUrl,
                $purchaseType,
                $quantity,
                $orgName !== '' ? $orgName : null,
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'No se pudo iniciar el pago',
                'detail' => $e->getMessage(),
            ], 502);
        }

        return response()->json([
            'payment_link_url' => $link['payment_link_url'],
            'checkout_url' => $link['payment_link_url'],
            'reference' => $link['reference'],
            'pricing' => $link['pricing'] ?? null,
        ]);
    }

    public function quote(Request $request): JsonResponse
    {
        $maxQty = $this->pricing->maxQuantity();

        $validated = $request->validate([
            'product_id' => 'required|string|max:64',
            'quantity' => 'nullable|integer|min:1|max:'.$maxQty,
            'purchase_type' => 'nullable|string|in:new_license,renewal,new_equipment',
        ]);

        $plan = $this->findPlan($validated['product_id']);
        if ($plan === null) {
            return response()->json(['message' => 'Plan no encontrado'], 404);
        }

        $purchaseType = $this->pricing->normalizePurchaseType($validated['purchase_type'] ?? 'new_license');
        $quantity = min($maxQty, max(1, (int) ($validated['quantity'] ?? 1)));

        return response()->json([
            'pricing' => $this->pricing->quote($plan, $quantity, $purchaseType),
            'plan' => [
                'period' => $plan['period'] ?? null,
                'name' => $plan['name'] ?? null,
            ],
        ]);
    }

    public function activate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'license_key' => 'required|string|max:32',
            'email' => 'required|email|max:255',
            'machine_fingerprint' => 'required|string|min:16|max:64',
            'machine_label' => 'nullable|string|max:120',
        ]);

        $result = $this->licenseService->activate(
            $validated['license_key'],
            $validated['email'],
            $validated['machine_fingerprint'],
            $validated['machine_label'] ?? null
        );

        $status = $result['ok'] ? 200 : 422;

        return response()->json([
            'ok' => $result['ok'],
            'message' => $result['message'],
            'expires_at' => $result['subscription']?->expires_at?->toIso8601String(),
            'license_key' => $result['subscription']?->license_key,
            'offline_grace_days' => (int) config('licensing.offline_grace_days', 14),
        ], $status);
    }

    public function validateLicense(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'license_key' => 'required|string|max:32',
            'machine_fingerprint' => 'required|string|min:16|max:64',
        ]);

        $result = $this->licenseService->validate(
            $validated['license_key'],
            $validated['machine_fingerprint']
        );

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    /** @return array<string, mixed>|null */
    private function findPlan(string $productId): ?array
    {
        $productId = strtolower(trim($productId));
        foreach ($this->planCatalog->plans() as $plan) {
            if (($plan['period'] ?? '') === $productId || ($plan['uuid'] ?? '') === $productId) {
                return $plan;
            }
        }

        return null;
    }
}
