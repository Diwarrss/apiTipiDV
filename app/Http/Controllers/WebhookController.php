<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\LicenseService;
use App\Services\WompiCheckoutService;
use App\Services\WindowsDownloadService;
use App\Support\WompiWebhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class WebhookController extends Controller
{
    public function __construct(
        private readonly LicenseService $licenseService,
        private readonly WompiCheckoutService $wompiCheckout,
    ) {
    }

    /** Eventos Wompi → URL configurada en el dashboard del comercio. */
    public function wompi(Request $request): JsonResponse
    {
        $payload = $request->all();
        $eventsSecret = (string) config('licensing.wompi.events_secret', '');

        if ($eventsSecret !== '' && ! WompiWebhook::verify($payload, $eventsSecret)) {
            Log::warning('TipiDV Wompi webhook: firma inválida');

            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $event = (string) ($payload['event'] ?? '');
        $transaction = $payload['data']['transaction'] ?? null;
        if ($event !== 'transaction.updated' || ! is_array($transaction)) {
            return response()->json(['message' => 'Event ignored'], 200);
        }

        if (($transaction['status'] ?? '') !== 'APPROVED') {
            return response()->json(['message' => 'Transaction not approved'], 200);
        }

        $pending = $this->wompiCheckout->resolvePending(
            isset($transaction['payment_link_id']) ? (string) $transaction['payment_link_id'] : null,
            isset($transaction['reference']) ? (string) $transaction['reference'] : null,
        );

        if ($pending === null) {
            Log::error('TipiDV Wompi: checkout pendiente no encontrado', [
                'payment_link_id' => $transaction['payment_link_id'] ?? null,
                'reference' => $transaction['reference'] ?? null,
            ]);

            return response()->json(['message' => 'Pending checkout not found'], 422);
        }

        try {
            $subscription = $this->licenseService->handleWompiApproved($transaction, $pending);

            if ($subscription === null) {
                return response()->json(['message' => 'Could not provision license'], 422);
            }

            return response()->json([
                'message' => 'TipiDV license processed',
                'license_key' => $subscription->license_key,
            ]);
        } catch (\Throwable $e) {
            Log::error('TipiDV Wompi webhook error', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Webhook processing failed'], 500);
        }
    }

    public function githubRelease(Request $request, WindowsDownloadService $downloads): JsonResponse
    {
        $secret = (string) config('marketing.github_release_webhook_secret', '');
        if ($secret === '' || $request->bearerToken() !== $secret) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'setup_url' => 'required|url|max:500',
            'portable_url' => 'nullable|url|max:500',
            'tag' => 'nullable|string|max:64',
            'published_at' => 'nullable|string|max:64',
        ]);

        try {
            $downloads->persistFromWebhook($validated);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Release URLs saved',
            'setup_url' => $validated['setup_url'],
        ]);
    }
}
