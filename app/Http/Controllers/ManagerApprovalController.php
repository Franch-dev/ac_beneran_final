<?php

namespace App\Http\Controllers;

use App\Models\ServiceOrder;
use App\Support\WorkflowLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ManagerApprovalController extends Controller
{
    /**
     * List orders with pending_fee_approval status.
     */
    public function index(Request $request)
    {
        $query = ServiceOrder::where('status', 'pending_fee_approval')
            ->with(['masjid', 'technicianAssignment', 'invoice', 'serviceDetails'])
            ->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('masjid', fn ($mq) => $mq->where('name', 'like', "%{$search}%"));
            });
        }

        $orders = $query->paginate(15);

        return view('manager.approvals', compact('orders'));
    }

    /**
     * Approve fee changes — moves order to fee_approved.
     */
    public function approve(ServiceOrder $order): JsonResponse
    {
        if ($order->status !== 'pending_fee_approval') {
            return response()->json([
                'success' => false,
                'message' => 'Order tidak dalam status pending fee approval.',
            ], 422);
        }

        $order->update(['status' => 'fee_approved']);
        WorkflowLogger::logFeeApproved($order);

        return response()->json([
            'success' => true,
            'message' => 'Biaya tambahan disetujui. Order siap untuk pembayaran.',
        ]);
    }

    /**
     * Reject fee changes — reverts to work_completed for frontdesk to re-edit.
     */
    public function reject(ServiceOrder $order, Request $request): JsonResponse
    {
        if ($order->status !== 'pending_fee_approval') {
            return response()->json([
                'success' => false,
                'message' => 'Order tidak dalam status pending fee approval.',
            ], 422);
        }

        $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $order->update(['status' => 'work_completed']);
        WorkflowLogger::logFeeRejected($order, $request->rejection_reason);

        return response()->json([
            'success' => true,
            'message' => 'Biaya tambahan ditolak. Order dikembalikan ke frontdesk.',
        ]);
    }
}
