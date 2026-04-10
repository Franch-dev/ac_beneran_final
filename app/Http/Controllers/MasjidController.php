<?php

namespace App\Http\Controllers;

use App\Models\AcUnit;
use App\Models\Masjid;
use App\Models\ServiceOrder;
use App\Support\RealtimeSync;
use App\Support\SqlDateExpressions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasjidController extends Controller
{
    public function index(Request $request)
    {
        return view('dashboard', $this->buildDashboardViewData($request));
    }

    public function snapshot(Request $request): JsonResponse
    {
        return response()->json([
            'html' => view('dashboard', $this->buildDashboardViewData($request))->render(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:masjid,musholla',
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'dkm_name' => 'required|string|max:255',
            'marbot_name' => 'required|string|max:255',
            'phone_numbers' => 'required|array|min:1',
            'phone_numbers.*' => 'required|string',
        ]);

        $masjid = null;

        DB::connection('ac_service')->transaction(function () use ($validated, &$masjid): void {
            $masjid = Masjid::create([
                'custom_id' => Masjid::generateCustomId($validated['type']),
                'type' => $validated['type'],
                'name' => $validated['name'],
                'address' => $validated['address'],
                'dkm_name' => $validated['dkm_name'],
                'marbot_name' => $validated['marbot_name'],
                'phone_numbers' => array_values(array_filter($validated['phone_numbers'])),
                'setup_status' => 'pending_ac',
            ]);

            RealtimeSync::afterCommit('masjid.created', [
                'resource' => 'masjid',
                'resource_id' => $masjid->id,
                'masjid_id' => $masjid->id,
                'payload' => [
                    'setup_status' => $masjid->setup_status,
                ],
            ]);
        });

        return response()->json([
            'success' => true,
            'masjid' => $masjid->fresh(),
            'custom_id' => $masjid->custom_id,
        ]);
    }

    public function update(Request $request, Masjid $masjid): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'dkm_name' => 'required|string|max:255',
            'marbot_name' => 'required|string|max:255',
            'phone_numbers' => 'required|array|min:1',
            'phone_numbers.*' => 'required|string',
        ]);

        DB::connection('ac_service')->transaction(function () use ($validated, $masjid): void {
            $masjid->update([
                'name' => $validated['name'],
                'address' => $validated['address'],
                'dkm_name' => $validated['dkm_name'],
                'marbot_name' => $validated['marbot_name'],
                'phone_numbers' => array_values(array_filter($validated['phone_numbers'])),
            ]);

            RealtimeSync::afterCommit('masjid.updated', [
                'resource' => 'masjid',
                'resource_id' => $masjid->id,
                'masjid_id' => $masjid->id,
            ]);
        });

        return response()->json([
            'success' => true,
            'masjid' => $masjid->fresh(),
        ]);
    }

    public function destroy(Masjid $masjid): JsonResponse
    {
        $masjidId = $masjid->id;

        DB::connection('ac_service')->transaction(function () use ($masjid): void {
            $masjid->delete();

            RealtimeSync::afterCommit('masjid.deleted', [
                'resource' => 'masjid',
                'resource_id' => $masjid->id,
                'masjid_id' => $masjid->id,
            ]);
        });

        return response()->json([
            'success' => true,
            'masjid_id' => $masjidId,
        ]);
    }

    public function detail(Masjid $masjid): JsonResponse
    {
        $masjid->load([
            'acUnits:id,masjid_id,pk_type,brand,quantity,last_service_date',
            'serviceOrders' => fn ($serviceOrders) => $serviceOrders
                ->select(['id', 'masjid_id', 'order_number', 'service_date', 'status', 'created_at'])
                ->latest('service_date')
                ->latest(),
        ]);

        return response()->json($masjid);
    }

    protected function buildDashboardViewData(Request $request): array
    {
        $query = Masjid::query()
            ->select(['id', 'custom_id', 'type', 'name', 'address', 'dkm_name', 'marbot_name', 'phone_numbers', 'setup_status', 'setup_completed_at', 'created_at'])
            ->with([
            'acUnits:id,masjid_id,quantity,last_service_date',
            'serviceOrders' => fn ($serviceOrders) => $serviceOrders
                ->select(['id', 'masjid_id', 'order_number', 'service_date', 'status', 'created_at'])
                ->latest('service_date')
                ->latest(),
        ]);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('custom_id', 'like', "%{$search}%");
            });
        }

        $masjids = $query->latest()->paginate(30)->withQueryString();
        $urgencyCounts = $this->urgencyCounts();

        $dashboardMetrics = [
            'total_locations' => Masjid::count(),
            'total_units' => (int) AcUnit::sum('quantity'),
            'active_orders' => ServiceOrder::active()->count(),
            'overdue_locations' => (int) ($urgencyCounts['overdue'] ?? 0),
            'needs_attention_locations' => (int) ($urgencyCounts['needs_attention'] ?? 0),
            'pending_setup_locations' => Masjid::where('setup_status', 'pending_ac')->count(),
            'type_totals' => Masjid::query()
                ->select('type')
                ->selectRaw('count(*) as total')
                ->groupBy('type')
                ->pluck('total', 'type'),
        ];

        return compact('masjids', 'dashboardMetrics');
    }

    /**
     * Compute urgency counts in SQL to avoid loading all masjid+ac_units into PHP memory.
     *
     * @return array{overdue:int,needs_attention:int}
     */
    protected function urgencyCounts(): array
    {
        $unitAges = AcUnit::query()
            ->selectRaw('masjid_id, COUNT(*) as unit_count, MAX(last_service_date) as max_last_service_date')
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

        $needsAttention = Masjid::query()
            ->joinSub($unitAges, 'unit_ages', function ($join): void {
                $join->on('masjids.id', '=', 'unit_ages.masjid_id');
            })
            ->where(function ($query): void {
                $query->whereNull('unit_ages.max_last_service_date')
                    ->orWhereRaw(SqlDateExpressions::daysSince('unit_ages.max_last_service_date', 'ac_service').' >= 90');
            })
            ->count();

        return [
            'overdue' => $overdue,
            'needs_attention' => $needsAttention,
        ];
    }
}
