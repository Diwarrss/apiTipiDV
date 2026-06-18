<?php

namespace App\Providers;

use App\Services\PlanCatalogService;
use App\Services\WindowsDownloadService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer(['site.*', 'emails.license-issued'], function ($view): void {
            $downloads = app(WindowsDownloadService::class);
            $release = $downloads->release();
            $hosted = $downloads->hasLocalSetup();

            // Solo mostrar botón de descarga cuando el .exe se sirve desde este servidor.
            $view->with('downloadUrl', $hosted ? route('site.download') : null);
            $view->with('releaseVersion', $hosted ? ($release['version'] ?? $release['tag'] ?? null) : null);
            $view->with('hasDownload', $hosted);
            $view->with('hostedOnPortal', $hosted);
        });

        View::composer('site.layout', function ($view): void {
            $plans = app(PlanCatalogService::class)->plans();
            $prices = array_filter(array_map(
                static fn (array $plan): float => (float) ($plan['value_cop'] ?? 0),
                $plans,
            ));
            $view->with('lowPriceCop', $prices !== [] ? min($prices) : null);
        });
    }
}
