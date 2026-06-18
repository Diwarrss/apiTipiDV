<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\LicenseIssuedMail;
use App\Models\MachineActivation;
use App\Models\Subscription;
use App\Support\GridPayTimestamp;
use App\Support\LicenseSupport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

final class LicenseService
{
    /** Pago aprobado vía webhook Wompi directo. */
    public function handleWompiApproved(array $wompiTransaction, array $pending): ?Subscription
    {
        $wompiId = (string) ($wompiTransaction['id'] ?? '');
        if ($wompiId !== '') {
            $existing = Subscription::query()
                ->where('wompi_reference', $wompiId)
                ->first();
            if ($existing) {
                return $existing;
            }
        }

        $customer = $pending['customer'] ?? [];
        $email = strtolower(trim((string) (
            $customer['email']
            ?? ($wompiTransaction['customer_email'] ?? '')
        )));

        if ($email === '') {
            Log::error('TipiDV Wompi: sin email', ['wompi_id' => $wompiId]);

            return null;
        }

        $amount = isset($wompiTransaction['amount_in_cents'])
            ? ((int) $wompiTransaction['amount_in_cents']) / 100
            : (float) ($pending['amount_cop'] ?? 0);

        $months = max(1, (int) ($pending['billing_months'] ?? 12));
        $period = (string) ($pending['period'] ?? LicenseSupport::PERIOD_ANNUAL);
        $machineSlots = max(1, (int) ($pending['machine_slots'] ?? 1));
        $startsAt = GridPayTimestamp::parse($wompiTransaction['created_at'] ?? null);

        $productPayload = [
            'name' => $pending['plan_name'] ?? 'TipiDV',
            'value' => $amount,
            'detail' => [
                'service_type' => LicenseSupport::SERVICE_TYPE,
                'billing_period' => $period,
                'billing_months' => $months,
                'machine_slots' => $machineSlots,
                'organization_name' => $pending['organization_name'] ?? null,
                'purchase_type' => $pending['purchase_type'] ?? null,
            ],
        ];

        return $this->provisionSubscription(
            email: $email,
            customerName: $customer['full_name'] ?? null,
            product: $productPayload,
            months: $months,
            period: $period,
            machineSlots: $machineSlots,
            amount: $amount,
            wompiReference: $wompiId !== '' ? $wompiId : null,
            transactionUuid: (string) ($pending['reference'] ?? null),
            wompiEvent: 'transaction.updated',
            startsAt: $startsAt,
        );
    }

    /**
     * @param array<string, mixed> $product
     */
    private function provisionSubscription(
        string $email,
        ?string $customerName,
        array $product,
        int $months,
        string $period,
        int $machineSlots,
        float $amount,
        ?string $wompiReference,
        ?string $transactionUuid,
        ?string $wompiEvent,
        ?\Carbon\CarbonInterface $startsAt = null,
    ): Subscription {
        $startsAt = $startsAt ?? GridPayTimestamp::parse(null);

        /** @var Subscription|null $renewal */
        $renewal = Subscription::query()
            ->where('customer_email', $email)
            ->orderByDesc('expires_at')
            ->first();

        if ($renewal) {
            $base = $renewal->expires_at->isFuture() ? $renewal->expires_at : now();
            $renewal->fill([
                'customer_name' => $customerName ?? $renewal->customer_name,
                'billing_period' => $period,
                'machine_slots' => max($renewal->machine_slots, $machineSlots),
                'expires_at' => $base->copy()->addMonths($months),
                'status' => Subscription::STATUS_ACTIVE,
                'wompi_reference' => $wompiReference,
                'transaction_uuid' => $transactionUuid,
                'amount_cop' => $amount,
                'metadata' => array_merge($renewal->metadata ?? [], [
                    'product_name' => $product['name'] ?? null,
                    'wompi_event' => $wompiEvent,
                    'renewed_at' => now()->toIso8601String(),
                ]),
            ]);
            $renewal->save();
            $subscription = $renewal;
        } else {
            $subscription = Subscription::query()->create([
                'license_key' => LicenseSupport::generateLicenseKey(),
                'customer_email' => $email,
                'customer_name' => $customerName,
                'organization_name' => $product['detail']['organization_name'] ?? null,
                'billing_period' => $period,
                'machine_slots' => $machineSlots,
                'starts_at' => $startsAt,
                'expires_at' => $startsAt->copy()->addMonths($months),
                'status' => Subscription::STATUS_ACTIVE,
                'wompi_reference' => $wompiReference,
                'transaction_uuid' => $transactionUuid,
                'amount_cop' => $amount,
                'metadata' => [
                    'product_name' => $product['name'] ?? null,
                    'wompi_event' => $wompiEvent,
                    'purchase_type' => $product['detail']['purchase_type'] ?? null,
                ],
            ]);
        }

        $this->sendLicenseEmail($subscription);

        Log::info('TipiDV licencia creada (Wompi)', [
            'license_key' => $subscription->license_key,
            'email' => $subscription->customer_email,
        ]);

        return $subscription;
    }

