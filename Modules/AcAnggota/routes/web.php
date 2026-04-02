<?php

use Illuminate\Support\Facades\Route;
use Modules\AcAnggota\Http\Controllers\AcAnggotaDashboardController;
use Modules\AcAnggota\Http\Controllers\AcAnggotaHomeController;
use Modules\AcAnggota\Http\Controllers\AcAnggotaMonitoringController;

Route::get('/modules/ac-anggota', AcAnggotaHomeController::class)
    ->name('modules.ac-anggota.index');

$module = collect(config('modules.catalog', []))->firstWhere('key', 'ac-anggota');
$domain = $module['subdomain'] ?? null;

if ($domain) {
    Route::domain($domain)->group(function (): void {
        Route::get('/', AcAnggotaHomeController::class)->name('modules.ac-anggota.subdomain.index');
    });
}

Route::middleware('auth')->prefix('modules/ac-anggota')->group(function (): void {
    Route::get('/dashboard', AcAnggotaDashboardController::class)->name('modules.ac-anggota.dashboard');
    Route::get('/monitoring', AcAnggotaMonitoringController::class)->name('modules.ac-anggota.monitoring');
});
