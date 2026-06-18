<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\WindowsDownloadService;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

final class DownloadController extends Controller
{
    public function setup(WindowsDownloadService $downloads): RedirectResponse|BinaryFileResponse|Response
    {
        if ($downloads->hasLocalSetup()) {
            return response()->download(
                $downloads->localSetupAbsolutePath(),
                (string) config('marketing.setup_asset_name', 'TipiDV-Setup.exe'),
                ['Cache-Control' => 'public, max-age=3600'],
            );
        }

        $url = $downloads->externalSetupUrl();
        if ($url !== null) {
            return redirect()->away($url);
        }

        return redirect()->to(url('/#descargar'))
            ->with('error', 'La descarga aún no está disponible. Escríbenos por WhatsApp.');
    }
}
