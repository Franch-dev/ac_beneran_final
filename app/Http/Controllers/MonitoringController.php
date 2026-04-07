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

        $query = ServiceOrder::with('masjid.acUnits', 'serviceDetails', 'invoice', 'workflowSteps', 'technicianAssignment');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($innerQuery) use ($search) {
                $innerQuery->whereHas('masjid', function ($q) use ($search) {
                    $q->where('name', 'like', "%$search%")
                      ->orWhere('custom_id', 'like', "%$search%");
                })->orWhere('order_number', 'like', "%$search%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $orders = $query->latest()->paginate(20);
        $statusTotals = ServiceOrder::query()
            ->select('status')
            ->selectRaw('count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalLokasi = Masjid::count();
        $totalUnit = AcUnit::sum('quantity');
        $overdue = Masjid::with('acUnits')
            ->get()
            ->filter(fn($m) => $m->urgency_status === 'overdue')
            ->count();

        $masjids = Masjid::with('acUnits')->paginate(15);

        return view('monitoring', compact('orders', 'totalLokasi', 'totalUnit', 'overdue', 'masjids', 'statusTotals'));
    }

    public function statusCounts()
    {
        $counts = ServiceOrder::select('status')
            ->selectRaw('count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return response()->json($counts);
    }
}
