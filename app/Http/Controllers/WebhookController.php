<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\LicenseService;
use App\Services\WindowsDownloadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class WebhookController extends Controller
{
    public function __construct(private readonly LicenseService $licenseService)
    {
    }

    public function gridPay(Request $request): JsonResponse
    {
        $requestData = $request->all();

        if (($requestData['event'] ?? null) !== 'APPROVED') {
            return response()->json(['message' => 'Event ignored'], 200);
        }

        try {
            $subscription = $this->licenseService->handleApprovedPayment($requestData);

            if ($subscription === null) {
                return response()->json(['message' => 'Payment not applicable or could not be provisioned'], 422);
            }

            return response()->json([
                'message' => 'TipiDV license processed',
                'license_key' => $subscription->license_key,
            ]);
        } catch (\Throwable $e) {
            Log::error('TipiDV webhook error', [
                'error' => $e->getMessage(),
                'uuid_transaction' => $requestData['uuid_transaction'] ?? null,
            ]);

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
