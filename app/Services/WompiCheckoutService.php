<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

final class WompiCheckoutService
{
    private const PENDING_TTL_SECONDS = 604_800; // 7 días

    public function __construct(private readonly LicensePricingService $pricing)
    {
    }

    /** @param array<string, mixed> $plan */
    /** @param array<string, mixed> $customer */
    public function createPaymentLink(
        array $plan,
        array $customer,
        string $returnUrl,
        string $purchaseType,
        int $quantity,
        ?string $organizationName = null,
    ): array {
        $privateKey = (string) config('licensing.wompi.private_key', '');
        $apiUrl = (string) config('licensing.wompi.api_url', '');
        if ($privateKey === '' || $apiUrl === '') {
            throw new \RuntimeException('Wompi no configurado (WOMPI_PRIVATE_KEY).');
        }

        $purchaseType = $this->pricing->normalizePurchaseType($purchaseType);
        $quote = $this->pricing->quote($plan, $quantity, $purchaseType);
        $amountCop = (float) $quote['total_cop'];
        if ($amountCop <= 0) {
            throw new \InvalidArgumentException('Plan sin precio válido.');
        }

        $qty = (int) $quote['quantity'];
        $amountInCents = (int) round($amountCop * 100);
        $reference = 'TDV-'.strtoupper(Str::random(12));

        $planLabel = (string) ($plan['name'] ?? 'Licencia TipiDV');
        $description = $purchaseType === 'renewal'
            ? "{$planLabel} — renovación de vigencia"
            : ($qty === 1
                ? "{$planLabel} — 1 equipo"
                : "{$planLabel} — paquete {$qty} equipos (1 clave)");

        $body = [
            'name' => $planLabel,
            'description' => $description,
            'single_use' => true,
            'collect_shipping' => false,
            'amount_in_cents' => $amountInCents,
            'currency' => 'COP',
            'redirect_url' => $returnUrl,
            'reference' => $reference,
            'customer_data' => array_filter([
                'email' => $customer['email'] ?? null,
                'full_name' => $customer['full_name'] ?? null,
                'phone_number' => $customer['phone_number'] ?? null,
            ]),
        ];

        $response = Http::withToken($privateKey)
            ->acceptJson()
            ->timeout(15)
            ->post($apiUrl.'/payment_links', $body);

        if (! $response->successful()) {
            throw new \RuntimeException(
                'Wompi rechazó el link de pago: '.($response->json('error.message') ?? $response->body())
            );
        }

        $linkId = (string) ($response->json('data.id') ?? '');
        if ($linkId === '') {
            throw new \RuntimeException('Wompi no devolvió id del link de pago.');
        }

        $pending = [
            'reference' => $reference,
            'payment_link_id' => $linkId,
            'period' => (string) ($plan['period'] ?? 'annual'),
            'plan_name' => $planLabel,
            'amount_cop' => $amountCop,
            'billing_months' => (int) ($plan['billing_months'] ?? 12),
            'quantity' => $qty,
            'purchase_type' => $purchaseType,
            'pricing' => $quote,
            'customer' => $customer,
            'organization_name' => $organizationName,
        ];

        Cache::put($this->cacheKeyForLink($linkId), $pending, self::PENDING_TTL_SECONDS);
        Cache::put($this->cacheKeyForReference($reference), $pending, self::PENDING_TTL_SECONDS);

        return [
            'payment_link_url' => 'https://checkout.wompi.co/l/'.$linkId,
            'payment_link_id' => $linkId,
            'reference' => $reference,
            'pricing' => $quote,
        ];
    }

    /** @return array<string, mixed>|null */
    public function resolvePending(?string $paymentLinkId, ?string $reference): ?array
    {
        if ($paymentLinkId !== null && $paymentLinkId !== '') {
            $fromLink = Cache::get($this->cacheKeyForLink($paymentLinkId));
            if (is_array($fromLink)) {
                return $fromLink;
            }
        }

        if ($reference !== null && $reference !== '') {
            $fromRef = Cache::get($this->cacheKeyForReference($reference));
            if (is_array($fromRef)) {
                return $fromRef;
            }
        }

        return null;
    }

    private function cacheKeyForLink(string $linkId): string
    {
        return 'tipidv:wompi:plink:'.$linkId;
    }

    private function cacheKeyForReference(string $reference): string
    {
        return 'tipidv:wompi:ref:'.$reference;
    }
}
