<?php

use App\Http\Controllers\ServiceOrderController;
use App\Models\AcUnit;
use App\Models\Masjid;
use App\Models\ServiceOrder;
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
    Route::get('/database', function () {
        try {
            $rows = Masjid::query()->withCount(['acUnits', 'serviceOrders'])->orderBy('name')->get();
            $pageError = null;
        } catch (\Throwable $exception) {
            report($exception);
            $rows = collect();
            $pageError = 'Query database modul AC Masjid & Musholla gagal. Halaman lain tetap aman.';
        }

        return view('modules.module-page-shell', [
            'pageTitle' => 'Database - AC Masjid & Musholla',
            'moduleLabel' => 'AC Masjid & Musholla',
            'pageIcon' => 'fas fa-database',
            'pageHeading' => 'Database Modul',
            'pageDescription' => 'Ringkasan database domain masjid dan musholla tanpa membawa dependency halaman lain.',
            'tableTitle' => 'Database Summary',
            'tableDescription' => 'Setiap query halaman ini berdiri sendiri. Error di sini tidak menabrak dashboard atau monitoring.',
            'rows' => $rows,
            'pageError' => $pageError,
            'columns' => [
                ['label' => 'Masjid', 'render' => fn ($row) => e($row->name)],
                ['label' => 'Custom ID', 'render' => fn ($row) => e($row->custom_id)],
                ['label' => 'Unit AC', 'render' => fn ($row) => e((string) $row->ac_units_count)],
                ['label' => 'Service Orders', 'render' => fn ($row) => e((string) $row->service_orders_count)],
            ],
        ]);
    })->name('modules.ac-masjid-musholla.database');
    Route::get('/service-orders', function () {
        try {
            $rows = ServiceOrder::query()->with('masjid:id,name,custom_id')->latest('created_at')->limit(100)->get();
            $pageError = null;
        } catch (\Throwable $exception) {
            report($exception);
            $rows = collect();
            $pageError = 'Service order masjid gagal dimuat. Isolasi halaman aktif.';
        }

        return view('modules.module-page-shell', [
            'pageTitle' => 'Service Orders - AC Masjid & Musholla',
            'moduleLabel' => 'AC Masjid & Musholla',
            'pageIcon' => 'fas fa-clipboard-list',
            'pageHeading' => 'Service Orders',
            'pageDescription' => 'Daftar order khusus domain masjid dan musholla.',
            'tableTitle' => 'Masjid Service Orders',
            'tableDescription' => 'Halaman ini hanya menyentuh tabel order domain masjid.',
            'rows' => $rows,
            'pageError' => $pageError,
            'columns' => [
                ['label' => 'Order', 'render' => fn ($row) => e($row->order_number)],
                ['label' => 'Masjid', 'render' => fn ($row) => e($row->masjid?->name ?? '-')],
                ['label' => 'Tanggal', 'render' => fn ($row) => e(optional($row->service_date)->format('d M Y') ?? '-')],
                ['label' => 'Status', 'render' => fn ($row) => e($row->status)],
            ],
        ]);
    })->name('modules.ac-masjid-musholla.service-orders');
    Route::get('/masjid-data', function () {
        try {
            $rows = Masjid::query()->orderBy('name')->get();
            $pageError = null;
        } catch (\Throwable $exception) {
            report($exception);
            $rows = collect();
            $pageError = 'Data masjid gagal dimuat. Domain lain tidak terpengaruh.';
        }

        return view('modules.module-page-shell', [
            'pageTitle' => 'Data Masjid - AC Masjid & Musholla',
            'moduleLabel' => 'AC Masjid & Musholla',
            'pageIcon' => 'fas fa-mosque',
            'pageHeading' => 'Data Masjid & Musholla',
            'pageDescription' => 'Halaman data master lokasi terpisah dari AC, monitoring, dan service order.',
            'tableTitle' => 'Master Lokasi',
            'tableDescription' => 'Split page untuk data masjid & musholla.',
            'rows' => $rows,
            'pageError' => $pageError,
            'columns' => [
                ['label' => 'Nama', 'render' => fn ($row) => e($row->name)],
                ['label' => 'Tipe', 'render' => fn ($row) => e($row->type)],
                ['label' => 'Alamat', 'render' => fn ($row) => e($row->address)],
                ['label' => 'Setup', 'render' => fn ($row) => e($row->setup_status)],
            ],
        ]);
    })->name('modules.ac-masjid-musholla.masjid-data');
    Route::get('/ac-data', function () {
        try {
            $rows = AcUnit::query()->with('masjid:id,name,custom_id')->latest('updated_at')->limit(150)->get();
            $pageError = null;
        } catch (\Throwable $exception) {
            report($exception);
            $rows = collect();
            $pageError = 'Data AC masjid gagal dimuat. Halaman lain tetap aman.';
        }

        return view('modules.module-page-shell', [
            'pageTitle' => 'Data AC - AC Masjid & Musholla',
            'moduleLabel' => 'AC Masjid & Musholla',
            'pageIcon' => 'fas fa-snowflake',
            'pageHeading' => 'Data AC Masjid & Musholla',
            'pageDescription' => 'Inventori unit AC domain masjid terpisah dari data lokasi dan order.',
            'tableTitle' => 'Unit AC',
            'tableDescription' => 'Split page untuk inventori AC masjid & musholla.',
            'rows' => $rows,
            'pageError' => $pageError,
            'columns' => [
                ['label' => 'Masjid', 'render' => fn ($row) => e($row->masjid?->name ?? '-')],
                ['label' => 'PK', 'render' => fn ($row) => e($row->pk_type)],
                ['label' => 'Brand', 'render' => fn ($row) => e($row->brand)],
                ['label' => 'Qty', 'render' => fn ($row) => e((string) $row->quantity)],
            ],
        ]);
    })->name('modules.ac-masjid-musholla.ac-data');
    Route::get('/monitoring', [AcMasjidMushollaMonitoringController::class, '__invoke'])->name('modules.ac-masjid-musholla.monitoring');
    Route::get('/monitoring/snapshot', [AcMasjidMushollaMonitoringController::class, 'snapshot'])->name('modules.ac-masjid-musholla.monitoring.snapshot');
    Route::get('/dashboard/snapshot', [AcMasjidMushollaDashboardController::class, 'snapshot'])->name('modules.ac-masjid-musholla.dashboard.snapshot');

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
