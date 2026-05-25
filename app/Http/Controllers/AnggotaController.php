<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use App\Models\AnggotaAcUnit;
use App\Models\AnggotaServiceOrder;
use App\Support\ApiResponse;
use App\Support\RealtimeSync;
use App\Support\SqlDateExpressions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnggotaController extends Controller
{
    public function index(Request $request)
    {
        return view('ac-anggota::dashboard', $this->buildDashboardViewData($request));
    }

    public function snapshot(Request $request): JsonResponse
    {
        return ApiResponse::snapshot(
            view('ac-anggota::dashboard', $this->buildDashboardViewData($request))->render()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'member_code' => 'nullable|string|max:255',
            'registered_at' => 'nullable|date',
            'name' => 'required|string|max:255',
            'gender' => 'nullable|string|max:50',
            'family_card_number' => 'nullable|string|max:255',
            'national_id_number' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date',
            'family_role' => 'nullable|string|max:255',
            'membership_status' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:50',
            'whatsapp_number' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'location' => 'nullable|string|max:255',
            'street' => 'nullable|string|max:255',
            'house_number' => 'nullable|string|max:50',
            'rt' => 'nullable|string|max:20',
            'rw' => 'nullable|string|max:20',
            'subdistrict' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'address' => 'required|string',
            'contact_name' => 'required|string|max:255',
            'phone_numbers' => 'required|array|min:1',
            'phone_numbers.*' => 'required|string',
        ]);

        $anggota = null;

        DB::connection('ac_service')->transaction(function () use ($validated, &$anggota): void {
            $anggota = Anggota::create([
                'custom_id' => Anggota::generateCustomId(),
                'member_code' => $validated['member_code'] ?? null,
                'registered_at' => $validated['registered_at'] ?? null,
                'name' => $validated['name'],
                'gender' => $validated['gender'] ?? null,
                'family_card_number' => $validated['family_card_number'] ?? null,
                'national_id_number' => $validated['national_id_number'] ?? null,
                'birth_date' => $validated['birth_date'] ?? null,
                'family_role' => $validated['family_role'] ?? null,
                'membership_status' => $validated['membership_status'] ?? null,
                'phone_number' => $validated['phone_number'] ?? null,
                'whatsapp_number' => $validated['whatsapp_number'] ?? null,
                'email' => $validated['email'] ?? null,
                'location' => $validated['location'] ?? null,
                'street' => $validated['street'] ?? null,
                'house_number' => $validated['house_number'] ?? null,
                'rt' => $validated['rt'] ?? null,
                'rw' => $validated['rw'] ?? null,
                'subdistrict' => $validated['subdistrict'] ?? null,
                'district' => $validated['district'] ?? null,
                'city' => $validated['city'] ?? null,
                'province' => $validated['province'] ?? null,
                'address' => $validated['address'],
                'contact_name' => $validated['contact_name'],
                'phone_numbers' => array_values(array_filter($validated['phone_numbers'])),
                'setup_status' => 'pending_ac',
            ]);

            RealtimeSync::afterCommit('anggota.created', [
                'resource' => 'anggota',
                'resource_id' => $anggota->id,
                'anggota_id' => $anggota->id,
                'payload' => [
                    'setup_status' => $anggota->setup_status,
                ],
            ]);
        });

        return ApiResponse::success([
            'anggota' => $anggota->fresh(),
            'custom_id' => $anggota->custom_id,
        ]);
    }

    public function update(Request $request, Anggota $anggota): JsonResponse
    {
        $validated = $request->validate([
            'member_code' => 'nullable|string|max:255',
            'registered_at' => 'nullable|date',
            'name' => 'required|string|max:255',
            'gender' => 'nullable|string|max:50',
            'family_card_number' => 'nullable|string|max:255',
            'national_id_number' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date',
            'family_role' => 'nullable|string|max:255',
            'membership_status' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:50',
            'whatsapp_number' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'location' => 'nullable|string|max:255',
            'street' => 'nullable|string|max:255',
            'house_number' => 'nullable|string|max:50',
            'rt' => 'nullable|string|max:20',
            'rw' => 'nullable|string|max:20',
            'subdistrict' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'address' => 'required|string',
            'contact_name' => 'required|string|max:255',
            'phone_numbers' => 'required|array|min:1',
            'phone_numbers.*' => 'required|string',
        ]);

        DB::connection('ac_service')->transaction(function () use ($validated, $anggota): void {
            $anggota->update([
                'member_code' => $validated['member_code'] ?? null,
                'registered_at' => $validated['registered_at'] ?? null,
                'name' => $validated['name'],
                'gender' => $validated['gender'] ?? null,
                'family_card_number' => $validated['family_card_number'] ?? null,
                'national_id_number' => $validated['national_id_number'] ?? null,
                'birth_date' => $validated['birth_date'] ?? null,
                'family_role' => $validated['family_role'] ?? null,
                'membership_status' => $validated['membership_status'] ?? null,
                'phone_number' => $validated['phone_number'] ?? null,
                'whatsapp_number' => $validated['whatsapp_number'] ?? null,
                'email' => $validated['email'] ?? null,
                'location' => $validated['location'] ?? null,
                'street' => $validated['street'] ?? null,
                'house_number' => $validated['house_number'] ?? null,
                'rt' => $validated['rt'] ?? null,
                'rw' => $validated['rw'] ?? null,
                'subdistrict' => $validated['subdistrict'] ?? null,
                'district' => $validated['district'] ?? null,
                'city' => $validated['city'] ?? null,
                'province' => $validated['province'] ?? null,
                'address' => $validated['address'],
                'contact_name' => $validated['contact_name'],
                'phone_numbers' => array_values(array_filter($validated['phone_numbers'])),
            ]);

            RealtimeSync::afterCommit('anggota.updated', [
                'resource' => 'anggota',
                'resource_id' => $anggota->id,
                'anggota_id' => $anggota->id,
            ]);
        });

        return ApiResponse::success([
            'anggota' => $anggota->fresh(),
        ]);
    }

    public function destroy(Anggota $anggota): JsonResponse
    {
        $anggotaId = $anggota->id;

        DB::connection('ac_service')->transaction(function () use ($anggota): void {
            $anggota->delete();

            RealtimeSync::afterCommit('anggota.deleted', [
                'resource' => 'anggota',
                'resource_id' => $anggota->id,
                'anggota_id' => $anggota->id,
            ]);
        });

        return ApiResponse::success([
            'anggota_id' => $anggotaId,
        ]);
    }

    public function detail(Anggota $anggota): JsonResponse
    {
        $anggota->load([
            'acUnits:id,anggota_id,pk_type,brand,quantity,last_service_date',
            'serviceOrders' => fn ($serviceOrders) => $serviceOrders
                ->select(['id', 'anggota_id', 'order_number', 'service_date', 'status', 'created_at'])
                ->latest('service_date')
                ->latest(),
        ]);

        return ApiResponse::raw($anggota);
    }

    protected function buildDashboardViewData(Request $request): array
    {
        $query = Anggota::query()
            ->select(['id', 'custom_id', 'name', 'address', 'contact_name', 'phone_numbers', 'setup_status', 'setup_completed_at', 'created_at'])
            ->with([
            'acUnits:id,anggota_id,quantity,last_service_date',
            'serviceOrders' => fn ($serviceOrders) => $serviceOrders
                ->select(['id', 'anggota_id', 'order_number', 'service_date', 'status', 'created_at'])
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

        $anggotas = $query->latest()->paginate(30)->withQueryString();
        $urgencyCounts = $this->urgencyCounts();

        $dashboardMetrics = [
            'total_anggota' => Anggota::count(),
            'total_units' => (int) AnggotaAcUnit::sum('quantity'),
            'active_orders' => AnggotaServiceOrder::active()->count(),
            'overdue' => (int) ($urgencyCounts['overdue'] ?? 0),
            'needs_attention' => (int) ($urgencyCounts['needs_attention'] ?? 0),
            'pending_setup' => Anggota::where('setup_status', 'pending_ac')->count(),
        ];

        return compact('anggotas', 'dashboardMetrics');
    }

    /**
     * Compute urgency counts in SQL to avoid loading all anggota+ac_units into PHP memory.
     *
     * @return array{overdue:int,needs_attention:int}
     */
    protected function urgencyCounts(): array
    {
        $unitAges = AnggotaAcUnit::query()
            ->selectRaw('anggota_id, COUNT(*) as unit_count, MAX(last_service_date) as max_last_service_date')
            ->groupBy('anggota_id');

        $overdue = Anggota::query()
            ->joinSub($unitAges, 'unit_ages', function ($join): void {
                $join->on('anggotas.id', '=', 'unit_ages.anggota_id');
            })
            ->where(function ($query): void {
                $query->whereNull('unit_ages.max_last_service_date')
                    ->orWhereRaw(SqlDateExpressions::daysSince('unit_ages.max_last_service_date', 'ac_service').' >= 120');
            })
            ->count();

        $needsAttention = Anggota::query()
            ->joinSub($unitAges, 'unit_ages', function ($join): void {
                $join->on('anggotas.id', '=', 'unit_ages.anggota_id');
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
