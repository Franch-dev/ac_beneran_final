<?php

use Illuminate\Support\Facades\Route;
use Modules\FutureModule\Http\Controllers\FutureModuleDashboardController;
use Modules\FutureModule\Http\Controllers\FutureModuleHomeController;

Route::get('/modules/future-module', [FutureModuleHomeController::class, '__invoke'])->name('modules.future-module.index');
Route::middleware('auth')->get('/modules/future-module/dashboard', [FutureModuleDashboardController::class, '__invoke'])->name('modules.future-module.dashboard');

$module = collect(config('modules.catalog', []))->firstWhere('key', 'future-module');
$domain = $module['subdomain'] ?? null;

if ($domain) {
    Route::domain($domain)->group(function (): void {
        Route::get('/', [FutureModuleHomeController::class, '__invoke'])->name('modules.future-module.subdomain.index');
        Route::middleware('auth')->get('/dashboard', [FutureModuleDashboardController::class, '__invoke'])->name('modules.future-module.subdomain.dashboard');
    });
}
