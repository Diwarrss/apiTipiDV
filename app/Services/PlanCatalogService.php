<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\LicenseSupport;
use Illuminate\Support\Facades\Http;

final class PlanCatalogService
{
    /** @return array<int, array{period: string, uuid: string|null, name: string, value_cop: float, billing_months: int, machine_slots: int}> */
    public function plans(): array
    {
        $gridPayUrl = (string) config('licensing.gridpay.url');
        $gridPayKey = (string) config('licensing.gridpay.key');
        $plans = [];

        if ($gridPayUrl !== '' && $gridPayKey !== '') {
            foreach (array_filter(config('licensing.products', [])) as $period => $uuid) {
                if (! $uuid) {
                    continue;
                }

                $response = Http::withHeaders(['x-api-key' => $gridPayKey])
                    ->timeout(8)
                    ->get($gridPayUrl.'/products/'.$uuid);

                if (! $response->successful()) {
                    continue;
                }

                $product = $response->json();
                if (! is_array($product) || ! LicenseSupport::isTipidvProduct($product)) {
                    continue;
                }

                $plans[] = $this->mapProduct($period, $product, $uuid);
            }
        }

        if ($plans === []) {
            $plans = $this->fallbackPlans();
        }

        usort($plans, fn (array $a, array $b) => ($a['billing_months'] ?? 0) <=> ($b['billing_months'] ?? 0));

        return $plans;
    }

    /** @param array<string, mixed> $product */
    private function mapProduct(string $period, array $product, string $uuid): array
    {
        return [
            'period' => $period,
            'uuid' => $product['uuid'] ?? $uuid,
            'name' => (string) ($product['name'] ?? ucfirst($period)),
            'value_cop' => (float) ($product['value'] ?? 0),
            'billing_months' => LicenseSupport::billingMonthsFromProduct($product),
            'machine_slots' => LicenseSupport::machineSlotsFromProduct($product),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function fallbackPlans(): array
    {
        $fallback = config('marketing.fallback_prices', []);

        return [
            [
                'period' => 'monthly',
                'uuid' => config('licensing.products.monthly'),
                'name' => 'TipiDV Mensual',
                'value_cop' => (float) ($fallback['monthly_cop'] ?? 29_000),
                'billing_months' => 1,
                'machine_slots' => 1,
            ],
            [
                'period' => 'annual',
                'uuid' => config('licensing.products.annual'),
                'name' => 'TipiDV Anual',
                'value_cop' => (float) ($fallback['annual_cop'] ?? 198_000),
                'billing_months' => 12,
                'machine_slots' => 1,
                'featured' => true,
            ],
        ];
    }
}
