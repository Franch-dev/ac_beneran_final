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
use Throwable;

class AnggotaController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:individual,business',
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'phone_numbers' => 'required|array|min:1',
            'phone_numbers.*' => 'required|string',
        ]);

        $anggota = null;

        DB::connection('ac_service')->transaction(function () use ($validated, &$anggota): void {
            $anggota = Anggota::create([
                'custom_id' => Anggota::generateCustomId(),
                'type' => $validated['type'],
                'name' => $validated['name'],
                'address' => $validated['address'],
                'phone_numbers' => array_values(array_filter($validated['phone_numbers'])),
                'setup_status' => 'pending_ac',
            ]);

            RealtimeSync::afterCommit('anggota.created', [
                'resource' => 'anggota',
                'resource_id' => $anggota->id,
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
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'phone_numbers' => 'required|array|min:1',
            'phone_numbers.*' => 'required|string',
        ]);

        DB::connection('ac_service')->transaction(function () use ($validated, $anggota): void {
            $anggota->update([
                'name' => $validated['name'],
                'address' => $validated['address'],
                'phone_numbers' => array_values(array_filter($validated['phone_numbers'])),
            ]);

            RealtimeSync::afterCommit('anggota.updated', [
                'resource' => 'anggota',
                'resource_id' => $anggota->id,
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
        ]);

        return ApiResponse::raw($anggota);
    }
}
