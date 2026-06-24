<?php

namespace App\Http\Controllers;

use App\Models\ServiceOrder;
use App\Support\ApiResponse;
use App\Support\ServiceOrderWorkflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

        $orders = ServiceOrder::whereIn('id', $serviceOrderIds)
            ->whereIn('status', ['completed', 'closed'])
            ->get();

        foreach ($orders as $order) {
            app(ServiceOrderWorkflow::class)->archiveClosed($order);
        }

        return ApiResponse::success(message: $orders->count() . ' order selesai berhasil diarsipkan.');
    }

}
