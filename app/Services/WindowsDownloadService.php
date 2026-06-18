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

    /** Metadata junto al .exe (escribible aunque windows-release.json esté bloqueado). */
    private const LOCAL_META_PATH = 'releases/release-meta.json';

    private const CACHE_KEY = 'tipidv.windows_release';

    private ?string $lastMirrorError = null;

    public function getLastMirrorError(): ?string
    {
        return $this->lastMirrorError;
    }

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
        if (! Storage::disk('local')->exists(self::LOCAL_SETUP_PATH)) {
            return false;
        }

        return is_readable($this->localSetupAbsolutePath());
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
        if ($this->hasLocalSetup()) {
            return null;
        }

        $release = $this->release();
        if ($release === null) {
            return null;
        }

        $url = $release['external_setup_url'] ?? $release['setup_url'] ?? null;

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

        $release['source'] = 'github_sync';

        if ($mirrorToServer) {
            $mirrored = $this->mirrorExternalSetup($release);
            if ($mirrored !== null) {
                $release = $mirrored;
            }
        }

        if ($persist) {
            try {
                $this->writeRelease($release);
            } catch (\RuntimeException $e) {
                if ($mirrorToServer && $this->hasLocalSetup()) {
                    Log::warning('TipiDV: windows-release.json no se pudo actualizar; usando release-meta.json', [
                        'error' => $e->getMessage(),
                    ]);
                } else {
                    throw $e;
                }
            }
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

        $this->ensureReleaseFilePermissions();

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
        $record['mirrored_at'] = now()->toIso8601String();
        $this->writeRelease($record);

        return $record;
    }

    /** Descarga el instalador al disco local (API de assets GitHub para repos privados). */
    public function mirrorExternalSetup(array $release): ?array
    {
        $this->lastMirrorError = null;
        $token = trim((string) config('marketing.github_token', ''));
        $repo = trim((string) config('marketing.github_repo', ''));

        if ($token === '') {
            $this->lastMirrorError = 'Falta MARKETING_GITHUB_TOKEN en .env';

            return null;
        }

        $this->ensureReleaseDirectory();

        $assetId = isset($release['setup_asset_id']) ? (int) $release['setup_asset_id'] : 0;
        $downloaded = false;

        if ($assetId > 0 && $repo !== '') {
            $downloaded = $this->downloadGithubReleaseAsset($repo, $assetId, $token);
        }

        if (! $downloaded) {
            $url = $release['external_setup_url'] ?? $release['setup_url'] ?? null;
            if (is_string($url) && $url !== '') {
                $downloaded = $this->downloadUrlToLocal($url, $token);
            }
        }

        if (! $downloaded) {
            return null;
        }

        $this->ensureReleaseFilePermissions();

        $release['local_file'] = self::LOCAL_SETUP_PATH;
        $release['local_size'] = filesize($this->localSetupAbsolutePath());
        $release['mirrored_at'] = now()->toIso8601String();

        $this->writeLocalMeta($release);

        Log::info('TipiDV: instalador copiado al servidor', [
            'tag' => $release['tag'] ?? null,
            'bytes' => $release['local_size'],
        ]);

        return $release;
    }

    private function ensureReleaseDirectory(): void
    {
        $dir = dirname(self::LOCAL_SETUP_PATH);
        if ($dir !== '.' && ! Storage::disk('local')->exists($dir)) {
            Storage::disk('local')->makeDirectory($dir);
        }
        $this->ensureReleaseFilePermissions();
    }

    /** Ajusta permisos para que nginx/php (www-data) pueda leer el instalador. */
    public function fixReleasePermissions(): void
    {
        $this->ensureReleaseFilePermissions();
    }

    private function ensureReleaseFilePermissions(): void
    {
        $root = Storage::disk('local')->path('');
        $path = $this->localSetupAbsolutePath();
        $dir = dirname($path);

        if (is_dir($root)) {
            @chmod($root, 0755);
        }
        if (is_dir($dir)) {
            @chmod($dir, 0755);
        }
        if (is_file($path)) {
            @chmod($path, 0644);
        }
    }

    private function downloadGithubReleaseAsset(string $repo, int $assetId, string $token): bool
    {
        $tempPath = $this->partFilePath();
        $url = 'https://api.github.com/repos/'.$repo.'/releases/assets/'.$assetId;

        $response = Http::timeout(600)
            ->withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Accept' => 'application/octet-stream',
                'X-GitHub-Api-Version' => '2022-11-28',
            ])
            ->withOptions(['sink' => $tempPath, 'allow_redirects' => true])
            ->get($url);

        return $this->finalizeDownload($tempPath, $response->status(), $url, (string) $response->body());
    }

    private function downloadUrlToLocal(string $url, string $token): bool
    {
        $tempPath = $this->partFilePath();

        $response = Http::timeout(600)
            ->withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Accept' => 'application/octet-stream',
            ])
            ->withOptions(['sink' => $tempPath, 'allow_redirects' => true])
            ->get($url);

        return $this->finalizeDownload($tempPath, $response->status(), $url, (string) $response->body());
    }

    private function partFilePath(): string
    {
        return Storage::disk('local')->path(self::LOCAL_SETUP_PATH.'.part');
    }

    private function finalizeDownload(string $tempPath, int $status, string $url, string $bodySnippet): bool
    {
        if (is_file($tempPath)) {
            $size = filesize($tempPath);
            $head = file_get_contents($tempPath, false, null, 0, 200) ?: '';
            $looksHtml = str_starts_with(ltrim($head), '<') || str_contains(strtolower($head), '<html');

            if ($status >= 200 && $status < 300 && $size >= 1024 && ! $looksHtml) {
                rename($tempPath, $this->localSetupAbsolutePath());
                $this->ensureReleaseFilePermissions();

                return true;
            }

            @unlink($tempPath);
            $this->lastMirrorError = "HTTP {$status}, {$size} bytes"
                .($looksHtml ? ' (GitHub devolvió HTML — token sin acceso al repo o URL incorrecta)' : '');
        } else {
            $this->lastMirrorError = "HTTP {$status}, archivo vacío";
        }

        if ($this->lastMirrorError === null || $this->lastMirrorError === "HTTP {$status}, archivo vacío") {
            $snippet = substr(preg_replace('/\s+/', ' ', strip_tags($bodySnippet)) ?? '', 0, 120);
            if ($snippet !== '') {
                $this->lastMirrorError .= ' — '.$snippet;
            }
        }

        Log::error('TipiDV: falló copia del instalador al servidor', [
            'status' => $status,
            'url' => $url,
            'error' => $this->lastMirrorError,
        ]);

        return false;
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** Reescribe metadata cuando el .exe ya está en disco (sin re-descargar). */
    public function repairMetadata(string $tag, ?string $version = null): array
    {
        if (! Storage::disk('local')->exists(self::LOCAL_SETUP_PATH)) {
            throw new \InvalidArgumentException('No hay instalador local en releases/TipiDV-Setup.exe.');
        }

        $this->fixReleasePermissions();

        $tag = ltrim(trim($tag), '@');
        $record = [
            'tag' => $tag,
            'version' => $version ?? $tag,
            'local_file' => self::LOCAL_SETUP_PATH,
            'local_size' => Storage::disk('local')->size(self::LOCAL_SETUP_PATH),
            'mirrored_at' => now()->toIso8601String(),
            'source' => 'repair',
        ];

        $this->writeLocalMeta($record);

        if (! $this->attemptWriteRelease($record)) {
            Log::warning('TipiDV: windows-release.json no actualizado; sitio usa release-meta.json', [
                'path' => Storage::disk('local')->path(self::STORE_PATH),
            ]);
        }

        Cache::forget(self::CACHE_KEY);

        return $this->enrichRelease($record);
    }

    /** @param array<string, mixed> $data */
    private function writeRelease(array $data): void
    {
        $this->writeLocalMeta($data);

        if (! $this->attemptWriteRelease($data)) {
            $path = Storage::disk('local')->path(self::STORE_PATH);
            Log::error('TipiDV: no se pudo escribir windows-release.json', ['path' => $path]);
            throw new \RuntimeException(
                'No se pudo guardar '.self::STORE_PATH.' — revisa permisos en storage/app/private (ej. chown ubuntu:www-data && chmod g+w).'
            );
        }
    }

    /** @param array<string, mixed> $data */
    private function attemptWriteRelease(array $data): bool
    {
        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        if ($json === false) {
            return false;
        }

        if (! $this->putLocalFile(self::STORE_PATH, $json)) {
            return false;
        }

        Cache::forget(self::CACHE_KEY);

        return true;
    }

    /** @param array<string, mixed> $data */
    private function writeLocalMeta(array $data): void
    {
        $payload = [
            'tag' => $data['tag'] ?? null,
            'version' => $data['version'] ?? null,
            'local_file' => $data['local_file'] ?? self::LOCAL_SETUP_PATH,
            'local_size' => $data['local_size'] ?? $this->localSetupSize(),
            'mirrored_at' => $data['mirrored_at'] ?? now()->toIso8601String(),
            'published_at' => $data['published_at'] ?? null,
            'source' => $data['source'] ?? null,
        ];

        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        if ($json === false) {
            return;
        }

        if (! $this->putLocalFile(self::LOCAL_META_PATH, $json)) {
            Log::warning('TipiDV: no se pudo escribir release-meta.json', [
                'path' => Storage::disk('local')->path(self::LOCAL_META_PATH),
            ]);
        }
    }

    private function putLocalFile(string $relativePath, string $contents): bool
    {
        if (Storage::disk('local')->put($relativePath, $contents) === true) {
            return true;
        }

        $absolutePath = Storage::disk('local')->path($relativePath);
        $dir = dirname($absolutePath);
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        try {
            $written = @file_put_contents($absolutePath, $contents);

            return $written !== false;
        } catch (\Throwable) {
            return false;
        }
    }

    /** @return array<string, mixed>|null */
    private function readLocalMeta(): ?array
    {
        if (! Storage::disk('local')->exists(self::LOCAL_META_PATH)) {
            return null;
        }

        $decoded = json_decode(Storage::disk('local')->get(self::LOCAL_META_PATH), true);

        return is_array($decoded) ? $decoded : null;
    }

    /** @param array<string, mixed> $record */
    private function mergeLocalMeta(array $record): array
    {
        $meta = $this->readLocalMeta();
        if ($meta === null) {
            return $record;
        }

        foreach (['tag', 'version', 'local_file', 'local_size', 'mirrored_at', 'published_at', 'source'] as $key) {
            if (! empty($meta[$key])) {
                $record[$key] = $meta[$key];
            }
        }

        return $record;
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
        $record = $this->mergeLocalMeta($record);

        if ($this->hasLocalSetup()) {
            $record['local_file'] = self::LOCAL_SETUP_PATH;
            $record['local_size'] = Storage::disk('local')->size(self::LOCAL_SETUP_PATH);
            $record['hosted_on_portal'] = true;
            $record['portal_download_url'] = route('site.download');
            unset($record['setup_url'], $record['external_setup_url']);
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

        $record = $this->buildReleaseRecord(
            tag: $tagName,
            version: $this->releaseVersionLabel($tagName),
            externalUrl: $setupUrl,
            portableUrl: $this->findAssetUrl($assets, $portableName),
            publishedAt: isset($release['published_at']) ? (string) $release['published_at'] : null,
            source: 'github_api',
        );

        $assetId = $this->findAssetId($assets, $setupName);
        if ($assetId !== null) {
            $record['setup_asset_id'] = $assetId;
        }

        return $record;
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
    private function findAssetId(array $assets, string $name): ?int
    {
        foreach ($assets as $asset) {
            if (! is_array($asset)) {
                continue;
            }
            if (($asset['name'] ?? '') === $name && isset($asset['id'])) {
                return (int) $asset['id'];
            }
        }

        return null;
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
