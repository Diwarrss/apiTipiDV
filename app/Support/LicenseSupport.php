<?php

declare(strict_types=1);

namespace App\Support;

final class LicenseSupport
{
    public const SERVICE_TYPE = 'TIPIDV';

    public const GRIDPAY_SLUG = 'tipidv';

    public const PERIOD_MONTHLY = 'monthly';

    public const PERIOD_ANNUAL = 'annual';

    public static function isTipidvProduct(array $product): bool
    {
        return ($product['detail']['service_type'] ?? null) === self::SERVICE_TYPE;
    }

    public static function billingMonthsFromProduct(array $product): int
    {
        $detail = $product['detail'] ?? [];
        $period = $detail['billing_period'] ?? $detail['payment_type'] ?? self::PERIOD_ANNUAL;

        if ($period === self::PERIOD_MONTHLY || $period === 'MENSUAL' || $period === 'month') {
            return 1;
        }

        return max(1, (int) ($detail['billing_months'] ?? 12));
    }

    public static function machineSlotsFromProduct(array $product): int
    {
        $detail = $product['detail'] ?? [];

        return max(1, (int) ($detail['machine_slots'] ?? 1));
    }

    public static function generateLicenseKey(): string
    {
        $a = strtoupper(bin2hex(random_bytes(2)));
        $b = strtoupper(bin2hex(random_bytes(2)));
        $c = strtoupper(bin2hex(random_bytes(2)));

        return "TDV-{$a}-{$b}-{$c}";
    }
}
