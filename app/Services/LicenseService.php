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
    public function handleApprovedPayment(array $requestData): ?Subscription
    {
        $transaction = $requestData['transaction'] ?? null;
        if (! is_array($transaction) || ! isset($transaction['product'])) {
            return null;
        }

        $product = $transaction['product'];
        if (! LicenseSupport::isTipidvProduct($product)) {
            return null;
        }

        $transactionUuid = isset($requestData['uuid_transaction'])
            ? (string) $requestData['uuid_transaction']
            : null;

        if ($transactionUuid) {
            $existing = Subscription::query()
                ->where('transaction_uuid', $transactionUuid)
                ->first();
            if ($existing) {
                return $existing;
            }
        }

        $wompiTransaction = $requestData['wompi_event']['data']['transaction'] ?? null;
        $customerData = $transaction['customer'] ?? [];
        $email = strtolower(trim((string) (
            $customerData['email']
            ?? ($wompiTransaction['customer_email'] ?? '')
        )));

        if ($email === '') {
            Log::error('TipiDV webhook: sin email de cliente', ['transaction_uuid' => $transactionUuid]);

            return null;
        }

        $amount = 0.0;
        if (isset($wompiTransaction['amount_in_cents']) && $wompiTransaction['amount_in_cents'] > 0) {
            $amount = $wompiTransaction['amount_in_cents'] / 100;
        } else {
            $amount = (float) ($product['value'] ?? 0);
        }

        $reference = $wompiTransaction['id']
            ?? $transaction['reference_transaction']
            ?? $transactionUuid;

        $months = LicenseSupport::billingMonthsFromProduct($product);
        $detail = $product['detail'] ?? [];
        $period = $detail['billing_period'] ?? ($months === 1
            ? LicenseSupport::PERIOD_MONTHLY
            : LicenseSupport::PERIOD_ANNUAL);

        $startsAt = GridPayTimestamp::parse(
            $transaction['created_at'] ?? ($wompiTransaction['created_at'] ?? null)
        );

        $machineSlots = LicenseSupport::machineSlotsFromProduct($product);

        $renewal = Subscription::query()
            ->where('customer_email', $email)
            ->orderByDesc('expires_at')
            ->first();

        if ($renewal) {
            $base = $renewal->expires_at->isFuture() ? $renewal->expires_at : now();
            $renewal->fill([
                'customer_name' => $customerData['full_name'] ?? $renewal->customer_name,
                'billing_period' => $period,
                'machine_slots' => max($renewal->machine_slots, $machineSlots),
                'expires_at' => $base->copy()->addMonths($months),
                'status' => Subscription::STATUS_ACTIVE,
                'wompi_reference' => $reference !== null ? (string) $reference : null,
                'transaction_uuid' => $transactionUuid,
                'gridpay_product_uuid' => $product['uuid'] ?? null,
                'amount_cop' => $amount,
                'metadata' => array_merge($renewal->metadata ?? [], [
                    'product_name' => $product['name'] ?? null,
                    'wompi_event' => $requestData['event'] ?? null,
                    'renewed_at' => now()->toIso8601String(),
                ]),
            ]);
            $renewal->save();
            $subscription = $renewal;
        } else {
            $subscription = Subscription::query()->create([
                'license_key' => LicenseSupport::generateLicenseKey(),
                'customer_email' => $email,
                'customer_name' => $customerData['full_name'] ?? null,
                'organization_name' => $detail['organization_name'] ?? null,
                'billing_period' => $period,
                'machine_slots' => $machineSlots,
                'starts_at' => $startsAt,
                'expires_at' => $startsAt->copy()->addMonths($months),
                'status' => Subscription::STATUS_ACTIVE,
                'wompi_reference' => $reference !== null ? (string) $reference : null,
                'transaction_uuid' => $transactionUuid,
                'gridpay_product_uuid' => $product['uuid'] ?? null,
                'amount_cop' => $amount,
                'metadata' => [
                    'product_name' => $product['name'] ?? null,
                    'wompi_event' => $requestData['event'] ?? null,
                ],
            ]);
        }

        $this->sendLicenseEmail($subscription);

        return $subscription;
    }

    /** @return array{ok: bool, message: string, subscription?: Subscription, activation?: MachineActivation} */
    public function activate(string $licenseKey, string $email, string $machineFingerprint, ?string $machineLabel): array
    {
        $licenseKey = strtoupper(trim($licenseKey));
        $email = strtolower(trim($email));
        $machineFingerprint = strtolower(trim($machineFingerprint));

        if ($licenseKey === '' || $email === '' || strlen($machineFingerprint) < 16) {
            return ['ok' => false, 'message' => 'Datos de activación incompletos.'];
        }

        $subscription = Subscription::query()->where('license_key', $licenseKey)->first();
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

            if ($subscription->activeActivations()->count() >= $subscription->machine_slots) {
                return [
                    'ok' => false,
                    'message' => 'Esta licencia ya alcanzó el máximo de equipos permitidos.',
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

    /** @return array{ok: bool, message: string, expires_at?: string, days_remaining?: int, offline_grace_days?: int} */
    public function validate(string $licenseKey, string $machineFingerprint): array
    {
        $licenseKey = strtoupper(trim($licenseKey));
        $machineFingerprint = strtolower(trim($machineFingerprint));

        $subscription = Subscription::query()->where('license_key', $licenseKey)->first();
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

        return [
            'ok' => true,
            'message' => 'Licencia vigente.',
            'expires_at' => $subscription->expires_at->toIso8601String(),
            'days_remaining' => max(0, (int) now()->diffInDays($subscription->expires_at, false)),
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
