<?php

use Illuminate\Support\Facades\Route;
use Modules\FutureModule\Http\Controllers\FutureModuleHomeController;

Route::get('/modules/future-module', FutureModuleHomeController::class)->name('modules.future-module.index');

$module = collect(config('modules.catalog', []))->firstWhere('key', 'future-module');
$domain = $module['subdomain'] ?? null;

if ($domain) {
    Route::domain($domain)->group(function (): void {
        Route::get('/', FutureModuleHomeController::class)->name('modules.future-module.subdomain.index');
    });
}
