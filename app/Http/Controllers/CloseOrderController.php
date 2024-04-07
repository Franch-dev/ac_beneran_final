<?php

namespace App\Http\Controllers;

use App\Models\ServiceOrder;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class CloseOrderController extends Controller
{

    public function __invoke(Request $request): RedirectResponse
    {
        $request->validate([
            'service_order_ids' => 'required|array',
            'service_order_ids.*' => 'exists:service_orders,id',
        ]);

        $serviceOrderIds = $request->input('service_order_ids');

        ServiceOrder::whereIn('id', $serviceOrderIds)
            ->whereIn('status', ['waiting_invoice', 'waiting_review'])
            ->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

        cache()->forget('monitoring_status_totals');
        cache()->forget('monitoring_orders_*');

        return redirect()->route('monitoring')->with('success', count($serviceOrderIds) . ' order berhasil ditutup dan dibersihkan dari tabel.');
    }

}

