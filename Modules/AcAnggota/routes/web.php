<?php

use App\Http\Controllers\ServiceOrderController;
use App\Models\Masjid;
use Illuminate\Support\Facades\Route;
use Modules\AcAnggota\Http\Controllers\AcAnggotaDashboardController;
use Modules\AcAnggota\Http\Controllers\AcAnggotaHomeController;
use Modules\AcAnggota\Http\Controllers\AcAnggotaMonitoringController;

Route::get('/modules/ac-anggota', [AcAnggotaHomeController::class, '__invoke'])
    ->name('modules.ac-anggota.index');

Route::get('/modules/ac-anggota/card', [AcAnggotaHomeController::class, 'card'])
    ->name('modules.ac-anggota.card');

Route::get('/modules/ac-anggota/guest-order', function () {
    if (auth()->check()) {
        return redirect()->route('modules.ac-anggota.dashboard');
    }

    $masjids = Masjid::query()
        ->orderBy('name')
        ->get(['id', 'name', 'custom_id']);

    return view('ac-service::guest-order', [
        'masjids' => $masjids,
        'moduleName' => 'AC Anggota',
        'returnRoute' => 'modules.ac-anggota.index',
        'formActionRoute' => 'modules.ac-anggota.guest-order.store',
    ]);
})->name('modules.ac-anggota.guest-order.index');

Route::post('/modules/ac-anggota/guest-order', [ServiceOrderController::class, 'guestStore'])
    ->middleware('throttle:writes')
    ->name('modules.ac-anggota.guest-order.store');

$module = collect(config('modules.catalog', []))->firstWhere('key', 'ac-anggota');
$domain = $module['subdomain'] ?? null;

if ($domain) {
    Route::domain($domain)->group(function (): void {
        Route::get('/', [AcAnggotaHomeController::class, '__invoke'])->name('modules.ac-anggota.subdomain.index');
        Route::middleware('auth')->group(function (): void {
            Route::get('/dashboard', [AcAnggotaDashboardController::class, '__invoke'])->name('modules.ac-anggota.subdomain.dashboard');
            Route::get('/monitoring', [AcAnggotaMonitoringController::class, '__invoke'])->name('modules.ac-anggota.subdomain.monitoring');
        });
    });
}

Route::middleware('auth')->prefix('modules/ac-anggota')->group(function (): void {
    Route::get('/dashboard', [AcAnggotaDashboardController::class, '__invoke'])->name('modules.ac-anggota.dashboard');
    Route::get('/dashboard/snapshot', [AcAnggotaDashboardController::class, 'snapshot'])->name('modules.ac-anggota.dashboard.snapshot');
    Route::get('/monitoring', [AcAnggotaMonitoringController::class, '__invoke'])->name('modules.ac-anggota.monitoring');

    Route::prefix('anggota')->name('anggota.')->middleware(['role:frontdesk,admin', 'throttle:writes'])->group(function () {
        Route::post('/', [Modules\AcAnggota\Http\Controllers\AnggotaController::class, 'store'])->name('store');
        Route::get('{anggota}', [Modules\AcAnggota\Http\Controllers\AnggotaController::class, 'detail'])->name('detail');
        Route::match(['put', 'patch'], '{anggota}', [Modules\AcAnggota\Http\Controllers\AnggotaController::class, 'update'])->name('update');
        Route::delete('{anggota}', [Modules\AcAnggota\Http\Controllers\AnggotaController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('ac')->name('ac.')->middleware(['role:frontdesk,admin', 'throttle:writes'])->group(function () {
        Route::post('/bulk', [Modules\AcAnggota\Http\Controllers\AcAnggotaController::class, 'bulkStore'])->name('bulk');
        Route::match(['put', 'patch'], '{ac}', [Modules\AcAnggota\Http\Controllers\AcAnggotaController::class, 'update'])->name('update');
        Route::delete('{ac}', [Modules\AcAnggota\Http\Controllers\AcAnggotaController::class, 'destroy'])->name('destroy');
    });
});
