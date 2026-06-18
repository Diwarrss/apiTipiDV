<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\LicenseSupport;

final class PlanCatalogService
{
    /** @return array<int, array{period: string, uuid: string, name: string, value_cop: float, billing_months: int, machine_slots: int, featured?: bool}> */
    public function plans(): array
    {
        $fallback = config('marketing.fallback_prices', []);

        $plans = [
            [
                'period' => LicenseSupport::PERIOD_MONTHLY,
                'uuid' => LicenseSupport::PERIOD_MONTHLY,
                'name' => 'TipiDV Mensual',
                'value_cop' => (float) ($fallback['monthly_cop'] ?? 29_000),
                'billing_months' => 1,
                'machine_slots' => 1,
            ],
            [
                'period' => LicenseSupport::PERIOD_ANNUAL,
                'uuid' => LicenseSupport::PERIOD_ANNUAL,
                'name' => 'TipiDV Anual',
                'value_cop' => (float) ($fallback['annual_cop'] ?? 198_000),
                'billing_months' => 12,
                'machine_slots' => 1,
                'featured' => true,
            ],
        ];

        usort($plans, fn (array $a, array $b) => ($a['billing_months'] ?? 0) <=> ($b['billing_months'] ?? 0));

        return $plans;
    }
}
