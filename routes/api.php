<?php

declare(strict_types=1);

use App\Http\Controllers\LicenseController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/plans', [LicenseController::class, 'plans']);
Route::post('/checkout', [LicenseController::class, 'checkout']);
Route::post('/activate', [LicenseController::class, 'activate']);
Route::post('/validate', [LicenseController::class, 'validateLicense']);

Route::post('/webhook/gridpay', [WebhookController::class, 'gridPay']);
