<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\WindowsDownloadService;
use Illuminate\Console\Command;

final class SyncWindowsReleaseCommand extends Command
{
    protected $signature = 'tipidv:sync-release {tag? : Tag GitHub, ej. build-17. Sin argumento = latest}';

    protected $description = 'Actualiza la URL de descarga desde GitHub Releases (requiere MARKETING_GITHUB_TOKEN)';

    public function handle(WindowsDownloadService $downloads): int
    {
        $tag = $this->argument('tag');
        $tag = is_string($tag) ? trim($tag) : null;
        if ($tag === '') {
            $tag = null;
        }

        if ($tag !== null) {
            $this->line("Sincronizando tag <info>{$tag}</info>…");
        } else {
            $this->line('Sincronizando último release (latest)…');
        }

        if (trim((string) config('marketing.github_token', '')) === '') {
            $this->error('Falta MARKETING_GITHUB_TOKEN en .env (repo privado lo necesita).');

            return self::FAILURE;
        }

        $release = $downloads->syncFromGithub(true, $tag);
        if ($release === null) {
            $this->error('No se encontró release con '.config('marketing.setup_asset_name').'.');
            $this->line('Prueba: php artisan tipidv:sync-release build-17');

            return self::FAILURE;
        }

        $this->info('Release guardada en storage/app/private/windows-release.json');
        $this->table(
            ['Campo', 'Valor'],
            [
                ['Tag', $release['tag'] ?? '—'],
                ['Versión', $release['version'] ?? '—'],
                ['Setup', $release['setup_url'] ?? '—'],
            ]
        );
        $this->newLine();
        $this->comment('Ejecuta: php artisan tipidv:release-status');

        return self::SUCCESS;
    }
}
