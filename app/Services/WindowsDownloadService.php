<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Instalador Windows: archivo en este servidor (preferido) > metadata JSON > GitHub API > .env.
 *
 * Archivos en storage/app/private/
 *   windows-release.json
 *   releases/TipiDV-Setup.exe
 */
final class WindowsDownloadService
{
    private const STORE_PATH = 'windows-release.json';

    private const LOCAL_SETUP_PATH = 'releases/TipiDV-Setup.exe';

    private const CACHE_KEY = 'tipidv.windows_release';

    /** @param array{tag?: string, version?: string, setup_url: string, portable_url?: string|null, published_at?: string} $payload */
    public function persistFromWebhook(array $payload): void
    {
        $setup = trim((string) ($payload['setup_url'] ?? ''));
        if ($setup === '' || ! filter_var($setup, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('setup_url inválida');
        }

        $data = $this->buildReleaseRecord(
            tag: isset($payload['tag']) ? (string) $payload['tag'] : null,
            version: isset($payload['version']) ? (string) $payload['version'] : null,
            externalUrl: $setup,
            portableUrl: $payload['portable_url'] ?? null,
            publishedAt: $payload['published_at'] ?? now()->toIso8601String(),
            source: 'webhook',
        );

        $this->writeRelease($data);

        if (trim((string) config('marketing.github_token', '')) !== '') {
            $mirrored = $this->mirrorExternalSetup($data);
            if ($mirrored !== null) {
                $mirrored['source'] = $data['source'] ?? 'webhook';
                $this->writeRelease($mirrored);
            }
        }
    }

    public function hasLocalSetup(): bool
    {
        return Storage::disk('local')->exists(self::LOCAL_SETUP_PATH);
    }

    public function localSetupAbsolutePath(): string
    {
        return Storage::disk('local')->path(self::LOCAL_SETUP_PATH);
    }

    public function localSetupSize(): ?int
    {
        if (! $this->hasLocalSetup()) {
            return null;
        }

        return Storage::disk('local')->size(self::LOCAL_SETUP_PATH);
    }

    /** URL externa (GitHub) guardada en metadata, si existe. */
    public function externalSetupUrl(): ?string
    {
        $url = $this->release()['external_setup_url'] ?? $this->release()['setup_url'] ?? null;

        return is_string($url) && $url !== '' ? $url : null;
    }

    /** @deprecated Usar hasLocalSetup() o externalSetupUrl() */
    public function setupUrl(): ?string
    {
        if ($this->hasLocalSetup()) {
            return route('site.download');
        }

        return $this->externalSetupUrl();
    }

    public function portableUrl(): ?string
    {
        return $this->release()['portable_url'] ?? null;
    }

    public function isDownloadAvailable(): bool
    {
        return $this->hasLocalSetup() || $this->externalSetupUrl() !== null;
    }

    /** @return array<string, mixed>|null */
    public function release(): ?array
    {
        return Cache::remember(self::CACHE_KEY, 300, function (): ?array {
            $stored = $this->readStored();
            if ($stored !== null) {
                return $this->enrichRelease($stored);
            }

            $fromGithub = $this->fetchFromGithubApi();
            if ($fromGithub !== null) {
                return $this->enrichRelease($fromGithub);
            }

            $fallback = trim((string) config('marketing.download_url', ''));
            if ($fallback !== '' && filter_var($fallback, FILTER_VALIDATE_URL)) {
                return $this->enrichRelease([
                    'external_setup_url' => $fallback,
                    'source' => 'env',
                ]);
            }

            if ($this->hasLocalSetup()) {
                return $this->enrichRelease(['source' => 'local_only']);
            }

            return null;
        });
    }

    /**
     * Metadata desde GitHub + opcionalmente copia el .exe a este servidor.
     *
     * @return array<string, mixed>|null
     */
    public function syncFromGithub(bool $persist = true, ?string $tag = null, bool $mirrorToServer = true): ?array
    {
        Cache::forget(self::CACHE_KEY);

        $release = $tag !== null && $tag !== ''
            ? $this->fetchReleaseByTag($tag)
            : $this->fetchFromGithubApi();

        if ($release === null) {
            return null;
        }

        if ($mirrorToServer && ! empty($release['external_setup_url'])) {
            $release = $this->mirrorExternalSetup($release) ?? $release;
        }

        if ($persist) {
            $release['source'] = 'github_sync';
            $this->writeRelease($release);
        }

        Cache::forget(self::CACHE_KEY);

        return $release;
    }

    /**
     * Copia un .exe ya descargado al almacenamiento del portal.
     *
     * @return array<string, mixed>
     */
    public function uploadLocalFile(string $absolutePath, ?string $tag = null, ?string $version = null): array
    {
        if (! is_file($absolutePath) || ! is_readable($absolutePath)) {
            throw new \InvalidArgumentException('Archivo no encontrado o no legible: '.$absolutePath);
        }

        $stream = fopen($absolutePath, 'rb');
        if ($stream === false) {
            throw new \RuntimeException('No se pudo leer el instalador.');
        }

        Storage::disk('local')->put(self::LOCAL_SETUP_PATH, $stream);
        fclose($stream);

        $record = $this->buildReleaseRecord(
            tag: $tag,
            version: $version ?? $tag,
            externalUrl: null,
            portableUrl: null,
            publishedAt: now()->toIso8601String(),
            source: 'upload',
        );
        $record['local_file'] = self::LOCAL_SETUP_PATH;
        $record['local_size'] = Storage::disk('local')->size(self::LOCAL_SETUP_PATH);
        $this->writeRelease($record);

        return $record;
    }

    /** Descarga el instalador desde la URL externa (GitHub) al disco local. */
    public function mirrorExternalSetup(array $release): ?array
    {
        $url = $release['external_setup_url'] ?? $release['setup_url'] ?? null;
        if (! is_string($url) || $url === '') {
            return null;
        }

        $dir = dirname(self::LOCAL_SETUP_PATH);
        if ($dir !== '.' && ! Storage::disk('local')->exists($dir)) {
            Storage::disk('local')->makeDirectory($dir);
        }

        $tempPath = Storage::disk('local')->path(self::LOCAL_SETUP_PATH.'.part');
        $finalPath = Storage::disk('local')->path(self::LOCAL_SETUP_PATH);

        $request = Http::timeout(600)->accept('*/*');
        $token = (string) config('marketing.github_token', '');
        if ($token !== '' && str_contains($url, 'github.com')) {
            $request = $request->withToken($token);
        }

        $response = $request->withOptions(['sink' => $tempPath])->get($url);

        if (! $response->successful() || ! is_file($tempPath) || filesize($tempPath) < 1024) {
            if (is_file($tempPath)) {
                @unlink($tempPath);
            }
            Log::error('TipiDV: no se pudo copiar instalador al servidor', [
                'status' => $response->status(),
                'url' => $url,
            ]);

            return null;
        }

        rename($tempPath, $finalPath);

        $release['local_file'] = self::LOCAL_SETUP_PATH;
        $release['local_size'] = filesize($finalPath);
        $release['mirrored_at'] = now()->toIso8601String();

        Log::info('TipiDV: instalador copiado al servidor', [
            'tag' => $release['tag'] ?? null,
            'bytes' => $release['local_size'],
        ]);

        return $release;
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** @param array<string, mixed> $data */
    private function writeRelease(array $data): void
    {
        Storage::disk('local')->put(self::STORE_PATH, json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        Cache::forget(self::CACHE_KEY);
    }

    /** @return array<string, mixed> */
    private function buildReleaseRecord(
        ?string $tag,
        ?string $version,
        ?string $externalUrl,
        ?string $portableUrl,
        ?string $publishedAt,
        string $source,
    ): array {
        $record = [
            'tag' => $tag,
            'version' => $version ?? ($tag !== null ? $this->releaseVersionLabel($tag) : null),
            'published_at' => $publishedAt,
            'source' => $source,
        ];

        if ($externalUrl !== null && $externalUrl !== '') {
            $record['external_setup_url'] = $externalUrl;
        }

        if ($portableUrl !== null && $portableUrl !== '') {
            $record['portable_url'] = $portableUrl;
        }

        if ($this->hasLocalSetup()) {
            $record['local_file'] = self::LOCAL_SETUP_PATH;
            $record['local_size'] = Storage::disk('local')->size(self::LOCAL_SETUP_PATH);
        }

        return $record;
    }

    /** @param array<string, mixed> $record */
    private function enrichRelease(array $record): array
    {
        if ($this->hasLocalSetup()) {
            $record['local_file'] = self::LOCAL_SETUP_PATH;
            $record['local_size'] = Storage::disk('local')->size(self::LOCAL_SETUP_PATH);
            $record['hosted_on_portal'] = true;
            $record['portal_download_url'] = route('site.download');
        }

        return $record;
    }

    /** @return array<string, mixed>|null */
    private function readStored(): ?array
    {
        if (! Storage::disk('local')->exists(self::STORE_PATH)) {
            return $this->hasLocalSetup()
                ? $this->enrichRelease(['source' => 'local_only'])
                : null;
        }

        $decoded = json_decode(Storage::disk('local')->get(self::STORE_PATH), true);
        if (! is_array($decoded)) {
            return null;
        }

        $hasMeta = ! empty($decoded['external_setup_url'])
            || ! empty($decoded['setup_url'])
            || ! empty($decoded['local_file'])
            || $this->hasLocalSetup();

        return $hasMeta ? $this->enrichRelease($decoded) : null;
    }

    /** @return array<string, mixed>|null */
    private function fetchReleaseByTag(string $tag): ?array
    {
        $repo = (string) config('marketing.github_repo', '');
        $token = (string) config('marketing.github_token', '');
        if ($repo === '' || $token === '') {
            return null;
        }

        $tag = ltrim(trim($tag), '@');
        $url = 'https://api.github.com/repos/'.$repo.'/releases/tags/'.rawurlencode($tag);
        $response = Http::timeout(15)->acceptJson()->withToken($token)->get($url);

        if (! $response->successful()) {
            Log::warning('TipiDV: GitHub release by tag falló', [
                'status' => $response->status(),
                'tag' => $tag,
            ]);

            return null;
        }

        $release = $response->json();

        return is_array($release) ? $this->mapGithubRelease($release) : null;
    }

    /** @return array<string, mixed>|null */
    private function fetchFromGithubApi(): ?array
    {
        $repo = (string) config('marketing.github_repo', '');
        $token = (string) config('marketing.github_token', '');
        if ($repo === '' || $token === '') {
            return null;
        }

        $response = Http::timeout(15)
            ->acceptJson()
            ->withToken($token)
            ->get('https://api.github.com/repos/'.$repo.'/releases/latest');

        if (! $response->successful()) {
            Log::warning('TipiDV: GitHub releases/latest falló', [
                'status' => $response->status(),
                'repo' => $repo,
            ]);

            return null;
        }

        $release = $response->json();

        return is_array($release) ? $this->mapGithubRelease($release) : null;
    }

    /** @param array<string, mixed> $release */
    private function mapGithubRelease(array $release): ?array
    {
        $setupName = (string) config('marketing.setup_asset_name', 'TipiDV-Setup.exe');
        $assets = $release['assets'] ?? [];
        $tagName = isset($release['tag_name']) ? (string) $release['tag_name'] : null;

        $setupUrl = $this->findAssetUrl($assets, $setupName);
        if ($setupUrl === null) {
            return null;
        }

        $portableName = (string) config('marketing.portable_asset_name', 'TipiDV-Portable.zip');

        return $this->buildReleaseRecord(
            tag: $tagName,
            version: $this->releaseVersionLabel($tagName),
            externalUrl: $setupUrl,
            portableUrl: $this->findAssetUrl($assets, $portableName),
            publishedAt: isset($release['published_at']) ? (string) $release['published_at'] : null,
            source: 'github_api',
        );
    }

    private function releaseVersionLabel(?string $tag): ?string
    {
        if ($tag === null || $tag === '') {
            return null;
        }

        $tag = ltrim($tag, 'vV');
        if (preg_match('/^build-\d+$/i', $tag)) {
            return $tag;
        }
        if (preg_match('/^\d+\.\d+(\.\d+)?/', $tag, $m)) {
            return $m[0];
        }

        return $tag;
    }

    /** @param array<int, array<string, mixed>> $assets */
    private function findAssetUrl(array $assets, string $name): ?string
    {
        foreach ($assets as $asset) {
            if (! is_array($asset)) {
                continue;
            }
            if (($asset['name'] ?? '') === $name && ! empty($asset['browser_download_url'])) {
                return (string) $asset['browser_download_url'];
            }
        }

        return null;
    }
}
