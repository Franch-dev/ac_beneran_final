<?php

namespace App\Http\Controllers;

use App\Models\Masjid;
use App\Models\ServiceOrder;
use Illuminate\Http\Request;

class MasjidHistoryController extends Controller
{
    public function show(Request $request, Masjid $masjid)
    {
        $query = $masjid->serviceOrders()->with('serviceDetails', 'invoice', 'workflowSteps');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('start_date')) {
            $query->where('service_date', '>=', $request->input('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->where('service_date', '<=', $request->input('end_date'));
        }

        $orders = $query->latest('service_date')->paginate(15)->withQueryString();

        $totalRevenue = $masjid->serviceOrders()
            ->where('status', 'completed')
            ->with('invoice')
            ->get()
            ->sum(fn($o) => $o->invoice?->total_price ?? 0);

        $totalServices = $masjid->serviceOrders()->where('status', 'completed')->count();

        return view('history.show', compact('masjid', 'orders', 'totalRevenue', 'totalServices'));
    }
}

