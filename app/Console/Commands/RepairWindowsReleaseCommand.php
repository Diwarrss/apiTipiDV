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

        $downloads->fixReleasePermissions();

        $this->info('Metadata reparada');
        $this->table(['Campo', 'Valor'], [
            ['Tag', $record['tag'] ?? '—'],
            ['Versión sitio', $record['version'] ?? '—'],
            ['URL pública', route('site.download')],
        ]);
        $this->line('Verifica: php artisan tipidv:release-status');

        return self::SUCCESS;
    }
}
