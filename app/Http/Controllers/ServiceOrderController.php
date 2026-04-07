<?php

namespace App\Http\Controllers;

use App\Models\ServiceOrder;
use App\Models\ServiceDetail;
use App\Models\Masjid;
use App\Models\AcUnit;
use App\Models\Invoice;
use App\Models\WorkflowStep;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class ServiceOrderController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'masjid_id'            => ['required', Rule::exists('ac_service.masjids', 'id')],
            'meeting_person'       => 'required|in:dkm,marbot',
            'phone'                => 'required|string|max:20',
            'service_date'         => 'required|date|after_or_equal:today',
            'notes'                => 'nullable|string|max:1000',
            'details'              => 'required|array|min:1',
            'details.*.pk_type'    => 'required|in:1PK,2PK,5PK',
            'details.*.brand'      => 'required|string|max:100',
            'details.*.quantity'   => 'required|integer|min:1|max:100',
        ]);

        $masjid = Masjid::with('acUnits')->findOrFail($request->masjid_id);

        // Cek apakah sudah ada order aktif (pending/approved) untuk masjid ini
        $orderLama = ServiceOrder::query()
            ->where('masjid_id', $request->masjid_id)
            ->active()
            ->latest('service_date')
            ->latest()
            ->first();

        // Jika ada order lama dan user TIDAK memilih untuk replace, tolak
        if ($orderLama && !$request->boolean('force_replace')) {
            $statusLabel = ServiceOrder::statusLabel($orderLama->status);

            return response()->json([
                'success'       => false,
                'has_existing'  => true,
                'existing_order' => [
                    'id'           => $orderLama->id,
                    'order_number' => $orderLama->order_number,
                    'status'       => $orderLama->status,
                    'status_label' => $statusLabel,
                    'service_date' => $orderLama->service_date->format('d M Y'),
                ],
                'message' => "Masjid ini sudah memiliki service order aktif ({$orderLama->order_number}, status: {$statusLabel}, tanggal: {$orderLama->service_date->format('d M Y')}). Apakah ingin mengganti order lama dengan yang baru?",
            ], 409);
        }

        // Validasi jumlah unit tidak melebihi yang tersedia
        foreach ($request->details as $detail) {
            $available = $masjid->acUnits
                ->where('pk_type', $detail['pk_type'])
                ->where('brand', $detail['brand'])
                ->sum('quantity');

            if ($detail['quantity'] > $available) {
                return response()->json([
                    'success' => false,
                    'message' => "Jumlah unit {$detail['pk_type']} {$detail['brand']} melebihi unit tersedia ({$available})",
                ], 422);
            }
        }

        // Use transaction to ensure data consistency
        return DB::connection('ac_service')->transaction(function () use ($request, $masjid, $orderLama) {
            if ($orderLama && $request->boolean('force_replace')) {
                $orderLama->invoice()?->delete();
                $orderLama->delete();
            }

            $order = ServiceOrder::create([
                'masjid_id'      => $request->masjid_id,
                'order_number'   => ServiceOrder::generateOrderNumber(),
                'meeting_person' => $request->meeting_person,
                'phone'          => $request->phone,
                'service_date'   => $request->service_date,
                'notes'          => $request->notes,
                'status'         => 'pending',
            ]);

            foreach ($request->details as $detail) {
                $hargaServer = getHargaServis($masjid->type, $detail['pk_type']);
                $hargaKirim  = isset($detail['price_per_unit']) ? (int) $detail['price_per_unit'] : 0;
                $hargaFinal  = $hargaServer > 0 ? $hargaServer : $hargaKirim;

                ServiceDetail::create([
                    'service_order_id' => $order->id,
                    'pk_type'          => $detail['pk_type'],
                    'brand'            => $detail['brand'],
                    'quantity'         => $detail['quantity'],
                    'price_per_unit'   => $hargaFinal,
                ]);
            }

            WorkflowStep::create([
                'service_order_id' => $order->id,
                'step'             => 'created',
                'actor_id'         => auth()->id(),
                'actor_name'       => auth()->user()->name,
                'actor_role'       => auth()->user()->role,
                'notes'            => $request->notes,
            ]);

            return response()->json(['success' => true, 'order' => $order]);
        });
    }

    public function approve(ServiceOrder $serviceOrder)
    {
        if ($serviceOrder->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Order tidak dalam status pending.',
            ], 422);
        }

        return DB::connection('ac_service')->transaction(function () use ($serviceOrder) {
            $serviceOrder->update(['status' => 'approved']);

            WorkflowStep::create([
                'service_order_id' => $serviceOrder->id,
                'step'             => 'approved',
                'actor_id'         => auth()->id(),
                'actor_name'       => auth()->user()->name,
                'actor_role'       => auth()->user()->role,
                'notes'            => 'SPK diterbitkan',
            ]);

            return response()->json(['success' => true]);
        });
    }

    public function cancelApprove(ServiceOrder $serviceOrder)
    {
        if (!in_array($serviceOrder->status, ['approved', 'in_progress', 'waiting_invoice', 'waiting_review'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Order tidak dapat dikembalikan ke pending.',
            ], 422);
        }

        DB::connection('ac_service')->transaction(function () use ($serviceOrder) {
            $serviceOrder->update(['status' => 'pending']);
            $serviceOrder->invoice?->delete();
            $serviceOrder->technicianAssignment()->delete();

            WorkflowStep::create([
                'service_order_id' => $serviceOrder->id,
                'step'             => 'cancelled',
                'actor_id'         => auth()->id(),
                'actor_name'       => auth()->user()->name,
                'actor_role'       => auth()->user()->role,
                'notes'            => 'Approval dibatalkan dan order dikembalikan ke pending',
            ]);
        });

        return response()->json(['success' => true]);
    }

    public function destroy(ServiceOrder $serviceOrder)
    {
        DB::connection('ac_service')->transaction(function () use ($serviceOrder) {
            $serviceOrder->invoice?->delete();
            $serviceOrder->delete();
        });

        return response()->json(['success' => true]);
    }

    public function history(Masjid $masjid)
    {
        $orders = $masjid->serviceOrders()
            ->with('serviceDetails')
            ->latest()
            ->get();
        return response()->json($orders);
    }

    public function generateInvoice(ServiceOrder $serviceOrder)
    {
        if (!auth()->user()->isFrontdesk() && !auth()->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Akses tidak diizinkan.'], 403);
        }

        if ($serviceOrder->status !== 'waiting_invoice') {
            return response()->json(['success' => false, 'message' => 'Order belum siap untuk invoice.'], 422);
        }

        if ($serviceOrder->invoice) {
            return response()->json(['success' => true, 'message' => 'Invoice sudah ada.']);
        }

        return DB::connection('ac_service')->transaction(function () use ($serviceOrder) {
            $total = $serviceOrder->serviceDetails->sum(fn($d) => $d->quantity * $d->price_per_unit);

            Invoice::create([
                'service_order_id' => $serviceOrder->id,
                'invoice_number'   => Invoice::generateInvoiceNumber(),
                'total_price'      => $total,
            ]);

            $serviceOrder->update(['status' => 'waiting_review']);

            WorkflowStep::create([
                'service_order_id' => $serviceOrder->id,
                'step'             => 'invoice_generated',
                'actor_id'         => auth()->id(),
                'actor_name'       => auth()->user()->name,
                'actor_role'       => auth()->user()->role,
                'notes'            => 'Invoice diterbitkan',
            ]);

            return response()->json(['success' => true]);
        });
    }

    public function approveInvoice(ServiceOrder $serviceOrder)
    {
        if (!auth()->user()->isManager() && !auth()->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Akses tidak diizinkan.'], 403);
        }

        if (!$serviceOrder->invoice) {
            return response()->json(['success' => false, 'message' => 'Invoice belum dibuat.'], 422);
        }

        if ($serviceOrder->status !== 'waiting_review') {
            return response()->json(['success' => false, 'message' => 'Order belum dalam review invoice.'], 422);
        }

        return DB::connection('ac_service')->transaction(function () use ($serviceOrder) {
            $serviceOrder->update(['status' => 'completed']);

            WorkflowStep::create([
                'service_order_id' => $serviceOrder->id,
                'step'             => 'closed',
                'actor_id'         => auth()->id(),
                'actor_name'       => auth()->user()->name,
                'actor_role'       => auth()->user()->role,
                'notes'            => 'Invoice disetujui manager',
            ]);

            foreach ($serviceOrder->serviceDetails as $detail) {
                $units = $serviceOrder->masjid->acUnits
                    ->where('pk_type', $detail->pk_type)
                    ->where('brand', $detail->brand);
                foreach ($units as $unit) {
                    $unit->update(['last_service_date' => $serviceOrder->service_date]);
                }
            }

            return response()->json(['success' => true]);
        });
    }

    public function show(ServiceOrder $serviceOrder)
    {
        $serviceOrder->load('masjid', 'serviceDetails', 'invoice', 'workflowSteps', 'technicianAssignment');

        return response()->json([
            'order'   => $serviceOrder,
            'history' => $serviceOrder->workflowSteps->map(fn($s) => [
                'id'         => $s->id,
                'label'      => WorkflowStep::stepLabel($s->step),
                'icon'       => WorkflowStep::stepIcon($s->step),
                'color'      => WorkflowStep::stepColor($s->step),
                'actor'      => $s->actor_name,
                'role'       => $s->actor_role,
                'notes'      => $s->notes,
                'time'       => $s->created_at->format('d M Y, H:i'),
            ]),
        ]);
    }

    public function cleanExpired()
    {
        $expired = ServiceOrder::where('status', 'pending')
            ->where('service_date', '<', now()->toDateString())
            ->get();

        foreach ($expired as $order) {
            $order->serviceDetails()->delete();
            $order->delete();
        }

        return response()->json(['cleaned' => $expired->count()]);
    }
}
