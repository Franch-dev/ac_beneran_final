<?php

use Illuminate\Support\Facades\Route;
use Modules\AcMasjidMusholla\Http\Controllers\AcMasjidMushollaDashboardController;
use Modules\AcMasjidMusholla\Http\Controllers\AcMasjidMushollaHomeController;
use Modules\AcMasjidMusholla\Http\Controllers\AcMasjidMushollaMonitoringController;

Route::get('/modules/ac-masjid-musholla', [AcMasjidMushollaHomeController::class, '__invoke'])
    ->name('modules.ac-masjid-musholla.index');

$module = collect(config('modules.catalog', []))->firstWhere('key', 'ac-masjid-musholla');
$domain = $module['subdomain'] ?? null;

if ($domain) {
    Route::domain($domain)->group(function (): void {
        Route::get('/', [AcMasjidMushollaHomeController::class, '__invoke'])->name('modules.ac-masjid-musholla.subdomain.index');
    });
}

Route::middleware('auth')->prefix('modules/ac-masjid-musholla')->group(function (): void {
    Route::get('/dashboard', [AcMasjidMushollaDashboardController::class, '__invoke'])->name('modules.ac-masjid-musholla.dashboard');
    Route::get('/monitoring', [AcMasjidMushollaMonitoringController::class, '__invoke'])->name('modules.ac-masjid-musholla.monitoring');
});
