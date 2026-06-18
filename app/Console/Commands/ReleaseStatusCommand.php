<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\WindowsDownloadService;
use Illuminate\Console\Command;

final class ReleaseStatusCommand extends Command
{
    protected $signature = 'tipidv:release-status';

    protected $description = 'Muestra la URL de descarga activa (sitio + correo) y dónde está guardada';

    public function handle(WindowsDownloadService $downloads): int
    {
        $release = $downloads->release();
        $storePath = storage_path('app/private/windows-release.json');

        if ($release === null) {
            $this->warn('No hay release configurada.');
            $this->line('Opciones:');
            $this->line('  1. POST /api/webhook/github-release (GitHub Actions o curl)');
            $this->line('  2. php artisan tipidv:sync-release (requiere MARKETING_GITHUB_TOKEN)');
            $this->line('  3. MARKETING_DOWNLOAD_URL en .env');

            return self::FAILURE;
        }

        $this->info('Release activa ('.($release['source'] ?? 'unknown').')');
        $this->table(
            ['Campo', 'Valor'],
            [
                ['Tag', $release['tag'] ?? '—'],
                ['Versión', $release['version'] ?? '—'],
                ['Setup', $release['setup_url'] ?? '—'],
                ['Portable', $release['portable_url'] ?? '—'],
                ['Archivo en disco', file_exists($storePath) ? $storePath : '(solo cache/API)'],
            ]
        );

        $this->newLine();
        $this->comment('Si Setup tiene URL → botón Descargar en el sitio y en el correo de licencia.');

        return self::SUCCESS;
    }
}
