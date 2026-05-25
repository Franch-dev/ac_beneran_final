<?php

namespace App\Http\Controllers;

use App\Models\Masjid;
use App\Models\ServiceOrder;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

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

    public function historyJson(Masjid $masjid): JsonResponse
    {
        $orders = $masjid->serviceOrders()
            ->with(['serviceDetails', 'invoice', 'workflowSteps'])
            ->latest('service_date')
            ->get()
            ->map(fn($order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'service_date' => $order->service_date,
                'status' => $order->status,
                'total_price' => $order->invoice?->total_price ?? 0,
                'details' => $order->serviceDetails->map(fn($d) => [
                    'pk_type' => $d->pk_type,
                    'brand' => $d->brand,
                    'quantity' => $d->quantity,
                    'service_type' => $d->service_type,
                    'complaint' => $d->complaint,
                ]),
                'steps' => $order->workflowSteps->map(fn($s) => [
                    'step' => $s->step,
                    'notes' => $s->notes,
                    'time' => $s->created_at->format('d M Y, H:i'),
                ]),
            ]);

        return ApiResponse::raw($orders);
    }
}
