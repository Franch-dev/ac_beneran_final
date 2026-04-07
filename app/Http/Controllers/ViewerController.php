<?php

namespace App\Http\Controllers;

use App\Models\Masjid;
use App\Models\ServiceOrder;
use App\Models\AcUnit;
use App\Models\Invoice;

class ViewerController extends Controller
{
    public function dashboard()
    {
        $totalMasjid  = Masjid::count();
        $totalUnit    = AcUnit::sum('quantity');
        $totalOrders  = ServiceOrder::count();
        $totalRevenue = Invoice::sum('total_price');

        $overdueMasjids = Masjid::with('acUnits')->get()
            ->filter(fn($m) => $m->urgency_status === 'overdue')
            ->count();

        $recentOrders = ServiceOrder::with('masjid', 'serviceDetails')
            ->latest()
            ->take(20)
            ->get();

        return view('viewer.dashboard', compact(
            'totalMasjid', 'totalUnit', 'totalOrders',
            'totalRevenue', 'overdueMasjids', 'recentOrders'
        ));
    }
}

