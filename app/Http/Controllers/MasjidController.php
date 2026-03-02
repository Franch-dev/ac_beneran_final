<?php

namespace App\Http\Controllers;

use App\Models\Masjid;
use App\Models\AcUnit;
use Illuminate\Http\Request;

class MasjidController extends Controller
{
    public function index(Request $request)
    {
        $query = Masjid::with('acUnits', 'serviceOrders');

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('custom_id', 'like', "%$search%");
            });
        }

        $masjids = $query->latest()->get();
        return view('dashboard', compact('masjids'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:masjid,musholla',
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'dkm_name' => 'required|string|max:255',
            'marbot_name' => 'required|string|max:255',
            'phone_numbers' => 'required|array|min:1',
            'phone_numbers.*' => 'required|string',
        ]);

        $customId = Masjid::generateCustomId($request->type);

        $masjid = Masjid::create([
            'custom_id' => $customId,
            'type' => $request->type,
            'name' => $request->name,
            'address' => $request->address,
            'dkm_name' => $request->dkm_name,
            'marbot_name' => $request->marbot_name,
            'phone_numbers' => $request->phone_numbers,
        ]);

        return response()->json(['success' => true, 'masjid' => $masjid, 'custom_id' => $customId]);
    }

    public function update(Request $request, Masjid $masjid)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'dkm_name' => 'required|string|max:255',
            'marbot_name' => 'required|string|max:255',
            'phone_numbers' => 'required|array|min:1',
        ]);

        $masjid->update($request->only(['name', 'address', 'dkm_name', 'marbot_name', 'phone_numbers']));

        return response()->json(['success' => true]);
    }

    public function destroy(Masjid $masjid)
    {
        // Hard delete so FK cascade cleans up service orders, AC units, etc.
        $masjid->forceDelete();
        return response()->json(['success' => true]);
    }

    public function detail(Masjid $masjid)
    {
        $masjid->load('acUnits', 'serviceOrders');
        return response()->json($masjid);
    }
}
