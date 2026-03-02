<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MasjidController;
use App\Http\Controllers\ACController;
use App\Http\Controllers\ServiceOrderController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\InvoiceController;
use Illuminate\Support\Facades\Route;

// Public
use App\Http\Controllers\HomeController;
Route::get('/', [HomeController::class, 'index'])->name('home');

// Auth
Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Authenticated routes
Route::middleware('auth')->group(function () {

    // Dashboard
    Route::get('/dashboard', [MasjidController::class, 'index'])->name('dashboard');

    // Masjid detail (all roles can view)
    Route::get('/masjid/{masjid}', [MasjidController::class, 'detail'])->name('masjid.detail');

    // Frontdesk only
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

    // Monitoring - all roles
    Route::get('/monitoring', [MonitoringController::class, 'index'])->name('monitoring');

    // Service order history
    Route::get('/masjid/{masjid}/history', [ServiceOrderController::class, 'history'])->name('service-order.history');

    // Manager only
    Route::middleware('role:manager')->group(function () {
        Route::post('/service-order/{serviceOrder}/approve', [ServiceOrderController::class, 'approve'])->name('service-order.approve');
        Route::post('/service-order/{serviceOrder}/cancel-approve', [ServiceOrderController::class, 'cancelApprove'])->name('service-order.cancel-approve');
        Route::delete('/service-order/{serviceOrder}/manager', [ServiceOrderController::class, 'destroy'])->name('service-order.destroy-manager');
    });

    // Print routes (frontdesk + manager)
    Route::middleware('role:frontdesk,manager')->group(function () {
        Route::get('/service-order/{serviceOrder}/spk', [InvoiceController::class, 'spk'])->name('spk.print');
        Route::get('/service-order/{serviceOrder}/invoice', [InvoiceController::class, 'print'])->name('invoice.print');
    });
});
