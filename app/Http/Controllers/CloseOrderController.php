<?php

namespace App\Http\Controllers;

use App\Models\ServiceOrder;
use App\Support\RealtimeSync;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CloseOrderController extends Controller
{

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'service_order_ids' => 'required|array',
            'service_order_ids.*' => ['required', Rule::exists('ac_service.service_orders', 'id')],
        ]);

        $serviceOrderIds = $request->input('service_order_ids');

        // Delete completed orders (remove them from monitoring table)
        $deleted = DB::connection('ac_service')->transaction(function () use ($serviceOrderIds) {
            $orders = ServiceOrder::whereIn('id', $serviceOrderIds)
                ->where('status', 'completed')
                ->get();

            foreach ($orders as $order) {
                $order->invoice?->delete();
                $order->serviceDetails()->delete();
                $order->workflowSteps()->delete();
                $order->technicianAssignment()?->delete();
                $order->delete();
            }

            return $orders->count();
        });

        Cache::forget('monitoring:status_counts');
        Cache::forget('monitoring:status_totals');

        return response()->json([
            'success' => true,
            'message' => $deleted . ' order selesai berhasil dihapus dari tabel.',
        ]);
    }

}
