<?php

use App\Http\Controllers\ACController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MasjidController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\ServiceOrderController;
use Illuminate\Support\Facades\Route;

Route::get('/modules/ac-service', function () {
    return redirect()->route('dashboard');
})->name('modules.ac-service.index');

$module = collect(config('modules.catalog', []))->firstWhere('key', 'ac-service');
$domain = $module['subdomain'] ?? null;

if ($domain) {
    Route::domain($domain)->group(function (): void {
        Route::get('/', function () {
            return redirect()->route('dashboard');
        })->name('modules.ac-service.subdomain.index');
    });
}

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [MasjidController::class, 'index'])->name('dashboard');
    Route::get('/monitoring', [MonitoringController::class, 'index'])->name('monitoring');

    Route::get('/masjid/{masjid}', [MasjidController::class, 'detail'])->name('masjid.detail');
    Route::get('/masjid/{masjid}/history', [ServiceOrderController::class, 'history'])->name('service-order.history');

    Route::middleware('role:frontdesk')->group(function () {
        Route::post('/masjid', [MasjidController::class, 'store'])->name('masjid.store');
        Route::put('/masjid/{masjid}', [MasjidController::class, 'update'])->name('masjid.update');
        Route::delete('/masjid/{masjid}', [MasjidController::class, 'destroy'])->name('masjid.destroy');

        Route::post('/masjid/{masjid}/ac', [ACController::class, 'store'])->name('ac.store');
        Route::post('/ac/bulk', [ACController::class, 'bulkStore'])->name('ac.bulk');
        Route::put('/ac/{acUnit}', [ACController::class, 'update'])->name('ac.update');
        Route::delete('/ac/{acUnit}', [ACController::class, 'destroy'])->name('ac.destroy');

        Route::post('/service-order', [ServiceOrderController::class, 'store'])->name('service-order.store');
        Route::delete('/service-order/{serviceOrder}', [ServiceOrderController::class, 'destroy'])->name('service-order.destroy');
    });

    Route::middleware('role:manager')->group(function () {
        Route::post('/service-order/{serviceOrder}/approve', [ServiceOrderController::class, 'approve'])->name('service-order.approve');
        Route::post('/service-order/{serviceOrder}/cancel-approve', [ServiceOrderController::class, 'cancelApprove'])->name('service-order.cancel-approve');
        Route::delete('/service-order/{serviceOrder}/manager', [ServiceOrderController::class, 'destroy'])->name('service-order.destroy-manager');
    });

    Route::middleware('role:frontdesk,manager')->group(function () {
        Route::get('/service-order/{serviceOrder}/spk', [InvoiceController::class, 'spk'])->name('spk.print');
        Route::get('/service-order/{serviceOrder}/invoice', [InvoiceController::class, 'print'])->name('invoice.print');
    });
});
