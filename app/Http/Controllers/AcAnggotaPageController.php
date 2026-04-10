<?php

namespace App\Http\Controllers;

use App\Models\AcUnit;
use App\Models\Masjid;
use Illuminate\Contracts\View\View;
use Throwable;

class AcAnggotaPageController extends Controller
{
    public function index(): View
    {
        [$totalAnggota, $totalUnit] = $this->resolveMetrics();

        return view('ac-anggota.home', compact('totalAnggota', 'totalUnit'));
    }

    public function dashboard(): View
    {
        [$totalAnggota, $totalUnit] = $this->resolveMetrics();

        try {
            $sampleAnggota = Masjid::query()
                ->withCount('acUnits')
                ->orderByDesc('ac_units_count')
                ->limit(8)
                ->get(['id', 'name', 'type', 'custom_id']);
        } catch (Throwable $exception) {
            report($exception);
            $sampleAnggota = collect();
        }

        return view('ac-anggota.dashboard', compact('totalAnggota', 'totalUnit', 'sampleAnggota'));
    }

    public function monitoring(): View
    {
        try {
            $units = AcUnit::query()
                ->with('masjid:id,name,type,custom_id')
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
                Masjid::count(),
                (int) AcUnit::sum('quantity'),
            ];
        } catch (Throwable $exception) {
            report($exception);

            return [0, 0];
        }
    }
}
