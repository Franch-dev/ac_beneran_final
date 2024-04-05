<?php

use App\Http\Controllers\ACController;
use App\Http\Controllers\AdminLogController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MasjidController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\ServiceOrderController;
use App\Http\Controllers\SyncController;
use App\Models\Masjid;
use Illuminate\Support\Facades\Route;
use Modules\AcService\Http\Controllers\AcServiceHomeController;

Route::get('/modules/ac-service', [AcServiceHomeController::class, '__invoke'])->name('modules.ac-service.index');

Route::get('/modules/ac-service/guest-order', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    $masjids = Masjid::query()
        ->orderBy('name')
        ->get(['id', 'name', 'custom_id']);

    return view('ac-service::guest-order', compact('masjids'));
})->name('modules.ac-service.guest-order.index');

Route::post('/modules/ac-service/guest-order', [ServiceOrderController::class, 'guestStore'])
    ->name('modules.ac-service.guest-order.store');

$module = collect(config('modules.catalog', []))->firstWhere('key', 'ac-service');
$domain = $module['subdomain'] ?? null;

if ($domain) {
    Route::domain($domain)->group(function (): void {
        Route::get('/', [AcServiceHomeController::class, '__invoke'])->name('modules.ac-service.subdomain.index');
    });
}

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [MasjidController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/snapshot', [MasjidController::class, 'snapshot'])->name('dashboard.snapshot');
    Route::get('/monitoring', [MonitoringController::class, 'index'])->name('monitoring');
    Route::get('/monitoring/snapshot', [MonitoringController::class, 'snapshot'])->name('monitoring.snapshot');
    Route::get('/monitoring/status-counts', [MonitoringController::class, 'statusCounts'])->name('monitoring.status-counts');
    Route::get('/sync/stream', [SyncController::class, 'stream'])->name('sync.stream');

    Route::get('/masjid/{masjid}', [MasjidController::class, 'detail'])->name('masjid.detail');
    Route::get('/masjid/{masjid}/history', [ServiceOrderController::class, 'history'])->name('service-order.history');
    Route::get('/service-order/{serviceOrder}', [ServiceOrderController::class, 'show'])->name('service-order.show');

    Route::middleware(['role:frontdesk,admin', 'throttle:writes'])->group(function () {
        Route::post('/masjid', [MasjidController::class, 'store'])->name('masjid.store');
        Route::put('/masjid/{masjid}', [MasjidController::class, 'update'])->name('masjid.update');
        Route::delete('/masjid/{masjid}', [MasjidController::class, 'destroy'])->name('masjid.destroy');

        Route::post('/masjid/{masjid}/ac', [ACController::class, 'store'])->name('ac.store');
        Route::post('/ac/bulk', [ACController::class, 'bulkStore'])->name('ac.bulk');
        Route::put('/ac/{acUnit}', [ACController::class, 'update'])->name('ac.update');
        Route::delete('/ac/{acUnit}', [ACController::class, 'destroy'])->name('ac.destroy');

        Route::post('/service-order', [ServiceOrderController::class, 'store'])->name('service-order.store');
        Route::delete('/service-order/{serviceOrder}', [ServiceOrderController::class, 'destroy'])->name('service-order.destroy');
        Route::post('/service-order/{serviceOrder}/invoice', [ServiceOrderController::class, 'generateInvoice'])->name('service-order.invoice-generate');
    });

    Route::middleware(['role:manager,admin', 'throttle:writes'])->group(function () {
        Route::post('/service-order/{serviceOrder}/approve', [ServiceOrderController::class, 'approve'])->name('service-order.approve');
        Route::post('/service-order/{serviceOrder}/cancel-approve', [ServiceOrderController::class, 'cancelApprove'])->name('service-order.cancel-approve');
        Route::delete('/service-order/{serviceOrder}/manager', [ServiceOrderController::class, 'destroy'])->name('service-order.destroy-manager');
        Route::post('/service-order/{serviceOrder}/approve-invoice', [ServiceOrderController::class, 'approveInvoice'])->name('service-order.approve-invoice');
    });

    Route::middleware('role:frontdesk,manager,admin')->group(function () {
        Route::get('/service-order/{serviceOrder}/spk', [InvoiceController::class, 'spk'])->name('spk.print');
        Route::get('/service-order/{serviceOrder}/invoice', [InvoiceController::class, 'print'])->name('invoice.print');
    });
});

