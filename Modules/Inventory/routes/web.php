<?php

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Http\Controllers\InventoryHomeController;

Route::get('/modules/inventory', [InventoryHomeController::class, '__invoke'])->name('modules.inventory.index');

$module = collect(config('modules.catalog', []))->firstWhere('key', 'inventory');
$domain = $module['subdomain'] ?? null;

if ($domain) {
    Route::domain($domain)->group(function (): void {
        Route::get('/', [InventoryHomeController::class, '__invoke'])->name('modules.inventory.subdomain.index');
    });
}
