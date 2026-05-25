<?php

namespace Modules\AcMasjidMusholla\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AcUnit;
use App\Models\Masjid;
use App\Models\ServiceOrder;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Throwable;

class AcMasjidMushollaDashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        session(['show_role_buttons' => true, 'current_module' => 'ac-masjid-musholla']);

        $metrics = $this->metrics($request);

        return view('ac-masjid-musholla::dashboard', $metrics);
    }

    public function snapshot(Request $request)
    {
        $metrics = $this->metrics($request);

        return ApiResponse::raw($metrics);
    }

    protected function metrics(Request $request): array
    {
        try {
            $searchTerm = $request->get('search');

            $masjidsQuery = Masjid::query()
                ->with([
                    'acUnits',
                    'serviceOrders' => fn ($serviceOrders) => $serviceOrders
                        ->whereNull('archived_at')
                        ->latest('service_date')
                        ->latest(),
                    'serviceOrders.serviceDetails',
                ]);

            if ($searchTerm) {
                $masjidsQuery->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'like', "%{$searchTerm}%")
                        ->orWhere('custom_id', 'like', "%{$searchTerm}%");
                });
            }

            $masjids = $masjidsQuery->orderBy('name')->paginate(12)->withQueryString();
            $visibleMasjids = $masjids->getCollection();
            $visibleUnits = $visibleMasjids->sum(fn ($masjid) => $masjid->acUnits->sum('quantity'));

            // Urgency counts
            $urgencyCounts = $this->urgencyCounts();

            $dashboardMetrics = [
                'total_locations' => Masjid::count(),
                'total_units' => (int) AcUnit::sum('quantity'),
                'active_orders' => ServiceOrder::active()->count(),
                'overdue_locations' => (int) ($urgencyCounts['overdue'] ?? 0),
                'needs_attention_locations' => (int) ($urgencyCounts['needs_attention'] ?? 0),
                'pending_setup_locations' => Masjid::where('setup_status', 'pending_ac')->count(),
            ];

            return compact('masjids', 'dashboardMetrics');
        } catch (Throwable $e) {
            report($e);

            return [
                'masjids' => new LengthAwarePaginator([], 0, 12),
                'dashboardMetrics' => [
                    'total_locations' => 0,
                    'total_units' => 0,
                    'active_orders' => 0,
                    'overdue_locations' => 0,
                    'needs_attention_locations' => 0,
                    'pending_setup_locations' => 0,
                ],
            ];
        }
    }

    protected function urgencyCounts(): array
    {
        $unitAges = AcUnit::query()
            ->selectRaw('masjid_id, COUNT(*) as unit_count, MAX(last_service_date) as max_last_service_date')
            ->groupBy('masjid_id');

        $overdue = Masjid::query()
            ->joinSub($unitAges, 'unit_ages', function ($join): void {
                $join->on('masjids.id', '=', 'unit_ages.masjid_id');
            })
            ->whereRaw('DATEDIFF(NOW(), COALESCE(unit_ages.max_last_service_date, masjids.created_at)) > 120')
            ->count();

        $needsAttention = Masjid::query()
            ->joinSub($unitAges, 'unit_ages', function ($join): void {
                $join->on('masjids.id', '=', 'unit_ages.masjid_id');
            })
            ->whereRaw('DATEDIFF(NOW(), COALESCE(unit_ages.max_last_service_date, masjids.created_at)) BETWEEN 90 AND 120')
            ->count();

        return [
            'overdue' => $overdue,
            'needs_attention' => $needsAttention,
        ];
    }
}
