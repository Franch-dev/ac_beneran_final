<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use App\Models\AnggotaAcUnit;
use App\Support\PlatformNavigation;
use Illuminate\Contracts\View\View;
use Throwable;

class AcAnggotaPageController extends Controller
{
    public function index(): View
    {
        [$totalAnggota, $totalUnit] = $this->resolveMetrics();

        $dashboardPath = route('ac-anggota.dashboard', [], false);
        $monitoringPath = route('ac-anggota.monitoring', [], false);
        $dashboardUrl = auth()->check() ? route('ac-anggota.dashboard') : PlatformNavigation::loginUrl($dashboardPath);
        $monitoringUrl = auth()->check() ? route('ac-anggota.monitoring') : PlatformNavigation::loginUrl($monitoringPath);

        return view('ac-anggota.home', compact('totalAnggota', 'totalUnit', 'dashboardUrl', 'monitoringUrl'));
    }

    public function dashboard(): View
    {
        [$totalAnggota, $totalUnit] = $this->resolveMetrics();

        try {
            $sampleAnggota = Anggota::query()
                ->withCount('acUnits')
                ->orderByDesc('ac_units_count')
                ->limit(8)
                ->get(['id', 'name', 'custom_id', 'membership_status', 'address']);
        } catch (Throwable $exception) {
            report($exception);
            $sampleAnggota = collect();
        }

        return view('ac-anggota.dashboard', compact('totalAnggota', 'totalUnit', 'sampleAnggota'));
    }

    public function monitoring(): View
    {
        try {
            $units = AnggotaAcUnit::query()
                ->with('anggota:id,name,custom_id,address')
                ->orderByDesc('updated_at')
                ->limit(100)
                ->get();
        } catch (Throwable $exception) {
            report($exception);
            $units = collect();
        }

        return view('ac-anggota.monitoring', compact('units'));
    }

    protected function resolveMetrics(): array
    {
        try {
            return [
                Anggota::count(),
                (int) AnggotaAcUnit::sum('quantity'),
            ];
        } catch (Throwable $exception) {
            report($exception);

            return [0, 0];
        }
    }
}
