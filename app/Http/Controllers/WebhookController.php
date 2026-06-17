<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\LicenseService;
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
}
