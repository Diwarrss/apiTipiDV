<?php

declare(strict_types=1);

namespace App\Services;

final class LicensePricingService
{
    public function maxQuantity(): int
    {
        return max(1, (int) config('marketing.max_license_quantity', 50));
    }

    /** @return array<int, array{min_quantity: int, percent: int, label?: string}> */
    public function volumeDiscounts(): array
    {
        $tiers = config('marketing.volume_discounts', []);
        if (! is_array($tiers)) {
            return [];
        }

        $normalized = [];
        foreach ($tiers as $tier) {
            if (! is_array($tier)) {
                continue;
            }
            $min = (int) ($tier['min_quantity'] ?? 0);
            $percent = (int) ($tier['percent'] ?? 0);
            if ($min < 2 || $percent <= 0) {
                continue;
            }
            $normalized[] = [
                'min_quantity' => $min,
                'percent' => min(50, $percent),
                'label' => $tier['label'] ?? "-{$percent}% desde {$min} equipos",
            ];
        }

        usort($normalized, fn (array $a, array $b) => ($b['min_quantity'] ?? 0) <=> ($a['min_quantity'] ?? 0));

        return $normalized;
    }

    public function discountPercent(int $quantity): int
    {
        foreach ($this->volumeDiscounts() as $tier) {
            if ($quantity >= (int) ($tier['min_quantity'] ?? 0)) {
                return (int) ($tier['percent'] ?? 0);
            }
        }

        return 0;
    }

    /**
     * Calcula el total en servidor (nunca confiar en el precio del navegador).
     *
     * @param  array<string, mixed>  $plan
     * @return array{
     *     quantity: int,
     *     unit_price_cop: float,
     *     subtotal_cop: float,
     *     discount_percent: int,
     *     discount_cop: float,
     *     total_cop: float,
     *     purchase_type: string,
     * }
     */
    public function quote(array $plan, int $quantity, string $purchaseType = 'new_license'): array
    {
        $purchaseType = $this->normalizePurchaseType($purchaseType);
        $quantity = max(1, min($this->maxQuantity(), $quantity));

        if ($purchaseType === 'renewal') {
            $quantity = 1;
        }

        $unit = (float) ($plan['value_cop'] ?? 0);
        $subtotal = round($unit * $quantity, 0);
        $discountPercent = $purchaseType === 'renewal' ? 0 : $this->discountPercent($quantity);
        $discountCop = round($subtotal * ($discountPercent / 100), 0);
        $total = max(0.0, $subtotal - $discountCop);

        return [
            'quantity' => $quantity,
            'unit_price_cop' => $unit,
            'subtotal_cop' => $subtotal,
            'discount_percent' => $discountPercent,
            'discount_cop' => $discountCop,
            'total_cop' => $total,
            'purchase_type' => $purchaseType,
        ];
    }

    public function normalizePurchaseType(string $purchaseType): string
    {
        $allowed = ['new_license', 'renewal', 'new_equipment'];

        return in_array($purchaseType, $allowed, true) ? $purchaseType : 'new_license';
    }

    /**
     * Cupos finales de equipos según tipo de compra y suscripción existente.
     */
    public function resolveMachineSlots(string $purchaseType, int $quantity, int $existingSlots = 0): int
    {
        $quantity = max(1, $quantity);
        $existingSlots = max(0, $existingSlots);

        return match ($this->normalizePurchaseType($purchaseType)) {
            'renewal' => max(1, $existingSlots),
            'new_equipment' => max(1, $existingSlots + $quantity),
            default => max(1, $existingSlots > 0 ? max($existingSlots, $quantity) : $quantity),
        };
    }
}