// New routes appended here

use App\Http\Controllers\MasjidHistoryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TechnicianController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ViewerController;
use App\Http\Controllers\WorkflowController;

/*
|--------------------------------------------------------------------------
| All-auth routes (profile accessible by every logged-in role)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    // ── Profile ──────────────────────────────────────────────
    Route::get('/profile',          [ProfileController::class, 'index'])->name('profile.index');
    Route::put('/profile',          [ProfileController::class, 'update'])->middleware('throttle:writes')->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->middleware('throttle:writes')->name('profile.password');

    // ── Masjid Service History (dedicated page) ───────────────
    Route::get('/masjid/{masjid}/history-page', [MasjidHistoryController::class, 'show'])
         ->name('masjid.history.show');

    // ── Workflow: timeline (read — all auth roles) ────────────
    Route::get('/workflow/{serviceOrder}/timeline',  [WorkflowController::class, 'timeline'])
         ->name('workflow.timeline');
    Route::get('/workflow/technicians',              [WorkflowController::class, 'technicians'])
         ->name('workflow.technicians');
});

/*
|--------------------------------------------------------------------------
| Admin-only routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin'])->group(function () {

    // ── User Management ───────────────────────────────────────
    Route::get   ('/users',                  [UserController::class, 'index'])->name('users.index');
    Route::post  ('/users',                  [UserController::class, 'store'])->middleware('throttle:writes')->name('users.store');
    Route::put   ('/users/{user}',           [UserController::class, 'update'])->middleware('throttle:writes')->name('users.update');
    Route::put   ('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->middleware('throttle:writes')->name('users.reset-password');
    Route::delete('/users/{user}',           [UserController::class, 'destroy'])->middleware('throttle:writes')->name('users.destroy');
    Route::get('/admin/logs',                [AdminLogController::class, 'index'])->name('admin.logs.index');
});

/*
|--------------------------------------------------------------------------
| Manager + Admin routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:manager,admin'])->group(function () {

    // ── Reports ───────────────────────────────────────────────
    Route::get('/reports',        [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'exportJson'])->name('reports.export');

    // ── Workflow: assign + close ──────────────────────────────
    Route::post('/workflow/{serviceOrder}/assign', [WorkflowController::class, 'assign'])
         ->middleware('throttle:writes')
         ->name('workflow.assign');
    Route::post('/workflow/{serviceOrder}/close',  [WorkflowController::class, 'close'])
         ->middleware('throttle:writes')
         ->name('workflow.close');
});

/*
|--------------------------------------------------------------------------
| Technician-only routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:technician'])->group(function () {

    Route::get ('/technician',                          [TechnicianController::class, 'dashboard'])->name('technician.dashboard');
    Route::get ('/technician/snapshot',                 [TechnicianController::class, 'snapshot'])->name('technician.snapshot');
    Route::get ('/technician/spk/{serviceOrder}',       [TechnicianController::class, 'spkView'])->name('technician.spk');
    Route::get ('/technician/invoice/{serviceOrder}',    [TechnicianController::class, 'invoiceView'])->name('technician.invoice');
    Route::post('/workflow/{serviceOrder}/progress',    [WorkflowController::class, 'updateProgress'])
         ->middleware('throttle:writes')
         ->name('workflow.progress');
});

/*
|--------------------------------------------------------------------------
| Viewer/Auditor-only routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:viewer'])->group(function () {

    Route::get('/viewer', [ViewerController::class, 'dashboard'])->name('viewer.dashboard');
    Route::get('/viewer/snapshot', [ViewerController::class, 'snapshot'])->name('viewer.snapshot');
});