    /**
     * @return array{ok: bool, message: string, subscription?: Subscription, activation?: MachineActivation}
     */
    public function activate(string $licenseKey, string $email, string $machineFingerprint, ?string $machineLabel): array
    {
        $licenseKey = strtoupper(trim($licenseKey));
        $email = strtolower(trim($email));
        $machineFingerprint = strtolower(trim($machineFingerprint));

        if ($licenseKey === '' || $email === '' || strlen($machineFingerprint) < 16) {
            return ['ok' => false, 'message' => 'Datos de activación incompletos.'];
        }

        /** @var Subscription|null $subscription */
        $subscription = Subscription::query()
            ->where('license_key', $licenseKey)
            ->first();

        if (! $subscription) {
            return ['ok' => false, 'message' => 'Clave de licencia no válida.'];
        }

        if (strcasecmp($subscription->customer_email, $email) !== 0) {
            return ['ok' => false, 'message' => 'El correo no coincide con la licencia.'];
        }

        if (! $subscription->isActive()) {
            return ['ok' => false, 'message' => 'La suscripción está vencida o inactiva. Renueve en el portal TipiDV.'];
        }

        return DB::transaction(function () use ($subscription, $machineFingerprint, $machineLabel) {
            $existing = MachineActivation::query()
                ->where('subscription_id', $subscription->id)
                ->where('machine_fingerprint', $machineFingerprint)
                ->first();

            if ($existing && $existing->isActive()) {
                $existing->update(['last_seen_at' => now(), 'machine_label' => $machineLabel]);

                return [
                    'ok' => true,
                    'message' => 'Equipo ya activado.',
                    'subscription' => $subscription->fresh(),
                    'activation' => $existing->fresh(),
                ];
            }

            $activeCount = $subscription->activeActivations()->count();
            if ($activeCount >= $subscription->machine_slots) {
                return [
                    'ok' => false,
                    'message' => 'Esta licencia ya alcanzó el máximo de equipos permitidos. Libere un equipo o compre otro plan.',
                ];
            }

            $activation = MachineActivation::query()->create([
                'subscription_id' => $subscription->id,
                'machine_fingerprint' => $machineFingerprint,
                'machine_label' => $machineLabel,
                'activated_at' => now(),
                'last_seen_at' => now(),
            ]);

            return [
                'ok' => true,
                'message' => 'Equipo activado correctamente.',
                'subscription' => $subscription->fresh(),
                'activation' => $activation,
            ];
        });
    }

    /**
     * @return array{ok: bool, message: string, expires_at?: string, days_remaining?: int, offline_grace_days?: int}
     */
    public function validate(string $licenseKey, string $machineFingerprint): array
    {
        $licenseKey = strtoupper(trim($licenseKey));
        $machineFingerprint = strtolower(trim($machineFingerprint));

        $subscription = Subscription::query()
            ->where('license_key', $licenseKey)
            ->first();

        if (! $subscription) {
            return ['ok' => false, 'message' => 'Licencia no encontrada.'];
        }

        if (! $subscription->isActive()) {
            return ['ok' => false, 'message' => 'Suscripción vencida. Renueve en el portal TipiDV.'];
        }

        $activation = MachineActivation::query()
            ->where('subscription_id', $subscription->id)
            ->where('machine_fingerprint', $machineFingerprint)
            ->whereNull('deactivated_at')
            ->first();

        if (! $activation) {
            return ['ok' => false, 'message' => 'Este equipo no está activado para esta licencia.'];
        }

        $activation->update(['last_seen_at' => now()]);

        $daysRemaining = max(0, (int) now()->diffInDays($subscription->expires_at, false));

        return [
            'ok' => true,
            'message' => 'Licencia vigente.',
            'expires_at' => $subscription->expires_at->toIso8601String(),
            'days_remaining' => $daysRemaining,
            'offline_grace_days' => (int) config('licensing.offline_grace_days', 14),
            'billing_period' => $subscription->billing_period,
            'customer_email' => $subscription->customer_email,
        ];
    }

    private function sendLicenseEmail(Subscription $subscription): void
    {
        try {
            Mail::to($subscription->customer_email)->send(new LicenseIssuedMail($subscription));
        } catch (\Throwable $e) {
            Log::error('TipiDV: no se pudo enviar correo de licencia', [
                'email' => $subscription->customer_email,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
