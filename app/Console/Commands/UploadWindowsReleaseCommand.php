<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\WindowsDownloadService;
use Illuminate\Console\Command;

final class UploadWindowsReleaseCommand extends Command
{
    protected $signature = 'tipidv:release-upload
                            {path : Ruta al TipiDV-Setup.exe en el servidor}
                            {--tag= : Tag visible, ej. build-17}
                            {--version= : Versión mostrada en el sitio}';

    protected $description = 'Sube un TipiDV-Setup.exe local al portal para descarga pública (/descargar)';

    public function handle(WindowsDownloadService $downloads): int
    {
        $path = (string) $this->argument('path');
        $tag = $this->option('tag');
        $version = $this->option('version') ?? $tag;

        try {
            $record = $downloads->uploadLocalFile($path, is_string($tag) ? $tag : null, is_string($version) ? $version : null);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Instalador publicado en el servidor');
        $this->table(
            ['Campo', 'Valor'],
            [
                ['Tag', $record['tag'] ?? '—'],
                ['Versión', $record['version'] ?? '—'],
                ['Archivo', $downloads->localSetupAbsolutePath()],
                ['Tamaño', round(($record['local_size'] ?? 0) / 1024 / 1024, 1).' MB'],
                ['Descarga', route('site.download')],
            ]
        );

        return self::SUCCESS;
    }
}
