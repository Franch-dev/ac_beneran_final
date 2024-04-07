<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Public
use App\Http\Controllers\AcAnggotaPageController;
use App\Http\Controllers\HomeController;
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/ac-anggota', [AcAnggotaPageController::class, 'index'])->name('ac-anggota.index');

Route::middleware('auth')->prefix('ac-anggota')->group(function (): void {
    Route::get('/dashboard', [AcAnggotaPageController::class, 'dashboard'])->name('ac-anggota.dashboard');
    Route::get('/monitoring', [AcAnggotaPageController::class, 'monitoring'])->name('ac-anggota.monitoring');
});

// Auth
Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login')->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware(['web', 'auth']);

// Sitemap
Route::get('/sitemap', function () {
    return view('sitemap');
})->name('sitemap');

Route::get('/sitemap.json', function () {
    return response()->json(config('sitemap'));
})->name('sitemap.json');

Route::post('/service-orders/close', [App\Http\Controllers\CloseOrderController::class, '__invoke'])->name('service-orders.close')->middleware('auth');

