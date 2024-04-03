<?php

namespace Modules\AcAnggota\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Anggota;
use App\Models\AnggotaAcUnit;
use Throwable;

class AcAnggotaDashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        session(['show_role_buttons' => true, 'current_module' => 'ac-anggota']);

        $metrics = $this->metrics($request);

        return view('ac-anggota::dashboard', $metrics);
    }

    public function snapshot(Request $request)
    {
        $metrics = $this->metrics($request);

        return response()->json($metrics);
    }

    protected function metrics(Request $request): array
    {
        try {
            $searchTerm = $request->get('search');

            $anggotasQuery = Anggota::query()
                ->with(['acUnits', 'serviceOrders']);

            if ($searchTerm) {
                $anggotasQuery->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'like', "%{$searchTerm}%")
                        ->orWhere('custom_id', 'like', "%{$searchTerm}%");
                });
            }

            $anggotas = $anggotasQuery->orderBy('name')->paginate(12)->withQueryString();

            return [
                'anggotas' => $anggotas,
                'dashboardMetrics' => [
                    'total_anggota' => Anggota::count(),
                    'total_units' => (int) AnggotaAcUnit::sum('quantity'),
                ],
            ];
        } catch (Throwable $e) {
            report($e);
            return [
                'anggotas' => collect(),
                'dashboardMetrics' => [
                    'total_anggota' => 0,
                    'total_units' => 0,
                ],
            ];
        }
    }
}
