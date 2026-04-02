<?php

namespace Modules\AcAnggota\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AcUnit;
use App\Models\Masjid;
use Throwable;

class AcAnggotaDashboardController extends Controller
{
    public function __invoke()
    {
        $metrics = $this->metrics();

        return view('ac-anggota::dashboard', $metrics);
    }

    protected function metrics(): array
    {
        try {
            return [
                'totalMasjid' => Masjid::count(),
                'totalUnit' => (int) AcUnit::sum('quantity'),
                'anggotaHighlights' => Masjid::query()
                    ->withCount('acUnits')
                    ->orderByDesc('ac_units_count')
                    ->limit(8)
                    ->get(['id', 'name', 'type', 'custom_id']),
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'totalMasjid' => 0,
                'totalUnit' => 0,
                'anggotaHighlights' => collect(),
            ];
        }
    }
}
