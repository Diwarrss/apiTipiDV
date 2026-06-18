<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\WindowsDownloadService;
use Illuminate\Console\Command;

final class SyncWindowsReleaseCommand extends Command
{
    protected $signature = 'tipidv:sync-release
                            {tag? : Tag GitHub, ej. build-17. Sin argumento = latest}
                            {--no-mirror : Solo guardar metadata, no copiar el .exe al servidor}';

    protected $description = 'Descarga el instalador desde GitHub y lo publica en este servidor (tipidv.gridsoft.co/descargar)';

    public function handle(WindowsDownloadService $downloads): int
    {
        $tag = $this->argument('tag');
        $tag = is_string($tag) ? trim($tag) : null;
        if ($tag === '') {
            $tag = null;
        }

        $mirror = ! $this->option('no-mirror');

        if (trim((string) config('marketing.github_token', '')) === '') {
            $this->error('Falta MARKETING_GITHUB_TOKEN en .env (necesario para repo privado).');
            $this->line('Alternativa: scp el .exe al server y ejecuta tipidv:release-upload /ruta/TipiDV-Setup.exe --tag=build-17');

            return self::FAILURE;
        }

        $this->line($tag !== null
            ? "Sincronizando <info>{$tag}</info>…"
            : 'Sincronizando último release…');

        if ($mirror) {
            $this->line('Copiando TipiDV-Setup.exe al servidor (puede tardar un minuto)…');
        }

        $release = $downloads->syncFromGithub(true, $tag, $mirror);
        if ($release === null) {
            $this->error('No se encontró release con '.config('marketing.setup_asset_name').'.');

            return self::FAILURE;
        }

        if ($downloads->hasLocalSetup()) {
            $downloads->fixReleasePermissions();
        }

        return $this->printResult($downloads, $release);
    }

    /** @param array<string, mixed> $release */
    private function printResult(WindowsDownloadService $downloads, array $release): int
    {
        $rows = [
            ['Tag', $release['tag'] ?? '—'],
            ['Versión', $release['version'] ?? '—'],
            ['En este servidor', $downloads->hasLocalSetup() ? 'Sí ✓' : 'No'],
        ];

        if ($downloads->hasLocalSetup()) {
            $bytes = $downloads->localSetupSize();
            $rows[] = ['Tamaño', $bytes !== null ? round($bytes / 1024 / 1024, 1).' MB' : '—'];
            $rows[] = ['Ruta disco', $downloads->localSetupAbsolutePath()];
            $rows[] = ['URL pública', route('site.download')];
        } else {
            $rows[] = ['URL externa', $release['external_setup_url'] ?? $release['setup_url'] ?? '—'];
            $this->warn('El .exe NO quedó en el servidor.');
            $mirrorError = $downloads->getLastMirrorError();
            if ($mirrorError) {
                $this->error('Motivo: '.$mirrorError);
            }
            $this->line('Alternativa: scp TipiDV-Setup.exe al server → php artisan tipidv:release-upload /tmp/TipiDV-Setup.exe --tag=build-17');
        }

        $this->info('Release actualizada');
        $this->table(['Campo', 'Valor'], $rows);

        $storePath = storage_path('app/private/windows-release.json');
        if (is_readable($storePath)) {
            $onDisk = json_decode((string) file_get_contents($storePath), true);
            $diskTag = is_array($onDisk) ? ($onDisk['tag'] ?? null) : null;
            $expectedTag = $release['tag'] ?? null;
            if ($expectedTag !== null && $diskTag !== $expectedTag) {
                $this->warn("windows-release.json sigue en «{$diskTag}» — permisos. Metadata en releases/release-meta.json.");
                $this->line('  sudo chown -R $USER:www-data storage/app/private && sudo chmod -R g+w storage/app/private');
            }
        }

        return $downloads->hasLocalSetup() ? self::SUCCESS : self::FAILURE;
    }
}
