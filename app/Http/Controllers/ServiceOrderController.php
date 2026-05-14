<?php

namespace App\Http\Controllers;

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

        $hasAcUnits = $masjid->acUnits->isNotEmpty();
        foreach ($validated['details'] as $detail) {
            if ($hasAcUnits) {
                // Use total across ALL brands for this pk_type — matches frontend display
                $available = $masjid->acUnits
                    ->where('pk_type', $detail['pk_type'])
                    ->sum('quantity');

                if ($detail['quantity'] > $available) {
                    return response()->json([
                        'success' => false,
                        'message' => "Jumlah unit {$detail['pk_type']} melebihi unit tersedia ({$available})",
                    ], 422);
                }
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
                'status' => 'spk_invoice_created',
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
                'step' => 'frontdesk_created',
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

        if ($masjid->acUnits->isNotEmpty()) {
            foreach ($validated['details'] as $detail) {
                $available = $masjid->acUnits->where('pk_type', $detail['pk_type'])->sum('quantity');
                if ($detail['quantity'] > $available) {
                    return back()->withErrors(['details' => "Jumlah unit {$detail['pk_type']} melebihi unit tersedia ({$available})"])->withInput();
                }
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
                'status' => 'spk_invoice_created',
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
                'step' => 'frontdesk_created',
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
        if ($serviceOrder->status !== 'spk_invoice_created') {
            return response()->json([
                'success' => false,
                'message' => 'Order tidak dalam status Order Dibuat (SPK & Invoice).',
            ], 422);
        }

        return DB::connection('ac_service')->transaction(function () use ($serviceOrder) {
            // ── 1. Build & save Invoice ───────────────────────────────
            $total = $serviceOrder->serviceDetails->sum(
                fn ($detail) => $detail->quantity * $detail->price_per_unit
            );

            Invoice::create([
                'service_order_id' => $serviceOrder->id,
                'invoice_number' => Invoice::generateInvoiceNumber(),
                'total_price' => $total,
            ]);

            // ── 2. Transition directly to waiting_payment ─────────────
            // Manager approval + invoice creation = ready for payment.
            // Previously set to 'approved' which was a dead-end because
            // createSpkInvoice refused to run when invoice already existed.
            $serviceOrder->update(['status' => 'waiting_payment']);

            // ── 3. Record workflow steps ──────────────────────────────
            // Step A: Record the approval
            WorkflowStep::create([
                'service_order_id' => $serviceOrder->id,
                'step' => 'approved',
                'actor_id' => auth()->id(),
                'actor_name' => auth()->user()->name,
                'actor_role' => auth()->user()->role,
                'notes' => 'Order disetujui oleh Manager, invoice dibuat',
            ]);

            // Step B: Record transition to waiting_payment
            WorkflowStep::create([
                'service_order_id' => $serviceOrder->id,
                'step' => 'waiting_payment',
                'actor_id' => auth()->id(),
                'actor_name' => auth()->user()->name,
                'actor_role' => auth()->user()->role,
                'notes' => 'Menunggu pembayaran dari masjid',
            ]);

            // ── 4. Real-time broadcast ─────────────────────────────────
            RealtimeSync::afterCommit('service_order.approved', [
                'resource' => 'service_order',
                'resource_id' => $serviceOrder->id,
                'masjid_id' => $serviceOrder->masjid_id,
                'service_order_id' => $serviceOrder->id,
                'payload' => ['status' => 'waiting_payment'],
            ]);

            $this->flushMonitoringCaches();

            return response()->json([
                'success' => true,
                'service_order_id' => $serviceOrder->id,
                'status' => $serviceOrder->fresh()->status,
            ]);
        });
    }

    public function cancelApprove(ServiceOrder $serviceOrder): JsonResponse
    {
        if (! in_array($serviceOrder->status, ['approved', 'waiting_payment', 'payment_verified', 'in_progress', 'waiting_review'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Order tidak dapat dikembalikan ke Order Dibuat (SPK & Invoice).',
            ], 422);
        }

        return DB::connection('ac_service')->transaction(function () use ($serviceOrder) {
            $serviceOrder->update(['status' => 'spk_invoice_created']);
            $serviceOrder->invoice?->delete();
            $serviceOrder->technicianAssignment()->delete();

            WorkflowStep::create([
                'service_order_id' => $serviceOrder->id,
                'step' => 'cancelled',
                'actor_id' => auth()->id(),
                'actor_name' => auth()->user()->name,
                'actor_role' => auth()->user()->role,
                'notes' => 'Approval dibatalkan dan order dikembalikan ke SPK & Invoice dibuat',
            ]);

            RealtimeSync::afterCommit('service_order.cancelled', [
                'resource' => 'service_order',
                'resource_id' => $serviceOrder->id,
                'masjid_id' => $serviceOrder->masjid_id,
                'service_order_id' => $serviceOrder->id,
                'payload' => [
                    'status' => 'spk_invoice_created',
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

        if ($serviceOrder->status !== 'approved') {
            return response()->json(['success' => false, 'message' => 'Order harus disetujui manager terlebih dahulu.'], 422);
        }

        return DB::connection('ac_service')->transaction(function () use ($serviceOrder) {
            if (!$serviceOrder->invoice) {
                $total = $serviceOrder->serviceDetails->sum(fn ($detail) => $detail->quantity * $detail->price_per_unit);

                Invoice::create([
                    'service_order_id' => $serviceOrder->id,
                    'invoice_number' => Invoice::generateInvoiceNumber(),
                    'total_price' => $total,
                ]);
            }

            $serviceOrder->update(['status' => 'waiting_payment']);
            WorkflowStep::create([
                'service_order_id' => $serviceOrder->id,
                'step' => 'waiting_payment',
                'actor_id' => auth()->id(),
                'actor_name' => auth()->user()->name,
                'actor_role' => auth()->user()->role,
                'notes' => 'Invoice diterbitkan, menunggu pembayaran',
            ]);

            RealtimeSync::afterCommit('service_order.invoice_generated', [
                'resource' => 'service_order',
                'resource_id' => $serviceOrder->id,
                'masjid_id' => $serviceOrder->masjid_id,
                'service_order_id' => $serviceOrder->id,
                'payload' => [
                    'status' => 'waiting_payment',
                ],
            ]);

            $this->flushMonitoringCaches();
            return response()->json(['success' => true]);
        });
    }

    public function confirmPayment(ServiceOrder $serviceOrder): JsonResponse
    {
        if (! auth()->user()->isManager() && ! auth()->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Akses tidak diizinkan.'], 403);
        }

        if (! $serviceOrder->invoice) {
            return response()->json(['success' => false, 'message' => 'Invoice belum dibuat.'], 422);
        }

        if ($serviceOrder->status !== 'waiting_payment') {
            return response()->json(['success' => false, 'message' => 'Order tidak menunggu pembayaran.'], 422);
        }

        return DB::connection('ac_service')->transaction(function () use ($serviceOrder) {
            // Column safety: some environments may not have payment_verified_at yet.
            $invoicePayload = ['updated_at' => now()];
            $invoicePayload['payment_verified_at'] = now();
            $serviceOrder->invoice->update($invoicePayload);
            $serviceOrder->update(['status' => 'payment_verified']);

            WorkflowStep::create([
                'service_order_id' => $serviceOrder->id,
                'step' => 'payment_verified',
                'actor_id' => auth()->id(),
                'actor_name' => auth()->user()->name,
                'actor_role' => auth()->user()->role,
                'notes' => 'Pembayaran telah diverifikasi',
            ]);

            RealtimeSync::afterCommit('service_order.payment_verified', [
                'resource' => 'service_order',
                'resource_id' => $serviceOrder->id,
                'masjid_id' => $serviceOrder->masjid_id,
                'service_order_id' => $serviceOrder->id,
                'payload' => ['status' => 'payment_verified'],
            ]);

            $this->flushMonitoringCaches();
            return response()->json(['success' => true]);
        });
    }

    public function finalizeOrder(ServiceOrder $serviceOrder): JsonResponse
    {
        if (! auth()->user()->isManager() && ! auth()->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Akses tidak diizinkan.'], 403);
        }

        if (!in_array($serviceOrder->status, ['payment_verified', 'waiting_review'])) {
            return response()->json(['success' => false, 'message' => 'Order belum siap untuk diselesaikan.'], 422);
        }

        return DB::connection('ac_service')->transaction(function () use ($serviceOrder) {
            $serviceOrder->update(['status' => 'completed']);

            WorkflowStep::create([
                'service_order_id' => $serviceOrder->id,
                'step' => 'completed',
                'actor_id' => auth()->id(),
                'actor_name' => auth()->user()->name,
                'actor_role' => auth()->user()->role,
                'notes' => 'Order selesai',
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
                'payload' => ['status' => 'completed'],
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

        // Helper function to sanitize service data
        $sanitizeServiceData = function($detail) {
            return [
                'pk_type' => $detail->pk_type,
                'brand' => $detail->brand,
                'quantity' => $detail->quantity,
                'service_type' => $this->sanitizeText($detail->service_type ?? ''),
            ];
        };

        return response()->json([
            'order' => [
                'id' => $serviceOrder->id,
                'order_number' => $serviceOrder->order_number,
                'status' => $serviceOrder->status,
                'service_date' => $serviceOrder->service_date?->toDateString(),
                'phone' => $serviceOrder->phone,
                'notes' => $this->sanitizeText($serviceOrder->notes ?? ''),
                'masjid' => [
                    'id' => $serviceOrder->masjid?->id,
                    'name' => $this->sanitizeText($serviceOrder->masjid?->name ?? ''),
                ],
                'service_details' => $serviceOrder->serviceDetails->map($sanitizeServiceData)->values(),
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
        $expired = ServiceOrder::where('status', 'spk_invoice_created')
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

    // ============================================
    // FIELD REPORT SUBMISSION (Technician)
    // ============================================
    public function submitFieldReport(Request $request, ServiceOrder $serviceOrder): JsonResponse
    {
        if (!auth()->user()->isTechnician()) {
            return response()->json(['success' => false, 'message' => 'Akses tidak diizinkan.'], 403);
        }

        // Verify the logged-in technician is the one assigned to this order
        $assignment = $serviceOrder->technicianAssignment;
        if (!$assignment || $assignment->technician_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Anda tidak ditugaskan ke order ini.'], 403);
        }

        if ($serviceOrder->status !== 'in_progress') {
            return response()->json(['success' => false, 'message' => 'Order belum dalam status service.'], 422);
        }

        $validated = $request->validate([
            'field_report_notes' => 'required|string|max:2000',
            'field_report_additional_fee' => 'nullable|numeric|min:0',
            'field_report_tools_materials' => 'nullable|array',
            'field_report_tools_materials.*.name' => 'required|string',
            'field_report_tools_materials.*.quantity' => 'required|integer|min:1',
            'field_report_tools_materials.*.price' => 'required|numeric|min:0',
        ]);

        $additionalFee = $validated['field_report_additional_fee'] ?? 0;
        $toolsMaterials = $validated['field_report_tools_materials'] ?? null;

        return DB::connection('ac_service')->transaction(function () use ($serviceOrder, $additionalFee, $toolsMaterials, $validated, $assignment) {
            $serviceOrder->update([
                'field_report_notes' => $validated['field_report_notes'],
                'field_report_additional_fee' => $additionalFee,
                'field_report_tools_materials' => $toolsMaterials ? json_encode($toolsMaterials) : null,
                'field_report_submitted_at' => now(),
            ]);

            // Mark assignment as done
            $assignment->update([
                'status' => 'done',
                'completed_at' => now(),
                'technician_notes' => $validated['field_report_notes'],
            ]);

            WorkflowStep::create([
                'service_order_id' => $serviceOrder->id,
                'step' => 'waiting_review',
                'actor_id' => auth()->id(),
                'actor_name' => auth()->user()->name,
                'actor_role' => auth()->user()->role,
                'notes' => 'Teknisi submits field report. ' . ($additionalFee > 0 ? "Additional fee: Rp " . number_format($additionalFee) : 'Tanpa biaya tambahan'),
            ]);

            $newStatus = 'waiting_review';
            $serviceOrder->update(['status' => $newStatus]);

            RealtimeSync::afterCommit('service_order.field_report_submitted', [
                'resource' => 'service_order',
                'resource_id' => $serviceOrder->id,
                'masjid_id' => $serviceOrder->masjid_id,
                'payload' => [
                    'status' => $newStatus,
                    'additional_fee' => $additionalFee,
                ],
            ]);

            $this->flushMonitoringCaches();
            return response()->json(['success' => true, 'status' => $newStatus]);
        });
    }

    // ============================================
    // APPROVE ADDITIONAL FEE (Manager)
    // ============================================
    public function approveAdditionalFee(ServiceOrder $serviceOrder): JsonResponse
    {
        if (!auth()->user()->isManager() && !auth()->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Akses tidak diizinkan.'], 403);
        }

        if (!$serviceOrder->needsAdditionalFeeApproval()) {
            return response()->json(['success' => false, 'message' => 'Tidak ada biaya tambahan untuk disetujui.'], 422);
        }

        // Only allow during waiting_review status (after technician submitted field report)
        if ($serviceOrder->status !== 'waiting_review') {
            return response()->json(['success' => false, 'message' => 'Order harus dalam status menunggu review.'], 422);
        }

        return DB::connection('ac_service')->transaction(function () use ($serviceOrder) {
            $serviceOrder->update([
                'manager_approved_additional_fee' => true,
                'additional_fee_approved_by' => auth()->id(),
                'additional_fee_approved_at' => now(),
            ]);

            WorkflowStep::create([
                'service_order_id' => $serviceOrder->id,
                'step' => 'waiting_payment',
                'actor_id' => auth()->id(),
                'actor_name' => auth()->user()->name,
                'actor_role' => auth()->user()->role,
                'notes' => 'Manager menyetujui biaya tambahan: Rp ' . number_format($serviceOrder->field_report_additional_fee) . '. Menunggu pembayaran biaya tambahan.',
            ]);

            $invoice = $serviceOrder->invoice;
            if ($invoice) {
                $newTotal = $invoice->total_price + $serviceOrder->field_report_additional_fee;
                $invoice->update(['total_price' => $newTotal]);
            }

            $serviceOrder->update(['status' => 'waiting_payment']);

            RealtimeSync::afterCommit('service_order.additional_fee_approved', [
                'resource' => 'service_order',
                'resource_id' => $serviceOrder->id,
                'masjid_id' => $serviceOrder->masjid_id,
                'payload' => ['status' => 'waiting_payment'],
            ]);

            $this->flushMonitoringCaches();
            return response()->json(['success' => true]);
        });
    }

    // ============================================
    // FRONTDESK CONFIRM ORDER SELESAI
    // ============================================
    public function frontdeskConfirmComplete(ServiceOrder $serviceOrder): JsonResponse
    {
        if (!auth()->user()->isFrontdesk() && !auth()->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Akses tidak diizinkan.'], 403);
        }

        if ($serviceOrder->status !== 'completed') {
            return response()->json(['success' => false, 'message' => 'Order belum selesai.'], 422);
        }

        if ($serviceOrder->frontdesk_confirmed_complete) {
            return response()->json(['success' => false, 'message' => 'Anda sudah mengkonfirmasi.'], 422);
        }

        return DB::connection('ac_service')->transaction(function () use ($serviceOrder) {
            $serviceOrder->update([
                'frontdesk_confirmed_complete' => true,
                'frontdesk_confirmed_by' => auth()->id(),
                'frontdesk_confirmed_at' => now(),
            ]);

            WorkflowStep::create([
                'service_order_id' => $serviceOrder->id,
                'step' => 'closed',
                'actor_id' => auth()->id(),
                'actor_name' => auth()->user()->name,
                'actor_role' => auth()->user()->role,
                'notes' => 'Frontdesk konfirmasi Order Selesai',
            ]);

            $this->updateMasjidLastService($serviceOrder);

            RealtimeSync::afterCommit('service_order.frontdesk_confirmed', [
                'resource' => 'service_order',
                'resource_id' => $serviceOrder->id,
                'payload' => ['dual_confirmed' => true],
            ]);

            $this->flushMonitoringCaches();
            return response()->json(['success' => true]);
        });
    }

    // ============================================
    // MANAGER CONFIRM ORDER SELESAI
    // ============================================
    public function managerConfirmComplete(ServiceOrder $serviceOrder): JsonResponse
    {
        if (!auth()->user()->isManager() && !auth()->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Akses tidak diizinkan.'], 403);
        }

        if ($serviceOrder->status !== 'completed') {
            return response()->json(['success' => false, 'message' => 'Order belum selesai.'], 422);
        }

        if ($serviceOrder->manager_confirmed_complete) {
            return response()->json(['success' => false, 'message' => 'Anda sudah mengkonfirmasi.'], 422);
        }

        return DB::connection('ac_service')->transaction(function () use ($serviceOrder) {
            $serviceOrder->update([
                'manager_confirmed_complete' => true,
                'manager_confirmed_by' => auth()->id(),
                'manager_confirmed_at' => now(),
            ]);

            WorkflowStep::create([
                'service_order_id' => $serviceOrder->id,
                'step' => 'closed',
                'actor_id' => auth()->id(),
                'actor_name' => auth()->user()->name,
                'actor_role' => auth()->user()->role,
                'notes' => 'Manager konfirmasi Order Selesai',
            ]);

            $this->updateMasjidLastService($serviceOrder);

            RealtimeSync::afterCommit('service_order.manager_confirmed', [
                'resource' => 'service_order',
                'resource_id' => $serviceOrder->id,
                'payload' => ['dual_confirmed' => true],
            ]);

            $this->flushMonitoringCaches();
            return response()->json(['success' => true]);
        });
    }

    protected function updateMasjidLastService(ServiceOrder $serviceOrder): void
    {
        foreach ($serviceOrder->serviceDetails as $detail) {
            $units = $serviceOrder->masjid->acUnits
                ->where('pk_type', $detail->pk_type)
                ->where('brand', $detail->brand);

            foreach ($units as $unit) {
                $unit->update(['last_service_date' => $serviceOrder->service_date]);
            }
        }
    }

    /**
     * Sanitize text to remove potentially harmful content and image references
     */
    private function sanitizeText(?string $text): string
    {
        if (!$text) {
            return '';
        }
        // Delegate to centralized sanitizer for testability
        return \App\Utilities\TextSanitizer::sanitize($text);
    }
}
