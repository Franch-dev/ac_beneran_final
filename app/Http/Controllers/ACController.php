<?php

namespace App\Http\Controllers;

use App\Models\AcUnit;
use App\Models\Masjid;
use App\Support\ApiResponse;
use App\Support\RealtimeSync;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ACController extends Controller
{
    public function store(Request $request, Masjid $masjid): JsonResponse
    {
        $validated = $this->validateUnits($request);

        return $this->storeUnitsForMasjid($masjid, $validated['units']);
    }

    public function update(Request $request, AcUnit $acUnit): JsonResponse
    {
        $validated = $request->validate([
            'brand' => ['required', 'string', 'max:100', 'regex:/^[\pL\pN\s.\-+\/()]+$/u'],
            'quantity' => 'required|integer|min:1|max:100',
            'last_service_date' => 'nullable|date',
        ]);

        $masjid = $acUnit->masjid()->firstOrFail();

        DB::connection('ac_service')->transaction(function () use ($validated, $acUnit, $masjid): void {
            $acUnit->update($validated);
            $masjid->syncSetupStatus();

            RealtimeSync::afterCommit('ac.updated', [
                'resource' => 'ac_unit',
                'resource_id' => $acUnit->id,
                'masjid_id' => $masjid->id,
                'payload' => [
                    'setup_status' => $masjid->fresh()->setup_status,
                ],
            ]);
        });

        return ApiResponse::success([
            'ac_unit' => $acUnit->fresh(),
            'masjid' => $masjid->fresh('acUnits'),
        ]);
    }

    public function destroy(AcUnit $acUnit): JsonResponse
    {
        $masjid = $acUnit->masjid()->firstOrFail();
        $unitId = $acUnit->id;

        DB::connection('ac_service')->transaction(function () use ($acUnit, $masjid): void {
            $acUnit->delete();
            $masjid->syncSetupStatus();

            RealtimeSync::afterCommit('ac.deleted', [
                'resource' => 'ac_unit',
                'resource_id' => $acUnit->id,
                'masjid_id' => $masjid->id,
                'payload' => [
                    'setup_status' => $masjid->fresh()->setup_status,
                ],
            ]);
        });

        return ApiResponse::success([
            'ac_unit_id' => $unitId,
            'masjid' => $masjid->fresh('acUnits'),
        ]);
    }

    public function bulkStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'masjid_id' => ['required', Rule::exists('ac_service.masjids', 'id')],
            'units' => 'required|array|min:1',
            'units.*.pk_type' => 'required|in:1PK,2PK,5PK',
            'units.*.brand' => ['required', 'string', 'max:100', 'regex:/^[\pL\pN\s.\-+\/()]+$/u'],
            'units.*.quantity' => 'required|integer|min:1|max:100',
            'units.*.last_service_date' => 'nullable|date',
        ]);

        $masjid = Masjid::findOrFail($validated['masjid_id']);

        return $this->storeUnitsForMasjid($masjid, $validated['units']);
    }

    protected function validateUnits(Request $request): array
    {
        return $request->validate([
            'units' => 'required|array|min:1',
            'units.*.pk_type' => 'required|in:1PK,2PK,5PK',
            'units.*.brand' => ['required', 'string', 'max:100', 'regex:/^[\pL\pN\s.\-+\/()]+$/u'],
            'units.*.quantity' => 'required|integer|min:1|max:100',
            'units.*.last_service_date' => 'nullable|date',
        ]);
    }

    protected function storeUnitsForMasjid(Masjid $masjid, array $units): JsonResponse
    {
        DB::connection('ac_service')->transaction(function () use ($masjid, $units): void {
            foreach ($units as $unit) {
                AcUnit::create([
                    'masjid_id' => $masjid->id,
                    'pk_type' => $unit['pk_type'],
                    'brand' => $unit['brand'],
                    'quantity' => (int) $unit['quantity'],
                    'last_service_date' => $unit['last_service_date'] ?? null,
                ]);
            }

            $masjid->syncSetupStatus();

            RealtimeSync::afterCommit('ac.bulk_saved', [
                'resource' => 'masjid',
                'resource_id' => $masjid->id,
                'masjid_id' => $masjid->id,
                'payload' => [
                    'setup_status' => $masjid->fresh()->setup_status,
                ],
            ]);
        });

        return ApiResponse::success([
            'masjid' => $masjid->fresh('acUnits'),
        ]);
    }
}
