<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\WindowsDownloadService;
use Illuminate\Console\Command;

final class RepairWindowsReleaseCommand extends Command
{
    protected $signature = 'tipidv:release-repair
                            {tag : Tag activo, ej. build-17}
                            {--label= : Etiqueta visible en el sitio (default: tag)}';

    protected $description = 'Reescribe metadata de descarga sin volver a bajar el .exe (útil si windows-release.json quedó desactualizado)';

    public function handle(WindowsDownloadService $downloads): int
    {
        $tag = (string) $this->argument('tag');
        $version = $this->option('label');
        $version = is_string($version) && trim($version) !== '' ? trim($version) : null;

        try {
            $record = $downloads->repairMetadata($tag, $version);
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $metaPath = storage_path('app/private/releases/release-meta.json');
        $storePath = storage_path('app/private/windows-release.json');
        $mainJsonOk = false;
        if (is_readable($storePath)) {
            $onDisk = json_decode((string) file_get_contents($storePath), true);
            $mainJsonOk = is_array($onDisk) && ($onDisk['tag'] ?? null) === ($record['tag'] ?? $tag);
        }

        $this->info('Metadata reparada');
        $this->table(['Campo', 'Valor'], [
            ['Tag', $record['tag'] ?? '—'],
            ['Versión sitio', $record['version'] ?? '—'],
            ['Meta local', file_exists($metaPath) ? 'Sí ✓' : 'No'],
            ['windows-release.json', $mainJsonOk ? 'Sí ✓' : 'No (permisos)'],
            ['URL pública', route('site.download')],
        ]);

        if (! $mainJsonOk) {
            $this->newLine();
            $this->warn('release-meta.json sí se actualizó; el sitio debería funcionar.');
            $this->line('Para arreglar windows-release.json también:');
            $this->line('  sudo chown ubuntu:www-data storage/app/private/windows-release.json');
            $this->line('  sudo chmod g+w storage/app/private/windows-release.json');
        }

        $this->line('Verifica: php artisan tipidv:release-status');

        return self::SUCCESS;
    }
}
