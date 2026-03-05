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
        // Note: Expired orders are cleaned via scheduled command (app/Console/Commands/CleanExpiredOrders.php)
        // This prevents side effects in read operations

        $query = ServiceOrder::with('masjid.acUnits', 'serviceDetails', 'invoice');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('masjid', function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('custom_id', 'like', "%$search%");
            })->orWhere('order_number', 'like', "%$search%");
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $orders = $query->latest()->paginate(20);

        $totalLokasi = Masjid::count();
        $totalUnit = AcUnit::sum('quantity');
        $overdue = Masjid::with('acUnits')
            ->get()
            ->filter(fn($m) => $m->urgency_status === 'overdue')
            ->count();

        $masjids = Masjid::with('acUnits')->paginate(15);

        return view('monitoring', compact('orders', 'totalLokasi', 'totalUnit', 'overdue', 'masjids'));
    }
}
