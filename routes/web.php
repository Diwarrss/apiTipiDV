<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SiteController::class, 'home'])->name('site.home');
Route::get('/comprar', [SiteController::class, 'comprar'])->name('site.comprar');
Route::get('/gracias', [SiteController::class, 'gracias'])->name('site.gracias');
Route::get('/descargar', [DownloadController::class, 'setup'])->name('site.download');
Route::get('/robots.txt', [SiteController::class, 'robots']);
Route::get('/sitemap.xml', [SiteController::class, 'sitemap']);
Route::get('/llms.txt', [SiteController::class, 'llms']);

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.submit');
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware('super.admin')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('subscriptions/{subscription}', [DashboardController::class, 'show'])->name('subscriptions.show');
        Route::post('subscriptions/{subscription}/extend', [DashboardController::class, 'extend'])->name('subscriptions.extend');
        Route::post('subscriptions/{subscription}/slots', [DashboardController::class, 'updateSlots'])->name('subscriptions.slots');
        Route::post('activations/{activation}/deactivate', [DashboardController::class, 'deactivateMachine'])->name('activations.deactivate');
    });
});
