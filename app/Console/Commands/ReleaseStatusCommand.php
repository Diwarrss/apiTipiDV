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
        $metaPath = storage_path('app/private/releases/release-meta.json');
        $localPath = storage_path('app/private/releases/TipiDV-Setup.exe');
        $releasesDir = dirname($localPath);
        $dirAccessible = is_dir($releasesDir) && is_readable($releasesDir);

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
                ['Meta local (.exe)', file_exists($metaPath) ? $metaPath : '(no existe)'],
                ['Archivo .exe', file_exists($localPath) ? $localPath : '(no existe)'],
                ['Carpeta releases/', is_dir($releasesDir) ? substr(sprintf('%o', fileperms($releasesDir)), -4) : '—'],
                ['Legible por web', is_readable($localPath) && $dirAccessible ? 'Sí ✓' : 'No — chmod 755 releases/'],
            ]
        );

        if (file_exists($localPath) && (! is_readable($localPath) || ! $dirAccessible)) {
            $this->newLine();
            $this->error('www-data no puede leer el instalador (carpeta releases/ suele quedar en 700).');
            $this->line('  chmod 755 storage/app/private/releases');
            $this->line('  chmod 644 storage/app/private/releases/TipiDV-Setup.exe');
            $this->line('  php artisan tipidv:release-repair build-17');
        }

        if (file_exists($storePath) && file_exists($metaPath)) {
            $jsonTag = json_decode((string) file_get_contents($storePath), true)['tag'] ?? null;
            $metaTag = json_decode((string) file_get_contents($metaPath), true)['tag'] ?? null;
            if ($jsonTag !== null && $metaTag !== null && $jsonTag !== $metaTag) {
                $this->warn("JSON desactualizado ({$jsonTag}); versión activa: {$metaTag}. Ejecuta sync-release o corrige permisos.");
            }
        }

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
