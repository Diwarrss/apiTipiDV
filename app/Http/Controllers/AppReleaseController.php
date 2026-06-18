<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\WindowsDownloadService;
use Illuminate\Http\JsonResponse;

/** Versión publicada de TipiDV Windows (consultada por la app de escritorio). */
final class AppReleaseController extends Controller
{
    public function show(WindowsDownloadService $downloads): JsonResponse
    {
        $release = $downloads->release();
        if ($release === null || empty($release['setup_url'])) {
            return response()->json(['message' => 'No hay versión publicada'], 404);
        }

        return response()->json([
            'version' => $release['version'] ?? $release['tag'] ?? null,
            'tag' => $release['tag'] ?? null,
            'setup_url' => $release['setup_url'],
            'portable_url' => $release['portable_url'] ?? null,
            'published_at' => $release['published_at'] ?? null,
            'portal_url' => rtrim((string) config('licensing.portal_url', ''), '/'),
        ]);
    }
}
