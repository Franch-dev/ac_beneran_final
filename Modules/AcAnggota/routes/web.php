<?php

use App\Models\Anggota;
use App\Models\AnggotaAcUnit;
use App\Models\AnggotaServiceOrder;
use Illuminate\Support\Facades\Route;
use Modules\AcAnggota\Http\Controllers\AcAnggotaGuestOrderController;
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

    $masjids = Anggota::query()
        ->orderBy('name')
        ->get(['id', 'name', 'custom_id']);

    return view('ac-service::guest-order', [
        'masjids' => $masjids,
        'moduleName' => 'AC Anggota',
        'returnRoute' => 'modules.ac-anggota.index',
        'formActionRoute' => 'modules.ac-anggota.guest-order.store',
    ]);
})->name('modules.ac-anggota.guest-order.index');

Route::post('/modules/ac-anggota/guest-order', [AcAnggotaGuestOrderController::class, 'store'])
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
    Route::get('/database', function () {
        try {
            $rows = Anggota::query()->withCount(['acUnits', 'serviceOrders'])->orderBy('name')->get();
            $pageError = null;
        } catch (\Throwable $exception) {
            report($exception);
            $rows = collect();
            $pageError = 'Database AC Anggota gagal dimuat. Halaman lain tetap aman.';
        }

        return view('modules.module-page-shell', [
            'pageTitle' => 'Database - AC Anggota',
            'moduleLabel' => 'AC Anggota',
            'pageIcon' => 'fas fa-database',
            'pageHeading' => 'Database Modul Anggota',
            'pageDescription' => 'Ringkasan database domain anggota dipisah penuh dari domain masjid.',
            'tableTitle' => 'Database Summary',
            'tableDescription' => 'Error di halaman ini tidak menabrak dashboard, monitoring, atau modul lain.',
            'rows' => $rows,
            'pageError' => $pageError,
            'columns' => [
                ['label' => 'Anggota', 'render' => fn ($row) => e($row->name)],
                ['label' => 'Custom ID', 'render' => fn ($row) => e($row->custom_id)],
                ['label' => 'Unit AC', 'render' => fn ($row) => e((string) $row->ac_units_count)],
                ['label' => 'Service Orders', 'render' => fn ($row) => e((string) $row->service_orders_count)],
            ],
        ]);
    })->name('modules.ac-anggota.database');
    Route::get('/service-orders', function () {
        try {
            $rows = AnggotaServiceOrder::query()->with('anggota:id,name,custom_id')->latest('created_at')->limit(100)->get();
            $pageError = null;
        } catch (\Throwable $exception) {
            report($exception);
            $rows = collect();
            $pageError = 'Service order anggota gagal dimuat. Isolasi halaman aktif.';
        }

        return view('modules.module-page-shell', [
            'pageTitle' => 'Service Orders - AC Anggota',
            'moduleLabel' => 'AC Anggota',
            'pageIcon' => 'fas fa-clipboard-list',
            'pageHeading' => 'Service Orders Anggota',
            'pageDescription' => 'Daftar order khusus domain anggota.',
            'tableTitle' => 'Anggota Service Orders',
            'tableDescription' => 'Halaman ini hanya menyentuh tabel order anggota.',
            'rows' => $rows,
            'pageError' => $pageError,
            'columns' => [
                ['label' => 'Order', 'render' => fn ($row) => e($row->order_number)],
                ['label' => 'Anggota', 'render' => fn ($row) => e($row->anggota?->name ?? '-')],
                ['label' => 'Tanggal', 'render' => fn ($row) => e(optional($row->service_date)->format('d M Y') ?? '-')],
                ['label' => 'Status', 'render' => fn ($row) => e($row->status)],
            ],
        ]);
    })->name('modules.ac-anggota.service-orders');
    Route::get('/anggota-data', function () {
        try {
            $rows = Anggota::query()->orderBy('name')->get();
            $pageError = null;
        } catch (\Throwable $exception) {
            report($exception);
            $rows = collect();
            $pageError = 'Data anggota gagal dimuat. Domain masjid tidak terpengaruh.';
        }

        return view('modules.module-page-shell', [
            'pageTitle' => 'Data Anggota - AC Anggota',
            'moduleLabel' => 'AC Anggota',
            'pageIcon' => 'fas fa-users',
            'pageHeading' => 'Data Anggota',
            'pageDescription' => 'Halaman data master anggota dipisah dari AC dan service orders.',
            'tableTitle' => 'Master Anggota',
            'tableDescription' => 'Split page untuk data anggota.',
            'rows' => $rows,
            'pageError' => $pageError,
            'columns' => [
                ['label' => 'Nama', 'render' => fn ($row) => e($row->name)],
                ['label' => 'Custom ID', 'render' => fn ($row) => e($row->custom_id)],
                ['label' => 'Status', 'render' => fn ($row) => e($row->membership_status ?? '-')],
                ['label' => 'Alamat', 'render' => fn ($row) => e($row->address ?? '-')],
            ],
        ]);
    })->name('modules.ac-anggota.anggota-data');
    Route::get('/ac-data', function () {
        try {
            $rows = AnggotaAcUnit::query()->with('anggota:id,name,custom_id')->latest('updated_at')->limit(150)->get();
            $pageError = null;
        } catch (\Throwable $exception) {
            report($exception);
            $rows = collect();
            $pageError = 'Data AC anggota gagal dimuat. Halaman lain tetap aman.';
        }

        return view('modules.module-page-shell', [
            'pageTitle' => 'Data AC - AC Anggota',
            'moduleLabel' => 'AC Anggota',
            'pageIcon' => 'fas fa-snowflake',
            'pageHeading' => 'Data AC Anggota',
            'pageDescription' => 'Inventori AC khusus anggota, dipisah dari masjid dan service order.',
            'tableTitle' => 'Unit AC Anggota',
            'tableDescription' => 'Split page untuk inventori AC anggota.',
            'rows' => $rows,
            'pageError' => $pageError,
            'columns' => [
                ['label' => 'Anggota', 'render' => fn ($row) => e($row->anggota?->name ?? '-')],
                ['label' => 'PK', 'render' => fn ($row) => e($row->pk_type)],
                ['label' => 'Brand', 'render' => fn ($row) => e($row->brand)],
                ['label' => 'Qty', 'render' => fn ($row) => e((string) $row->quantity)],
            ],
        ]);
    })->name('modules.ac-anggota.ac-data');
    Route::get('/monitoring', [AcAnggotaMonitoringController::class, '__invoke'])->name('modules.ac-anggota.monitoring');

    Route::prefix('anggota')->name('anggota.')->group(function () {
        Route::post('/', [Modules\AcAnggota\Http\Controllers\AnggotaController::class, 'store'])->name('store');
        Route::get('{anggota}', [Modules\AcAnggota\Http\Controllers\AnggotaController::class, 'detail'])->name('detail');
        Route::match(['put', 'patch'], '{anggota}', [Modules\AcAnggota\Http\Controllers\AnggotaController::class, 'update'])->name('update');
        Route::delete('{anggota}', [Modules\AcAnggota\Http\Controllers\AnggotaController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('ac')->name('ac.')->group(function () {
        Route::post('/bulk', [Modules\AcAnggota\Http\Controllers\AcAnggotaController::class, 'bulkStore'])->name('bulk');
        Route::match(['put', 'patch'], '{ac}', [Modules\AcAnggota\Http\Controllers\AcAnggotaController::class, 'update'])->name('update');
        Route::delete('{ac}', [Modules\AcAnggota\Http\Controllers\AcAnggotaController::class, 'destroy'])->name('destroy');
    });
});
