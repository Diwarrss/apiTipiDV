<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\LicenseService;
use App\Services\WindowsDownloadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WebhookController extends Controller
{
    public function __construct(private readonly LicenseService $licenseService)
    {
    }

    public function gridPay(Request $request): JsonResponse
    {
        $data = $request->all();

        if (($data['event'] ?? null) !== 'APPROVED') {
            return response()->json(['message' => 'Event ignored']);
        }

        $subscription = $this->licenseService->handleApprovedPayment($data);

        return response()->json([
            'message' => $subscription ? 'License processed' : 'Payment not provisioned',
            'license_key' => $subscription?->license_key,
        ], $subscription ? 200 : 422);
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
