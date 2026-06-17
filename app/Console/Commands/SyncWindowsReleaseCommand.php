<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\WindowsDownloadService;
use Illuminate\Console\Command;

final class SyncWindowsReleaseCommand extends Command
{
    protected $signature = 'tipidv:sync-release';

    protected $description = 'Obtiene la URL del último TipiDV-Setup.exe desde GitHub Releases';

    public function handle(WindowsDownloadService $downloads): int
    {
        $release = $downloads->syncFromGithub();
        if ($release === null) {
            $this->error('No se encontró release con '.config('marketing.setup_asset_name').' en GitHub.');

            return self::FAILURE;
        }

        $this->info('Tag: '.($release['tag'] ?? '—'));
        $this->line('Setup: '.$release['setup_url']);
        if (! empty($release['portable_url'])) {
            $this->line('Portable: '.$release['portable_url']);
        }

        return self::SUCCESS;
    }
}
