<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\WindowsDownloadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

final class DownloadController extends Controller
{
    public function setup(WindowsDownloadService $downloads): RedirectResponse|BinaryFileResponse|Response
    {
        $path = $downloads->localSetupAbsolutePath();

        if ($downloads->hasLocalSetup()) {
            return response()->download(
                $path,
                (string) config('marketing.setup_asset_name', 'TipiDV-Setup.exe'),
                ['Cache-Control' => 'public, max-age=3600'],
            );
        }

        if (is_file($path) && ! is_readable($path)) {
            Log::error('TipiDV: instalador existe pero www-data no puede leerlo', ['path' => $path]);

            return redirect()->to(url('/#descargar'))
                ->with('error', 'El instalador está en el servidor pero falta ajustar permisos. Escríbenos por WhatsApp.');
        }

        $url = $downloads->externalSetupUrl();
        if ($url !== null) {
            return redirect()->away($url);
        }

        return redirect()->to(url('/#descargar'))
            ->with('error', 'La descarga aún no está disponible. Escríbenos por WhatsApp.');
    }
}
