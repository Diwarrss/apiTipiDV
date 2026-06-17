<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\LicenseService;
use App\Support\LicenseSupport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

final class LicenseController extends Controller
{
    public function __construct(private readonly LicenseService $licenseService)
    {
    }

    public function plans(): JsonResponse
    {
        $gridPayUrl = (string) config('licensing.gridpay.url');
        $gridPayKey = (string) config('licensing.gridpay.key');
        $uuids = array_filter(config('licensing.products', []));
        $plans = [];

        foreach ($uuids as $period => $uuid) {
            if (! $uuid) {
                continue;
            }

            $response = Http::withHeaders(['x-api-key' => $gridPayKey])
                ->get($gridPayUrl . '/products/' . $uuid);

            if (! $response->successful()) {
                continue;
            }

            $product = $response->json();
            if (! is_array($product) || ! LicenseSupport::isTipidvProduct($product)) {
                continue;
            }

            $plans[] = [
                'period' => $period,
                'uuid' => $product['uuid'] ?? $uuid,
                'name' => $product['name'] ?? null,
                'value_cop' => (float) ($product['value'] ?? 0),
                'billing_months' => LicenseSupport::billingMonthsFromProduct($product),
                'machine_slots' => LicenseSupport::machineSlotsFromProduct($product),
            ];
        }

        usort($plans, fn (array $a, array $b) => ($a['billing_months'] ?? 0) <=> ($b['billing_months'] ?? 0));

        return response()->json([
            'plans' => $plans,
            'portal_url' => config('licensing.portal_url'),
            'offline_grace_days' => (int) config('licensing.offline_grace_days', 14),
        ]);
    }

    public function checkout(Request $request): JsonResponse
    {
        $gridPayUrl = (string) config('licensing.gridpay.url');
        $gridPayKey = (string) config('licensing.gridpay.key');

        $validated = $request->validate([
            'product_id' => 'required|string|max:64',
            'customer' => 'required|array',
            'customer.email' => 'required|email|max:255',
            'customer.full_name' => 'required|string|max:255',
            'customer.phone_number' => 'nullable|string|max:32',
            'customer.type_id' => 'nullable|string|max:8',
            'customer.number_id' => 'nullable|string|max:32',
            'return_url' => 'nullable|url|max:500',
        ]);

        $productResponse = Http::withHeaders(['x-api-key' => $gridPayKey])
            ->get($gridPayUrl . '/products/' . basename($validated['product_id']));

        if (! $productResponse->successful()) {
            return response()->json(['message' => 'Plan no encontrado'], 404);
        }

        $product = $productResponse->json();
        if (! is_array($product) || ! LicenseSupport::isTipidvProduct($product)) {
            return response()->json(['message' => 'Producto no válido para TipiDV'], 422);
        }

        $returnUrl = $validated['return_url']
            ?? rtrim((string) config('licensing.portal_url'), '/') . '/gracias';

        $payload = [
            'return_url' => $returnUrl,
            'customer' => $validated['customer'],
            'product_id' => $product['uuid'] ?? $validated['product_id'],
            'sub_client_slug' => (string) config('licensing.gridpay_slug', LicenseSupport::GRIDPAY_SLUG),
            'webhook_url' => url('api/webhook/gridpay'),
        ];

        $response = Http::withHeaders(['x-api-key' => $gridPayKey])
            ->post($gridPayUrl . '/transactions', $payload);

        if (! $response->successful()) {
            return response()->json([
                'message' => 'No se pudo iniciar el pago',
                'detail' => $response->json('message') ?? $response->body(),
            ], $response->status() >= 400 ? $response->status() : 502);
        }

        return response()->json($response->json());
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
}
