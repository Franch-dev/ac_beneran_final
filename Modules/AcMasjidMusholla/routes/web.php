<?php

use App\Http\Controllers\ServiceOrderController;
use App\Models\Masjid;
use Illuminate\Support\Facades\Route;
use Modules\AcMasjidMusholla\Http\Controllers\AcMasjidMushollaDashboardController;
use Modules\AcMasjidMusholla\Http\Controllers\AcMasjidMushollaHomeController;
use Modules\AcMasjidMusholla\Http\Controllers\AcMasjidMushollaMonitoringController;

Route::get('/modules/ac-masjid-musholla', [AcMasjidMushollaHomeController::class, '__invoke'])
    ->name('modules.ac-masjid-musholla.index');

Route::get('/modules/ac-masjid-musholla/card', [AcMasjidMushollaHomeController::class, 'card'])
    ->name('modules.ac-masjid-musholla.card');

Route::get('/modules/ac-masjid-musholla/guest-order', function () {
    if (auth()->check()) {
        return redirect()->route('modules.ac-masjid-musholla.dashboard');
    }

    $masjids = Masjid::query()
        ->orderBy('name')
        ->get(['id', 'name', 'custom_id']);

    return view('ac-service::guest-order', [
        'masjids' => $masjids,
        'moduleName' => 'AC Masjid & Musholla',
        'returnRoute' => 'modules.ac-masjid-musholla.index',
        'formActionRoute' => 'modules.ac-masjid-musholla.guest-order.store',
    ]);
})->name('modules.ac-masjid-musholla.guest-order.index');

Route::post('/modules/ac-masjid-musholla/guest-order', [ServiceOrderController::class, 'guestStore'])
    ->name('modules.ac-masjid-musholla.guest-order.store');

$module = collect(config('modules.catalog', []))->firstWhere('key', 'ac-masjid-musholla');
$domain = $module['subdomain'] ?? null;

if ($domain) {
    Route::domain($domain)->group(function (): void {
        Route::get('/', [AcMasjidMushollaHomeController::class, '__invoke'])->name('modules.ac-masjid-musholla.subdomain.index');
        Route::middleware('auth')->group(function (): void {
            Route::get('/dashboard', [AcMasjidMushollaDashboardController::class, '__invoke'])->name('modules.ac-masjid-musholla.subdomain.dashboard');
            Route::get('/monitoring', [AcMasjidMushollaMonitoringController::class, '__invoke'])->name('modules.ac-masjid-musholla.subdomain.monitoring');
        });
    });
}

Route::middleware('auth')->prefix('modules/ac-masjid-musholla')->group(function (): void {
    Route::get('/dashboard', [AcMasjidMushollaDashboardController::class, '__invoke'])->name('modules.ac-masjid-musholla.dashboard');
    Route::get('/monitoring', [AcMasjidMushollaMonitoringController::class, '__invoke'])->name('modules.ac-masjid-musholla.monitoring');
    Route::get('/monitoring/snapshot', [AcMasjidMushollaMonitoringController::class, 'snapshot'])->name('modules.ac-masjid-musholla.monitoring.snapshot');
    Route::get('/dashboard/snapshot', [AcMasjidMushollaDashboardController::class, 'snapshot'])->name('modules.ac-masjid-musholla.dashboard.snapshot');

    // Archiving and history endpoints for monitoring workflow
    Route::get('/service-order/{serviceOrder}/history', [\App\Http\Controllers\WorkflowController::class, 'orderHistory'])
        ->name('workflow.service-order.history');
    Route::post('/service-order/{serviceOrder}/archive', [\App\Http\Controllers\WorkflowController::class, 'archiveOrder'])
        ->name('service-order.archive');
    Route::post('/workflow/{serviceOrder}/approve-spk-invoice', [\App\Http\Controllers\WorkflowController::class, 'approveSpkInvoice'])
        ->name('workflow.approve-spk-invoice');

    Route::prefix('masjid')->name('masjid.')->group(function () {
        Route::post('/', [ \App\Http\Controllers\MasjidController::class, 'store' ])->name('store');
        Route::get('{masjid}', [ \App\Http\Controllers\MasjidController::class, 'detail' ])->name('detail');
        Route::match([ 'PUT', 'PATCH' ], '{masjid}', [ \App\Http\Controllers\MasjidController::class, 'update' ])->name('update');
        Route::delete('{masjid}', [ \App\Http\Controllers\MasjidController::class, 'destroy' ])->name('destroy');
    });

    Route::prefix('ac')->name('ac.')->group(function () {
        Route::post('bulk', [ \App\Http\Controllers\ACController::class, 'bulkStore' ])->name('bulk');
        Route::match([ 'PUT', 'PATCH' ], '{ac}', [ \App\Http\Controllers\ACController::class, 'update' ])->name('update');
        Route::delete('{ac}', [ \App\Http\Controllers\ACController::class, 'destroy' ])->name('destroy');
    });

    Route::prefix('service-order')->name('service-order.')->group(function () {
        Route::post('/', [ \App\Http\Controllers\ServiceOrderController::class, 'store' ])->name('store');
        Route::post('{service_order}/approve', [ \App\Http\Controllers\ServiceOrderController::class, 'approve' ])->name('approve');
        Route::delete('{service_order}', [ \App\Http\Controllers\ServiceOrderController::class, 'destroy' ])->name('destroy');
    });
});
