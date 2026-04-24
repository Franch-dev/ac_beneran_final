<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Masjid;
use App\Models\ServiceDetail;
use App\Models\ServiceOrder;
use App\Models\WorkflowStep;
use App\Support\RealtimeSync;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ServiceOrderController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'masjid_id' => ['required', Rule::exists('ac_service.masjids', 'id')],
            'meeting_person' => 'required|in:dkm,marbot',
            'phone' => 'required|string|max:20',
            'service_date' => 'required|date|after_or_equal:today',
            'notes' => 'nullable|string|max:1000',
            'details' => 'required|array|min:1',
            'details.*.pk_type' => 'required|in:1PK,2PK,5PK',
            'details.*.brand' => 'required|string|max:100',
            'details.*.quantity' => 'required|integer|min:1|max:100',
        ]);

        $masjid = Masjid::with('acUnits')->findOrFail($validated['masjid_id']);

        $existingOrder = ServiceOrder::query()
            ->where('masjid_id', $validated['masjid_id'])
            ->active()
            ->latest('service_date')
            ->latest()
            ->first();

        if ($existingOrder && ! $request->boolean('force_replace')) {
            $statusLabel = ServiceOrder::statusLabel($existingOrder->status);

            return response()->json([
                'success' => false,
                'has_existing' => true,
                'existing_order' => [
                    'id' => $existingOrder->id,
                    'order_number' => $existingOrder->order_number,
                    'status' => $existingOrder->status,
                    'status_label' => $statusLabel,
                    'service_date' => $existingOrder->service_date->format('d M Y'),
                ],
                'message' => "Masjid ini sudah memiliki service order aktif ({$existingOrder->order_number}, status: {$statusLabel}, tanggal: {$existingOrder->service_date->format('d M Y')}). Apakah ingin mengganti order lama dengan yang baru?",
            ], 409);
        }

        foreach ($validated['details'] as $detail) {
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

        $order = DB::connection('ac_service')->transaction(function () use ($request, $validated, $masjid, $existingOrder) {
            if ($existingOrder && $request->boolean('force_replace')) {
                $existingOrder->invoice()?->delete();
                $existingOrder->delete();
            }

            $order = ServiceOrder::create([
                'masjid_id' => $validated['masjid_id'],
                'order_number' => ServiceOrder::generateOrderNumber(),
                'meeting_person' => $validated['meeting_person'],
                'phone' => $validated['phone'],
                'service_date' => $validated['service_date'],
                'notes' => $validated['notes'] ?? null,
                'status' => 'pending',
            ]);

            foreach ($validated['details'] as $detail) {
                $serverPrice = getHargaServis($masjid->type, $detail['pk_type']);
                $requestPrice = isset($detail['price_per_unit']) ? (int) $detail['price_per_unit'] : 0;

                ServiceDetail::create([
                    'service_order_id' => $order->id,
                    'pk_type' => $detail['pk_type'],
                    'brand' => $detail['brand'],
                    'quantity' => $detail['quantity'],
                    'price_per_unit' => $serverPrice > 0 ? $serverPrice : $requestPrice,
                ]);
            }

            WorkflowStep::create([
                'service_order_id' => $order->id,
                'step' => 'created',
                'actor_id' => auth()->id(),
                'actor_name' => auth()->user()->name,
                'actor_role' => auth()->user()->role,
                'notes' => $validated['notes'] ?? null,
            ]);

            RealtimeSync::afterCommit('service_order.created', [
                'resource' => 'service_order',
                'resource_id' => $order->id,
                'masjid_id' => $order->masjid_id,
                'service_order_id' => $order->id,
                'payload' => [
                    'status' => $order->status,
                ],
            ]);

            return $order;
        });
        $this->flushMonitoringCaches();

        return response()->json([
            'success' => true,
            'order' => $order->fresh('masjid', 'serviceDetails'),
        ]);
    }

    public function guestStore(Request $request)
    {
        $validated = $request->validate([
            'masjid_id' => ['required', Rule::exists('ac_service.masjids', 'id')],
            'meeting_person' => 'required|in:dkm,marbot',
            'phone' => 'required|string|max:20',
            'service_date' => 'required|date|after_or_equal:today',
            'notes' => 'nullable|string|max:1000',
            'details' => 'required|array|min:1',
            'details.*.pk_type' => 'required|in:1PK,2PK,5PK',
            'details.*.brand' => 'required|string|max:100',
            'details.*.quantity' => 'required|integer|min:1|max:100',
        ]);

        $masjid = Masjid::with('acUnits')->findOrFail($validated['masjid_id']);

        $existingOrder = ServiceOrder::query()
            ->where('masjid_id', $validated['masjid_id'])
            ->active()
            ->latest('service_date')
            ->latest()
            ->first();

        if ($existingOrder && ! $request->boolean('force_replace')) {
            return back()->withErrors(['masjid_id' => 'Masjid ini sudah memiliki service order aktif. Silakan cek kembali atau hubungi admin.'])->withInput();
        }

        $order = DB::connection('ac_service')->transaction(function () use ($request, $validated, $masjid, $existingOrder) {
            if ($existingOrder && $request->boolean('force_replace')) {
                $existingOrder->invoice()?->delete();
                $existingOrder->delete();
            }

            $order = ServiceOrder::create([
                'masjid_id' => $validated['masjid_id'],
                'order_number' => ServiceOrder::generateOrderNumber(),
                'meeting_person' => $validated['meeting_person'],
                'phone' => $validated['phone'],
                'service_date' => $validated['service_date'],
                'notes' => $validated['notes'] ?? null,
                'status' => 'pending',
            ]);

            foreach ($validated['details'] as $detail) {
                $serverPrice = getHargaServis($masjid->type, $detail['pk_type']);
                $requestPrice = isset($detail['price_per_unit']) ? (int) $detail['price_per_unit'] : 0;

                ServiceDetail::create([
                    'service_order_id' => $order->id,
                    'pk_type' => $detail['pk_type'],
                    'brand' => $detail['brand'],
                    'quantity' => $detail['quantity'],
                    'price_per_unit' => $serverPrice > 0 ? $serverPrice : $requestPrice,
                ]);
            }

            WorkflowStep::create([
                'service_order_id' => $order->id,
                'step' => 'created',
                'actor_id' => auth()->id(),
                'actor_name' => auth()->check() ? auth()->user()->name : 'Guest',
                'actor_role' => auth()->check() ? auth()->user()->role : 'guest',
                'notes' => $validated['notes'] ?? null,
            ]);

            RealtimeSync::afterCommit('service_order.created', [
                'resource' => 'service_order',
                'resource_id' => $order->id,
                'masjid_id' => $order->masjid_id,
                'service_order_id' => $order->id,
                'payload' => [
                    'status' => $order->status,
                ],
            ]);

            return $order;
        });

        $this->flushMonitoringCaches();

        return back()->with('success', 'Permintaan service order Anda berhasil terkirim. Kami akan menindaklanjutinya segera.');
    }

    public function approve(ServiceOrder $serviceOrder): JsonResponse
    {
        if ($serviceOrder->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Order tidak dalam status pending.',
            ], 422);
        }

        return DB::connection('ac_service')->transaction(function () use ($serviceOrder) {
            $serviceOrder->update(['status' => 'waiting_invoice']);

            WorkflowStep::create([
                'service_order_id' => $serviceOrder->id,
                'step' => 'approved',
                'actor_id' => auth()->id(),
                'actor_name' => auth()->user()->name,
                'actor_role' => auth()->user()->role,
                'notes' => 'Menunggu pembuatan SPK & Invoice',
            ]);

            RealtimeSync::afterCommit('service_order.approved', [
                'resource' => 'service_order',
                'resource_id' => $serviceOrder->id,
                'masjid_id' => $serviceOrder->masjid_id,
                'service_order_id' => $serviceOrder->id,
                'payload' => [
                    'status' => 'waiting_invoice',
                ],
            ]);

            $this->flushMonitoringCaches();
            return response()->json(['success' => true]);
        });
    }

    public function cancelApprove(ServiceOrder $serviceOrder): JsonResponse
    {
        if (! in_array($serviceOrder->status, ['approved', 'in_progress', 'waiting_invoice', 'waiting_review'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Order tidak dapat dikembalikan ke pending.',
            ], 422);
        }

        return DB::connection('ac_service')->transaction(function () use ($serviceOrder) {
            $serviceOrder->update(['status' => 'pending']);
            $serviceOrder->invoice?->delete();
            $serviceOrder->technicianAssignment()->delete();

            WorkflowStep::create([
                'service_order_id' => $serviceOrder->id,
                'step' => 'cancelled',
                'actor_id' => auth()->id(),
                'actor_name' => auth()->user()->name,
                'actor_role' => auth()->user()->role,
                'notes' => 'Approval dibatalkan dan order dikembalikan ke pending',
            ]);

            RealtimeSync::afterCommit('service_order.cancelled', [
                'resource' => 'service_order',
                'resource_id' => $serviceOrder->id,
                'masjid_id' => $serviceOrder->masjid_id,
                'service_order_id' => $serviceOrder->id,
                'payload' => [
                    'status' => 'pending',
                ],
            ]);

            $this->flushMonitoringCaches();
            return response()->json(['success' => true]);
        });
    }

    public function destroy(ServiceOrder $serviceOrder): JsonResponse
    {
        $orderId = $serviceOrder->id;
        $masjidId = $serviceOrder->masjid_id;

        return DB::connection('ac_service')->transaction(function () use ($serviceOrder, $orderId, $masjidId) {
            $serviceOrder->invoice?->delete();
            $serviceOrder->delete();

            RealtimeSync::afterCommit('service_order.deleted', [
                'resource' => 'service_order',
                'resource_id' => $orderId,
                'masjid_id' => $masjidId,
                'service_order_id' => $orderId,
            ]);

            $this->flushMonitoringCaches();
            return response()->json([
                'success' => true,
                'service_order_id' => $orderId,
            ]);
        });
    }

    public function history(Masjid $masjid): JsonResponse
    {
        $orders = $masjid->serviceOrders()
            ->with('serviceDetails')
            ->latest()
            ->get();

        return response()->json($orders);
    }

    public function generateInvoice(ServiceOrder $serviceOrder): JsonResponse
    {
        if (! auth()->user()->isFrontdesk() && ! auth()->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Akses tidak diizinkan.'], 403);
        }

        if ($serviceOrder->status !== 'waiting_invoice') {
            return response()->json(['success' => false, 'message' => 'Order belum siap untuk invoice.'], 422);
        }

        if ($serviceOrder->invoice) {
            return response()->json(['success' => true, 'message' => 'Invoice sudah ada.']);
        }

        return DB::connection('ac_service')->transaction(function () use ($serviceOrder) {
            $total = $serviceOrder->serviceDetails->sum(fn ($detail) => $detail->quantity * $detail->price_per_unit);

            Invoice::create([
                'service_order_id' => $serviceOrder->id,
                'invoice_number' => Invoice::generateInvoiceNumber(),
                'total_price' => $total,
            ]);

            $serviceOrder->update(['status' => 'approved']);

            WorkflowStep::create([
                'service_order_id' => $serviceOrder->id,
                'step' => 'invoice_generated',
                'actor_id' => auth()->id(),
                'actor_name' => auth()->user()->name,
                'actor_role' => auth()->user()->role,
                'notes' => 'SPK & Invoice diterbitkan',
            ]);

            RealtimeSync::afterCommit('service_order.invoice_generated', [
                'resource' => 'service_order',
                'resource_id' => $serviceOrder->id,
                'masjid_id' => $serviceOrder->masjid_id,
                'service_order_id' => $serviceOrder->id,
                'payload' => [
                    'status' => 'approved',
                ],
            ]);

            $this->flushMonitoringCaches();
            return response()->json(['success' => true]);
        });
    }

    public function approveInvoice(ServiceOrder $serviceOrder): JsonResponse
    {
        if (! auth()->user()->isManager() && ! auth()->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Akses tidak diizinkan.'], 403);
        }

        if (! $serviceOrder->invoice) {
            return response()->json(['success' => false, 'message' => 'Invoice belum dibuat.'], 422);
        }

        if ($serviceOrder->status !== 'waiting_review') {
            return response()->json(['success' => false, 'message' => 'Order belum dalam review invoice.'], 422);
        }

        return DB::connection('ac_service')->transaction(function () use ($serviceOrder) {
            $serviceOrder->update(['status' => 'completed']);

            WorkflowStep::create([
                'service_order_id' => $serviceOrder->id,
                'step' => 'closed',
                'actor_id' => auth()->id(),
                'actor_name' => auth()->user()->name,
                'actor_role' => auth()->user()->role,
                'notes' => 'Invoice disetujui manager',
            ]);

            foreach ($serviceOrder->serviceDetails as $detail) {
                $units = $serviceOrder->masjid->acUnits
                    ->where('pk_type', $detail->pk_type)
                    ->where('brand', $detail->brand);

                foreach ($units as $unit) {
                    $unit->update(['last_service_date' => $serviceOrder->service_date]);
                }
            }

            RealtimeSync::afterCommit('service_order.invoice_approved', [
                'resource' => 'service_order',
                'resource_id' => $serviceOrder->id,
                'masjid_id' => $serviceOrder->masjid_id,
                'service_order_id' => $serviceOrder->id,
                'payload' => [
                    'status' => 'completed',
                ],
            ]);

            $this->flushMonitoringCaches();
            return response()->json(['success' => true]);
        });
    }

    public function show(ServiceOrder $serviceOrder): JsonResponse
    {
        $serviceOrder->load([
            'masjid:id,name',
            'serviceDetails:id,service_order_id,pk_type,brand,quantity',
            'invoice:id,service_order_id,invoice_number',
        ]);

        $historySteps = WorkflowStep::query()
            ->where('service_order_id', $serviceOrder->id)
            ->orderBy('created_at')
            ->get(['id', 'step', 'actor_name', 'actor_role', 'notes', 'created_at']);

        return response()->json([
            'order' => [
                'id' => $serviceOrder->id,
                'order_number' => $serviceOrder->order_number,
                'status' => $serviceOrder->status,
                'service_date' => $serviceOrder->service_date?->toDateString(),
                'phone' => $serviceOrder->phone,
                'notes' => $serviceOrder->notes,
                'masjid' => [
                    'id' => $serviceOrder->masjid?->id,
                    'name' => $serviceOrder->masjid?->name,
                ],
                'service_details' => $serviceOrder->serviceDetails->map(fn ($detail) => [
                    'pk_type' => $detail->pk_type,
                    'brand' => $detail->brand,
                    'quantity' => $detail->quantity,
                ])->values(),
                'invoice' => $serviceOrder->invoice ? [
                    'id' => $serviceOrder->invoice->id,
                    'invoice_number' => $serviceOrder->invoice->invoice_number,
                ] : null,
            ],
            'history' => $historySteps->map(fn ($step) => [
                'id' => $step->id,
                'label' => WorkflowStep::stepLabel($step->step),
                'icon' => WorkflowStep::stepIcon($step->step),
                'color' => WorkflowStep::stepColor($step->step),
                'actor' => $step->actor_name,
                'role' => $step->actor_role,
                'notes' => $step->notes,
                'time' => $step->created_at->format('d M Y, H:i'),
            ]),
        ]);
    }

    public function cleanExpired(): JsonResponse
    {
        $expired = ServiceOrder::where('status', 'pending')
            ->where('service_date', '<', now()->toDateString())
            ->get();

        foreach ($expired as $order) {
            $order->serviceDetails()->delete();
            $order->delete();
        }

        if ($expired->isNotEmpty()) {
            RealtimeSync::afterCommit('service_order.expired_cleaned', [
                'resource' => 'service_order',
                'payload' => [
                    'count' => $expired->count(),
                ],
            ]);

            $this->flushMonitoringCaches();
        }

        return response()->json(['cleaned' => $expired->count()]);
    }

    protected function flushMonitoringCaches(): void
    {
        Cache::forget('monitoring:status_counts');
        Cache::forget('monitoring:status_totals');
    }
}
