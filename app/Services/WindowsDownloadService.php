<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * URL del instalador Windows: archivo guardado por webhook > GitHub API > .env.
 *
 * Laravel 11: el disco "local" vive en storage/app/private/
 * → storage/app/private/windows-release.json (no en storage/app/ directo).
 */
final class WindowsDownloadService
{
    private const STORE_PATH = 'windows-release.json';

    private const CACHE_KEY = 'tipidv.windows_release';

    /** @param array{tag?: string, setup_url: string, portable_url?: string|null, published_at?: string} $payload */
    public function persistFromWebhook(array $payload): void
    {
        $setup = trim((string) ($payload['setup_url'] ?? ''));
        if ($setup === '' || ! filter_var($setup, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('setup_url inválida');
        }

        $data = [
            'tag' => $payload['tag'] ?? null,
            'setup_url' => $setup,
            'portable_url' => $payload['portable_url'] ?? null,
            'published_at' => $payload['published_at'] ?? now()->toIso8601String(),
            'source' => 'webhook',
        ];

        Storage::disk('local')->put(self::STORE_PATH, json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        Cache::forget(self::CACHE_KEY);
    }

    public function setupUrl(): ?string
    {
        return $this->release()['setup_url'] ?? null;
    }

    public function portableUrl(): ?string
    {
        return $this->release()['portable_url'] ?? null;
    }

    /** @return array{tag?: string, setup_url?: string, portable_url?: string|null, published_at?: string, source?: string}|null */
    public function release(): ?array
    {
        return Cache::remember(self::CACHE_KEY, 300, function (): ?array {
            $stored = $this->readStored();
            if ($stored !== null) {
                return $stored;
            }

            $fromGithub = $this->fetchFromGithubApi();
            if ($fromGithub !== null) {
                return $fromGithub;
            }

            $fallback = trim((string) config('marketing.download_url', ''));
            if ($fallback !== '' && filter_var($fallback, FILTER_VALIDATE_URL)) {
                return [
                    'setup_url' => $fallback,
                    'source' => 'env',
                ];
            }

            return null;
        });
    }

    /** Refresca desde GitHub API y opcionalmente persiste. */
    public function syncFromGithub(bool $persist = true): ?array
    {
        Cache::forget(self::CACHE_KEY);
        $release = $this->fetchFromGithubApi();
        if ($release === null) {
            return null;
        }

        if ($persist) {
            $release['source'] = 'github_sync';
            Storage::disk('local')->put(self::STORE_PATH, json_encode($release, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        }

        Cache::forget(self::CACHE_KEY);

        return $release;
    }

    /** @return array<string, mixed>|null */
    private function readStored(): ?array
    {
        if (! Storage::disk('local')->exists(self::STORE_PATH)) {
            return null;
        }

        $decoded = json_decode(Storage::disk('local')->get(self::STORE_PATH), true);

        return is_array($decoded) && ! empty($decoded['setup_url']) ? $decoded : null;
    }

    /** @return array<string, mixed>|null */
    private function fetchFromGithubApi(): ?array
    {
        $repo = (string) config('marketing.github_repo', '');
        if ($repo === '') {
            return null;
        }

        $token = (string) config('marketing.github_token', '');
        // Repo privado: sin token GitHub responde 404 (no 403). Usar webhook o MARKETING_GITHUB_TOKEN.
        if ($token === '') {
            return null;
        }

        $url = 'https://api.github.com/repos/'.$repo.'/releases/latest';
        $response = Http::timeout(10)
            ->acceptJson()
            ->withToken($token)
            ->get($url);

        if (! $response->successful()) {
            Log::warning('TipiDV: GitHub releases/latest falló', [
                'status' => $response->status(),
                'repo' => $repo,
            ]);

            return null;
        }

        $release = $response->json();
        if (! is_array($release)) {
            return null;
        }

        $setupName = (string) config('marketing.setup_asset_name', 'TipiDV-Setup.exe');
        $portableName = (string) config('marketing.portable_asset_name', 'TipiDV-Portable.zip');
        $assets = $release['assets'] ?? [];

        $setupUrl = $this->findAssetUrl($assets, $setupName);
        if ($setupUrl === null) {
            return null;
        }

        return [
            'tag' => $release['tag_name'] ?? null,
            'setup_url' => $setupUrl,
            'portable_url' => $this->findAssetUrl($assets, $portableName),
            'published_at' => $release['published_at'] ?? null,
            'source' => 'github_api',
        ];
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
