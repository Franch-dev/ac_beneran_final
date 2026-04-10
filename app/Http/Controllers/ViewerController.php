<?php

namespace App\Http\Controllers;

use App\Models\AcUnit;
use App\Models\Invoice;
use App\Models\Masjid;
use App\Models\ServiceOrder;
use App\Support\SqlDateExpressions;
use Illuminate\Http\JsonResponse;

class ViewerController extends Controller
{
    public function dashboard()
    {
        return view('viewer.dashboard', $this->buildDashboardViewData());
    }

    public function snapshot(): JsonResponse
    {
        return response()->json([
            'html' => view('viewer.dashboard', $this->buildDashboardViewData())->render(),
        ]);
    }

    protected function buildDashboardViewData(): array
    {
        $totalMasjid = Masjid::count();
        $totalUnit = (int) AcUnit::sum('quantity');
        $totalOrders = ServiceOrder::count();
        $totalRevenue = (int) Invoice::sum('total_price');

        $unitAges = AcUnit::query()
            ->selectRaw('masjid_id, MAX(last_service_date) as max_last_service_date')
            ->groupBy('masjid_id');

        $overdueMasjids = Masjid::query()
            ->joinSub($unitAges, 'unit_ages', function ($join): void {
                $join->on('masjids.id', '=', 'unit_ages.masjid_id');
            })
            ->where(function ($query): void {
                $query->whereNull('unit_ages.max_last_service_date')
                    ->orWhereRaw(SqlDateExpressions::daysSince('unit_ages.max_last_service_date', 'ac_service').' >= 120');
            })
            ->count();

        $recentOrders = ServiceOrder::with('masjid', 'serviceDetails')
            ->latest()
            ->take(20)
            ->get();

        return compact(
            'totalMasjid',
            'totalUnit',
            'totalOrders',
            'totalRevenue',
            'overdueMasjids',
            'recentOrders'
        );
    }
}
