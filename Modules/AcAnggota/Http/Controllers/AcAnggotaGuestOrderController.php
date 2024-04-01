<?php

namespace Modules\AcAnggota\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Anggota;
use App\Models\AnggotaServiceOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AcAnggotaGuestOrderController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'masjid_id' => ['required', Rule::exists('ac_service.anggotas', 'id')],
            'meeting_person' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'service_date' => 'required|date|after_or_equal:today',
            'notes' => 'nullable|string|max:1000',
            'details' => 'required|array|min:1',
            'details.*.pk_type' => 'required|in:1PK,2PK,5PK',
            'details.*.brand' => 'required|string|max:100',
            'details.*.quantity' => 'required|integer|min:1|max:100',
        ]);

        $anggota = Anggota::query()->findOrFail($validated['masjid_id']);

        $existingOrder = AnggotaServiceOrder::query()
            ->where('anggota_id', $anggota->id)
            ->active()
            ->latest('service_date')
            ->latest()
            ->first();

        if ($existingOrder) {
            return back()
                ->withErrors(['masjid_id' => 'Anggota ini sudah memiliki service order aktif.'])
                ->withInput();
        }

        DB::connection('ac_service')->transaction(function () use ($validated, $anggota): void {
            $detailSummary = collect($validated['details'])
                ->map(fn (array $detail): string => sprintf(
                    '%s %s x%d',
                    $detail['pk_type'],
                    $detail['brand'],
                    $detail['quantity']
                ))
                ->implode(', ');

            AnggotaServiceOrder::query()->create([
                'anggota_id' => $anggota->id,
                'anggota_custom_id' => $anggota->custom_id,
                'order_number' => AnggotaServiceOrder::generateOrderNumber(),
                'meeting_person' => $validated['meeting_person'],
                'phone' => $validated['phone'],
                'service_date' => $validated['service_date'],
                'notes' => trim(($validated['notes'] ?? '')."\nDetail unit: ".$detailSummary),
                'status' => 'pending',
            ]);
        });

        return back()->with('success', 'Permintaan service anggota berhasil terkirim.');
    }
}
