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
})->middleware(['auth', 'role:admin'])->name('sitemap.json');

Route::post('/service-orders/close', [App\Http\Controllers\CloseOrderController::class, '__invoke'])->name('service-orders.close')->middleware(['auth', 'role:manager,admin', 'throttle:writes']);

// Guest Order Routes (Public - no auth required)
use App\Http\Controllers\GuestOrderController;

Route::get('/order/guest', [GuestOrderController::class, 'showForm'])->name('guest.order.form');
Route::post('/order/guest', [GuestOrderController::class, 'store'])->middleware('throttle:writes')->name('guest.order.store');
Route::get('/api/masjids/search', [GuestOrderController::class, 'searchMasjids'])->middleware('throttle:writes')->name('api.masjids.search');

// Frontdesk Guest Order Management (Auth required)
Route::middleware(['auth', 'role:frontdesk,admin'])->prefix('frontdesk/guest-orders')->group(function (): void {
    Route::get('/', [GuestOrderController::class, 'index'])->name('frontdesk.guest-orders');
    Route::get('/{order}', [GuestOrderController::class, 'show'])->name('frontdesk.guest-orders.show');
    Route::post('/{order}/approve', [GuestOrderController::class, 'approve'])->name('frontdesk.guest-orders.approve');
    Route::post('/{order}/reject', [GuestOrderController::class, 'reject'])->name('frontdesk.guest-orders.reject');
});

// Technician Job Completion (Auth required)
use App\Http\Controllers\TechnicianController;

Route::middleware(['auth', 'role:technician'])->prefix('technician')->group(function (): void {
    Route::get('/orders/{serviceOrder}/complete', [TechnicianController::class, 'jobCompletionForm'])->name('technician.orders.complete.form');
    Route::post('/orders/{serviceOrder}/complete', [TechnicianController::class, 'completeJob'])->name('technician.orders.complete');
});

// Manager Approvals (Auth required)
use App\Http\Controllers\ManagerApprovalController;

Route::middleware(['auth', 'role:manager,admin', 'throttle:writes'])->prefix('manager')->group(function (): void {
    Route::get('/approvals', [ManagerApprovalController::class, 'index'])->name('manager.approvals');
    Route::post('/approvals/{order}/approve', [ManagerApprovalController::class, 'approve'])->name('manager.approvals.approve');
    Route::post('/approvals/{order}/reject', [ManagerApprovalController::class, 'reject'])->name('manager.approvals.reject');
});

// Frontdesk Invoice Editor (Auth required)
use App\Http\Controllers\InvoiceController;

Route::middleware(['auth', 'role:frontdesk,admin', 'throttle:writes'])->prefix('frontdesk/invoices')->group(function (): void {
    Route::get('/{invoice}/edit', [InvoiceController::class, 'showEditor'])->name('frontdesk.invoices.edit');
    Route::post('/{invoice}/edit', [InvoiceController::class, 'editInvoice'])->name('frontdesk.invoices.edit.save');
});

// Payment Verification (Auth required - Manager/Admin)
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReceiptController;

Route::middleware(['auth', 'role:manager,admin', 'throttle:writes'])->prefix('payments')->group(function (): void {
    Route::get('/', [PaymentController::class, 'index'])->name('manager.payments');
    Route::post('/{order}/verify-cash', [PaymentController::class, 'verifyCash'])->name('payments.verify-cash');
    Route::post('/{order}/confirm-cash', [PaymentController::class, 'confirmCash'])->name('payments.confirm-cash');
    Route::post('/{order}/verify-transfer', [PaymentController::class, 'verifyTransfer'])->name('payments.verify-transfer');
    Route::post('/{order}/verify-qris', [PaymentController::class, 'verifyQris'])->name('payments.verify-qris');
});

// Receipts (Auth required - Manager/Admin/Frontdesk)
Route::middleware(['auth', 'role:manager,admin,frontdesk'])->prefix('receipts')->group(function (): void {
    Route::get('/', [ReceiptController::class, 'index'])->name('manager.receipts');
    Route::get('/{receipt}', [ReceiptController::class, 'show'])->name('manager.receipts.show');
    Route::get('/{receipt}/print', [ReceiptController::class, 'print'])->name('receipts.print');
});
