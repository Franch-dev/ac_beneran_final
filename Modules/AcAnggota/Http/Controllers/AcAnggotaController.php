<?php

namespace Modules\AcAnggota\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Anggota;
use App\Models\AnggotaAcUnit;
use App\Support\ApiResponse;
use App\Support\RealtimeSync;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Modules\AcAnggota\Http\Controllers\AnggotaController;

class AcAnggotaController extends Controller
{
    public function bulkStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'anggota_id' => ['required', Rule::exists('ac_service.anggotas', 'id')],
            'units' => 'required|array|min:1',
            'units.*.pk_type' => 'required|in:1PK,2PK,5PK',
            'units.*.brand' => ['required', 'string', 'max:100', 'regex:/^[\pL\pN\s.\-+\/()]+$/u'],
            'units.*.quantity' => 'required|integer|min:1|max:100',
            'units.*.last_service_date' => 'nullable|date',
        ]);

        $anggota = Anggota::findOrFail($validated['anggota_id']);

        return $this->storeUnitsForAnggota($anggota, $validated['units']);
    }

    public function update(Request $request, AnggotaAcUnit $acUnit): JsonResponse
    {
        $validated = $request->validate([
            'brand' => ['required', 'string', 'max:100', 'regex:/^[\pL\pN\s.\-+\/()]+$/u'],
            'quantity' => 'required|integer|min:1|max:100',
            'last_service_date' => 'nullable|date',
        ]);

        $anggota = $acUnit->anggota()->firstOrFail();

        DB::connection('ac_service')->transaction(function () use ($validated, $acUnit, $anggota): void {
            $acUnit->update($validated);
            $anggota->syncSetupStatus();

            RealtimeSync::afterCommit('ac-anggota.updated', [
                'resource' => 'ac_anggota',
                'resource_id' => $acUnit->id,
                'anggota_id' => $anggota->id,
            ]);
        });

        return ApiResponse::success([
            'ac_unit' => $acUnit->fresh(),
            'anggota' => $anggota->fresh('acUnits'),
        ]);
    }

    public function destroy(AnggotaAcUnit $acUnit): JsonResponse
    {
        $anggota = $acUnit->anggota()->firstOrFail();
        $unitId = $acUnit->id;

        DB::connection('ac_service')->transaction(function () use ($acUnit, $anggota): void {
            $acUnit->delete();
            $anggota->syncSetupStatus();

            RealtimeSync::afterCommit('ac-anggota.deleted', [
                'resource' => 'ac_anggota',
                'resource_id' => $acUnit->id,
                'anggota_id' => $anggota->id,
            ]);
        });

        return ApiResponse::success([
            'ac_unit_id' => $unitId,
            'anggota' => $anggota->fresh('acUnits'),
        ]);
    }

    protected function storeUnitsForAnggota(Anggota $anggota, array $units): JsonResponse
    {
        DB::connection('ac_service')->transaction(function () use ($anggota, $units): void {
            foreach ($units as $unit) {
                AnggotaAcUnit::create([
                    'anggota_id' => $anggota->id,
                    'anggota_custom_id' => $anggota->custom_id,
                    'pk_type' => $unit['pk_type'],
                    'brand' => $unit['brand'],
                    'quantity' => (int) $unit['quantity'],
                    'last_service_date' => $unit['last_service_date'] ?? null,
                ]);
            }

            $anggota->syncSetupStatus();

            RealtimeSync::afterCommit('ac-anggota.bulk_saved', [
                'resource' => 'anggota',
                'resource_id' => $anggota->id,
            ]);
        });

        return ApiResponse::success([
            'anggota' => $anggota->fresh('acUnits'),
        ]);
    }
}
