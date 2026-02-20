<?php

namespace App\Http\Controllers;

use App\Models\AcUnit;
use App\Models\Masjid;
use Illuminate\Http\Request;

class ACController extends Controller
{
    public function store(Request $request, Masjid $masjid)
    {
        $request->validate([
            'units' => 'required|array|min:1',
            'units.*.pk_type' => 'required|in:1PK,2PK,5PK',
            'units.*.brand' => 'required|string',
            'units.*.quantity' => 'required|integer|min:1',
            'units.*.last_service_date' => 'nullable|date',
        ]);

        foreach ($request->units as $unit) {
            AcUnit::create([
                'masjid_id' => $masjid->id,
                'pk_type' => $unit['pk_type'],
                'brand' => $unit['brand'],
                'quantity' => $unit['quantity'],
                'last_service_date' => $unit['last_service_date'] ?? null,
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function update(Request $request, AcUnit $acUnit)
    {
        $request->validate([
            'brand' => 'required|string',
            'quantity' => 'required|integer|min:1',
            'last_service_date' => 'nullable|date',
        ]);

        $acUnit->update($request->only(['brand', 'quantity', 'last_service_date']));

        return response()->json(['success' => true]);
    }

    public function destroy(AcUnit $acUnit)
    {
        $acUnit->delete();
        return response()->json(['success' => true]);
    }

    public function bulkStore(Request $request)
    {
        $request->validate([
            'masjid_id' => 'required|exists:masjids,id',
            'units' => 'required|array|min:1',
            'units.*.pk_type' => 'required|in:1PK,2PK,5PK',
            'units.*.brand' => 'required|string',
            'units.*.quantity' => 'required|integer|min:1',
            'units.*.last_service_date' => 'nullable|date',
        ]);

        $masjid = Masjid::findOrFail($request->masjid_id);

        foreach ($request->units as $unit) {
            AcUnit::create([
                'masjid_id' => $masjid->id,
                'pk_type' => $unit['pk_type'],
                'brand' => $unit['brand'],
                'quantity' => (int)$unit['quantity'],
                'last_service_date' => $unit['last_service_date'] ?? null,
            ]);
        }

        return response()->json(['success' => true]);
    }
}
