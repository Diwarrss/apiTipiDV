<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\LicenseService;
use App\Services\PlanCatalogService;
use App\Services\WompiCheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class LicenseController extends Controller
{
    public function __construct(
        private readonly LicenseService $licenseService,
        private readonly PlanCatalogService $planCatalog,
        private readonly WompiCheckoutService $wompiCheckout,
    ) {
    }

    public function plans(): JsonResponse
    {
        return response()->json([
            'plans' => $this->planCatalog->plans(),
            'portal_url' => config('licensing.portal_url'),
            'offline_grace_days' => (int) config('licensing.offline_grace_days', 14),
        ]);
    }

    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|string|max:64',
            'customer' => 'required|array',
            'customer.email' => 'required|email|max:255',
            'customer.full_name' => 'required|string|max:255',
            'customer.phone_number' => 'nullable|string|max:32',
            'customer.type_id' => 'nullable|string|max:8',
            'customer.number_id' => 'nullable|string|max:32',
            'purchase_type' => 'nullable|string|max:32',
            'organization_name' => 'nullable|string|max:255',
            'return_url' => 'nullable|url|max:500',
        ]);

        $plan = $this->findPlan($validated['product_id']);
        if ($plan === null) {
            return response()->json(['message' => 'Plan no encontrado'], 404);
        }

        $returnUrl = $validated['return_url']
            ?? rtrim((string) config('licensing.portal_url'), '/').'/gracias';

        try {
            $link = $this->wompiCheckout->createPaymentLink(
                $plan,
                $validated['customer'],
                $returnUrl,
                $validated['organization_name'] ?? null,
                $validated['purchase_type'] ?? null,
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
