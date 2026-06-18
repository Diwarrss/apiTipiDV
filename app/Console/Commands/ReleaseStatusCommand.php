<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\WindowsDownloadService;
use Illuminate\Console\Command;

final class ReleaseStatusCommand extends Command
{
    protected $signature = 'tipidv:release-status';

    protected $description = 'Muestra la descarga activa (servidor local vs GitHub)';

    public function handle(WindowsDownloadService $downloads): int
    {
        $release = $downloads->release();
        $storePath = storage_path('app/private/windows-release.json');
        $localPath = storage_path('app/private/releases/TipiDV-Setup.exe');

        if ($release === null && ! $downloads->hasLocalSetup()) {
            $this->warn('No hay release configurada.');
            $this->line('Opciones:');
            $this->line('  1. php artisan tipidv:sync-release build-17   (GitHub → este servidor)');
            $this->line('  2. php artisan tipidv:release-upload /ruta/TipiDV-Setup.exe --tag=build-17');
            $this->line('  3. Webhook GitHub Actions (copia automática si hay token)');

            return self::FAILURE;
        }

        $this->info('Estado de descarga');
        $this->table(
            ['Campo', 'Valor'],
            [
                ['Tag', $release['tag'] ?? '—'],
                ['Versión sitio', $release['version'] ?? '—'],
                ['Instalador en servidor', $downloads->hasLocalSetup() ? 'Sí ✓' : 'No'],
                ['Tamaño local', $downloads->hasLocalSetup()
                    ? round(($downloads->localSetupSize() ?? 0) / 1024 / 1024, 1).' MB'
                    : '—'],
                ['URL pública', $downloads->isDownloadAvailable() ? route('site.download') : '—'],
                ['Origen GitHub', $release['external_setup_url'] ?? $release['setup_url'] ?? '—'],
                ['JSON metadata', file_exists($storePath) ? $storePath : '(no existe)'],
                ['Archivo .exe', file_exists($localPath) ? $localPath : '(no existe)'],
            ]
        );

        if ($downloads->hasLocalSetup()) {
            $this->newLine();
            $this->comment('Los clientes descargan desde tipidv.gridsoft.co — no necesitan acceso a GitHub.');
        } else {
            $this->newLine();
            $this->warn('El .exe NO está en el servidor. Ejecuta: php artisan tipidv:sync-release');
        }

        return self::SUCCESS;
    }
}
