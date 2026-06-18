<?php

declare(strict_types=1);

use App\Http\Controllers\AppReleaseController;
use App\Http\Controllers\LicenseController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/plans', [LicenseController::class, 'plans']);
Route::post('/checkout/quote', [LicenseController::class, 'quote']);
Route::post('/checkout', [LicenseController::class, 'checkout']);
Route::post('/activate', [LicenseController::class, 'activate']);
Route::post('/validate', [LicenseController::class, 'validateLicense']);

Route::get('/app/release', [AppReleaseController::class, 'show']);

Route::post('/webhook/wompi', [WebhookController::class, 'wompi']);
/** @deprecated alias — misma URL que configuraste en Wompi */
Route::post('/webhook/gridpay', [WebhookController::class, 'wompi']);
Route::post('/webhook/github-release', [WebhookController::class, 'githubRelease']);
