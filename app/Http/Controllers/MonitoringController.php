<?php

namespace App\Http\Controllers;

use App\Models\Masjid;
use App\Models\ServiceOrder;
use App\Models\AcUnit;
use Illuminate\Http\Request;

class MonitoringController extends Controller
{
    public function index(Request $request)
    {
        // Auto clean expired pending orders
        ServiceOrder::where('status', 'pending')
            ->where('service_date', '<', now()->toDateString())
            ->delete();

        $query = ServiceOrder::with('masjid', 'serviceDetails', 'invoice');

        if ($request->search) {
            $search = $request->search;
            $query->whereHas('masjid', function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('custom_id', 'like', "%$search%");
            })->orWhere('order_number', 'like', "%$search%");
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $orders = $query->latest()->get();

        $totalLokasi = Masjid::count();
        $totalUnit = AcUnit::sum('quantity');
        $overdue = Masjid::with('acUnits')
            ->get()
            ->filter(fn($m) => $m->urgency_status === 'overdue')
            ->count();

        $masjids = Masjid::with('acUnits')->get();

        return view('monitoring', compact('orders', 'totalLokasi', 'totalUnit', 'overdue', 'masjids'));
    }
}
