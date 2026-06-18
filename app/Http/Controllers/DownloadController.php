<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\WindowsDownloadService;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

final class DownloadController extends Controller
{
    public function setup(WindowsDownloadService $downloads): RedirectResponse|Response
    {
        $url = $downloads->setupUrl();
        if ($url === null) {
            return redirect()->to(url('/#descargar'))
                ->with('error', 'La descarga aún no está disponible. Escríbenos por WhatsApp.');
        }

        return redirect()->away($url);
    }
}
