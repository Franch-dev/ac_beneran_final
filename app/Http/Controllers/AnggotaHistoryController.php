<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use App\Models\AnggotaServiceOrder;
use Illuminate\Http\Request;

class AnggotaHistoryController extends Controller
{
    public function show(Request $request, Anggota $anggota)
    {
        $query = $anggota->serviceOrders()->with('serviceDetails', 'invoice', 'workflowSteps');

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

        $totalRevenue = $anggota->serviceOrders()
            ->where('status', 'completed')
            ->with('invoice')
            ->get()
            ->sum(fn($o) => $o->invoice?->total_price ?? 0);

        $totalServices = $anggota->serviceOrders()->where('status', 'completed')->count();

        return view('ac-anggota::history.show', compact('anggota', 'orders', 'totalRevenue', 'totalServices'));
    }
}