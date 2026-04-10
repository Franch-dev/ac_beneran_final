<?php

namespace App\Http\Controllers;

use App\Models\AcUnit;
use App\Models\Masjid;
use App\Models\ServiceOrder;
use App\Support\SqlDateExpressions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MonitoringController extends Controller
{
    public function index(Request $request)
    {
        return view('monitoring', $this->buildMonitoringViewData($request));
    }

    public function snapshot(Request $request): JsonResponse
    {
        return response()->json([
            'html' => view('monitoring', $this->buildMonitoringViewData($request))->render(),
        ]);
    }

    public function statusCounts(): JsonResponse
    {
        $counts = Cache::remember('monitoring:status_counts', now()->addSeconds(20), function () {
            return ServiceOrder::query()
                ->select('status')
                ->selectRaw('count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');
        });

        return response()->json($counts);
    }

    protected function buildMonitoringViewData(Request $request): array
    {
        $query = ServiceOrder::query()
            ->select(['id', 'masjid_id', 'order_number', 'service_date', 'status', 'created_at'])
            ->with([
                'masjid:id,custom_id,name',
                'masjid.acUnits:id,masjid_id,quantity,last_service_date',
                'serviceDetails:id,service_order_id,pk_type,brand,quantity',
                'invoice:id,service_order_id,invoice_number',
                'latestWorkflowStep',
                'technicianAssignment:id,service_order_id,technician_id,technician_name,status,started_at,completed_at,technician_notes',
            ]);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($innerQuery) use ($search): void {
                $innerQuery->whereHas('masjid', function ($builder) use ($search): void {
                    $builder->where('name', 'like', "%{$search}%")
                        ->orWhere('custom_id', 'like', "%{$search}%");
                })->orWhere('order_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $orders = $query->latest()->paginate(30)->withQueryString();
        $statusTotals = Cache::remember('monitoring:status_totals', now()->addSeconds(20), function () {
            return ServiceOrder::query()
                ->select('status')
                ->selectRaw('count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');
        });

        $totalLokasi = Masjid::count();
        $totalUnit = (int) AcUnit::sum('quantity');
        $unitAges = AcUnit::query()
            ->selectRaw('masjid_id, MAX(last_service_date) as max_last_service_date')
            ->groupBy('masjid_id');

        $overdue = Masjid::query()
            ->joinSub($unitAges, 'unit_ages', function ($join): void {
                $join->on('masjids.id', '=', 'unit_ages.masjid_id');
            })
            ->where(function ($query): void {
                $query->whereNull('unit_ages.max_last_service_date')
                    ->orWhereRaw(SqlDateExpressions::daysSince('unit_ages.max_last_service_date', 'ac_service').' >= 120');
            })
            ->count();

        $masjids = Masjid::query()
            ->select(['id', 'custom_id', 'name', 'type', 'address', 'dkm_name', 'marbot_name', 'phone_numbers', 'setup_status', 'setup_completed_at', 'created_at'])
            ->with(['acUnits:id,masjid_id,quantity,last_service_date,pk_type,brand'])
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return compact('orders', 'totalLokasi', 'totalUnit', 'overdue', 'masjids', 'statusTotals');
    }
}
