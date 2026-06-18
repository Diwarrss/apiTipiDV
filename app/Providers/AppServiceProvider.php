<?php

namespace App\Providers;

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
            $view->with('downloadUrl', $downloads->setupUrl());
            $view->with('releaseVersion', $release['version'] ?? $release['tag'] ?? null);
            $view->with('hasDownload', $downloads->setupUrl() !== null);
        });
    }
}
