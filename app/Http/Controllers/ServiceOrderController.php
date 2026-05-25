<?php

namespace App\Http\Controllers;

use App\Models\GuestOrder;
use App\Models\Invoice;
use App\Models\Masjid;
use App\Models\ServiceDetail;
use App\Models\ServiceOrder;
use App\Models\WorkflowStep;
use App\Support\ApiResponse;
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
        $validated = $request->validate(self::serviceOrderValidationRules());
        $masjid = $this->loadMasjidWithAcUnits($validated['masjid_id']);

        $existingOrder = $this->findExistingActiveOrder($validated['masjid_id']);

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

        $error = $this->validateAcUnitAvailability($masjid, $validated['details']);
        if ($error) {
            return ApiResponse::error($error, 422);
        }

        $order = $this->createOrderInTransaction($validated, $request, $masjid, $existingOrder, [
            'id' => auth()->id(),
            'name' => auth()->user()->name,
            'role' => auth()->user()->role,
        ]);

        $this->flushMonitoringCaches();

        return ApiResponse::success([
            'order' => $order->fresh('masjid', 'serviceDetails'),
        ]);
    }

    public function guestStore(Request $request)
    {
        $validated = $request->validate([
            'reporter_name' => 'required|string|max:100',
            'masjid_name' => 'required|string|max:200',
            'masjid_address' => 'required|string|max:500',
            'meeting_person' => 'required|in:dkm,marbot',
            'phone' => 'required|string|max:20',
            'service_date' => 'required|date|after_or_equal:today',
            'notes' => 'nullable|string|max:1000',
            'details' => 'required|array|min:1',
            'details.*.pk_type' => 'required|in:1PK,2PK,5PK',
            'details.*.brand' => ['required', 'string', 'max:100', 'regex:/^[\pL\pN\s.\-+\/()]+$/u'],
            'details.*.quantity' => 'required|integer|min:1|max:100',
        ]);

        // Find existing masjid
        $masjid = Masjid::where('name', $validated['masjid_name'])->first();

        // Calculate total AC units from details
        $acType = $validated['details'][0]['pk_type'] ?? '1PK';
        $acAmount = collect($validated['details'])->sum('quantity');

        // Build problem description from details + notes
        $detailLines = collect($validated['details'])->map(fn ($d) => "{$d['quantity']} unit {$d['pk_type']} ({$d['brand']})")->implode(', ');
        $problemDesc = "AC: {$detailLines}";
        if (!empty($validated['notes'])) {
            $problemDesc .= "\nCatatan: {$validated['notes']}";
        }

        // Store brand from first detail
        $brand = $validated['details'][0]['brand'] ?? '-';

        // Create GuestOrder with pending_review status (goes to frontdesk inbox)
        GuestOrder::create([
            'guest_name' => $validated['reporter_name'],
            'guest_phone' => $validated['phone'],
            'masjid_id' => $masjid?->id,
            'masjid_name' => $validated['masjid_name'],
            'address' => $validated['masjid_address'],
            'ac_type' => $acType,
            'ac_amount' => $acAmount,
            'brand' => $brand,
            'problem_description' => $problemDesc,
            'status' => 'pending_review',
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Permintaan service order Anda berhasil terkirim dan sedang meninjau oleh tim kami. Kami akan menghubungi Anda segera.');
    }

    public function approve(ServiceOrder $serviceOrder): JsonResponse
    {
        if ($serviceOrder->status !== 'pending_review') {
            return ApiResponse::error('Order tidak dalam status menunggu persetujuan manager.', 422);
        }

        return DB::connection('ac_service')->transaction(function () use ($serviceOrder) {
            // ── 1. Build & save Invoice ───────────────────────────────
            // ── 2. Transition directly to waiting_payment ─────────────
            // Manager approval + invoice creation = ready for payment.
            // Previously set to 'approved' which was a dead-end because
            // createSpkInvoice refused to run when invoice already existed.
            $serviceOrder->update(['status' => 'approved']);

            // ── 3. Record workflow steps ──────────────────────────────
            // Step A: Record the approval
            WorkflowStep::create([
                'service_order_id' => $serviceOrder->id,
                'step' => 'approved',
                'actor_id' => auth()->id(),
                'actor_name' => auth()->user()->name,
                'actor_role' => auth()->user()->role,
                'notes' => 'Order disetujui manager dan siap dibuatkan SPK & Invoice.',
            ]);

            // ── 4. Real-time broadcast ─────────────────────────────────
            RealtimeSync::afterCommit('service_order.approved', [
                'resource' => 'service_order',
                'resource_id' => $serviceOrder->id,
                'masjid_id' => $serviceOrder->masjid_id,
                'service_order_id' => $serviceOrder->id,
                'payload' => ['status' => 'approved'],
            ]);

            $this->flushMonitoringCaches();

            return ApiResponse::success([
                'service_order_id' => $serviceOrder->id,
                'status' => $serviceOrder->fresh()->status,
            ]);
        });
    }

    public function cancelApprove(ServiceOrder $serviceOrder): JsonResponse
    {
        if (! in_array($serviceOrder->status, ['approved', 'spk_invoice_created', 'spk_invoice_approved'], true)) {
            return ApiResponse::error('Order tidak dapat dikembalikan ke status menunggu persetujuan manager.', 422);
        }

        return DB::connection('ac_service')->transaction(function () use ($serviceOrder) {
            $serviceOrder->update(['status' => 'pending_review']);
            $serviceOrder->invoice?->delete();
            $serviceOrder->technicianAssignment()->delete();

            WorkflowStep::create([
                'service_order_id' => $serviceOrder->id,
                'step' => 'cancelled',
                'actor_id' => auth()->id(),
                'actor_name' => auth()->user()->name,
                'actor_role' => auth()->user()->role,
                'notes' => 'Approval dibatalkan dan order dikembalikan ke status menunggu persetujuan manager.',
            ]);

            RealtimeSync::afterCommit('service_order.cancelled', [
                'resource' => 'service_order',
                'resource_id' => $serviceOrder->id,
                'masjid_id' => $serviceOrder->masjid_id,
                'service_order_id' => $serviceOrder->id,
                'payload' => [
                    'status' => 'pending_review',
                ],
            ]);

            $this->flushMonitoringCaches();
            return ApiResponse::success();
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
            return ApiResponse::success([
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

        return ApiResponse::raw($orders);
    }

    public function generateInvoice(ServiceOrder $serviceOrder): JsonResponse
    {
        if (! auth()->user()->isFrontdesk() && ! auth()->user()->isAdmin()) {
            return ApiResponse::forbidden();
        }

        if ($serviceOrder->status !== 'approved') {
            return ApiResponse::error('Order harus disetujui manager terlebih dahulu.', 422);
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

            $serviceOrder->update(['status' => 'spk_invoice_created']);
            WorkflowStep::create([
                'service_order_id' => $serviceOrder->id,
                'step' => 'spk_invoice_created',
                'actor_id' => auth()->id(),
                'actor_name' => auth()->user()->name,
                'actor_role' => auth()->user()->role,
                'notes' => 'SPK & Invoice diterbitkan, menunggu persetujuan manager.',
            ]);

            RealtimeSync::afterCommit('service_order.invoice_generated', [
                'resource' => 'service_order',
                'resource_id' => $serviceOrder->id,
                'masjid_id' => $serviceOrder->masjid_id,
                'service_order_id' => $serviceOrder->id,
                'payload' => [
                    'status' => 'spk_invoice_created',
                ],
            ]);

            $this->flushMonitoringCaches();
            return ApiResponse::success();
        });
    }

    public function confirmPayment(ServiceOrder $serviceOrder): JsonResponse
    {
        if (! auth()->user()->isManager() && ! auth()->user()->isAdmin()) {
            return ApiResponse::forbidden();
        }

        if (! $serviceOrder->invoice) {
            return ApiResponse::error('Invoice belum dibuat.', 422);
        }

        if ($serviceOrder->status !== 'waiting_payment') {
            return ApiResponse::error('Order tidak menunggu pembayaran.', 422);
        }

        if ($serviceOrder->needsAdditionalFeeApproval()) {
            return ApiResponse::error('Biaya tambahan harus disetujui manager sebelum pembayaran.', 422);
        }

        return DB::connection('ac_service')->transaction(function () use ($serviceOrder) {
            // Column safety: some environments may not have payment_verified_at yet.
            $invoicePayload = [
                'updated_at' => now(),
                'payment_verified_by' => auth()->id(),
                'payment_verified_by_name' => auth()->user()->name,
            ];
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
            return ApiResponse::success();
        });
    }

    public function finalizeOrder(ServiceOrder $serviceOrder): JsonResponse
    {
        if (! auth()->user()->isManager() && ! auth()->user()->isAdmin()) {
            return ApiResponse::forbidden();
        }

        $canFinalizeFieldWork = $serviceOrder->status === 'waiting_review';
        $canCompleteOrder = $serviceOrder->status === 'payment_verified';

        if (! $canFinalizeFieldWork && ! $canCompleteOrder) {
            return ApiResponse::error('Order belum siap untuk diproses ke tahap berikutnya.', 422);
        }

        if ($canFinalizeFieldWork && $serviceOrder->needsAdditionalFeeApproval()) {
            return ApiResponse::error('Biaya tambahan harus disetujui manager sebelum finalisasi pembayaran.', 422);
        }

        if ($canCompleteOrder) {
            $assignment = $serviceOrder->technicianAssignment;
            if (! $assignment || $assignment->status !== 'done') {
                return ApiResponse::error('Order belum memiliki pekerjaan teknisi yang selesai.', 422);
            }
        }

        return DB::connection('ac_service')->transaction(function () use ($serviceOrder) {
            if ($serviceOrder->status === 'waiting_review') {
                $hasApprovedAdditionalFee = (float) ($serviceOrder->field_report_additional_fee ?? 0) > 0
                    && (bool) $serviceOrder->manager_approved_additional_fee;

                if ($hasApprovedAdditionalFee) {
                    $serviceOrder->update(['status' => 'waiting_payment']);

                    $invoice = $serviceOrder->invoice;
                    if ($invoice) {
                        $invoice->update([
                            'payment_verified_at' => null,
                            'payment_verified_by' => null,
                            'payment_verified_by_name' => null,
                            'payment_notes' => null,
                            'payment_metadata' => null,
                            'cash_confirmed_at' => null,
                            'cash_confirmed_by' => null,
                            'cash_confirmed_by_name' => null,
                        ]);
                    }

                    WorkflowStep::create([
                        'service_order_id' => $serviceOrder->id,
                        'step' => 'waiting_payment',
                        'actor_id' => auth()->id(),
                        'actor_name' => auth()->user()->name,
                        'actor_role' => auth()->user()->role,
                        'notes' => 'Pekerjaan lapangan difinalisasi. Menunggu pembayaran biaya tambahan.',
                    ]);

                    RealtimeSync::afterCommit('service_order.ready_for_payment', [
                        'resource' => 'service_order',
                        'resource_id' => $serviceOrder->id,
                        'masjid_id' => $serviceOrder->masjid_id,
                        'service_order_id' => $serviceOrder->id,
                        'payload' => ['status' => 'waiting_payment'],
                    ]);

                    $this->flushMonitoringCaches();
                    return ApiResponse::success([], 'Pekerjaan selesai. Order sekarang menunggu pembayaran biaya tambahan.');
                }

                $serviceOrder->update(['status' => 'completed']);

                WorkflowStep::create([
                    'service_order_id' => $serviceOrder->id,
                    'step' => 'completed',
                    'actor_id' => auth()->id(),
                    'actor_name' => auth()->user()->name,
                    'actor_role' => auth()->user()->role,
                    'notes' => 'Pekerjaan lapangan difinalisasi tanpa biaya tambahan. Order selesai.',
                ]);

                RealtimeSync::afterCommit('service_order.completed_after_review', [
                    'resource' => 'service_order',
                    'resource_id' => $serviceOrder->id,
                    'masjid_id' => $serviceOrder->masjid_id,
                    'service_order_id' => $serviceOrder->id,
                    'payload' => ['status' => 'completed'],
                ]);

                $this->flushMonitoringCaches();
                return ApiResponse::success([], 'Pekerjaan selesai. Order langsung ditutup tanpa pembayaran tambahan.');
            }

            $serviceOrder->update(['status' => 'completed']);

            WorkflowStep::create([
                'service_order_id' => $serviceOrder->id,
                'step' => 'completed',
                'actor_id' => auth()->id(),
                'actor_name' => auth()->user()->name,
                'actor_role' => auth()->user()->role,
                'notes' => 'Pembayaran diverifikasi dan order dinyatakan selesai.',
            ]);

            foreach ($serviceOrder->serviceDetails as $detail) {
                $units = $serviceOrder->masjid->acUnits
                    ->where('pk_type', $detail->pk_type)
                    ->where('brand', $detail->brand);
                foreach ($units as $unit) {
                    $unit->update(['last_service_date' => $serviceOrder->service_date]);
                }
            }

            RealtimeSync::afterCommit('service_order.completed_after_payment', [
                'resource' => 'service_order',
                'resource_id' => $serviceOrder->id,
                'masjid_id' => $serviceOrder->masjid_id,
                'service_order_id' => $serviceOrder->id,
                'payload' => ['status' => 'completed'],
            ]);

            $this->flushMonitoringCaches();
            return ApiResponse::success([], 'Pembayaran diverifikasi. Order selesai dan siap untuk konfirmasi akhir.');
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

        return ApiResponse::success([
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
        $expired = ServiceOrder::whereIn('status', ['pending_review', 'approved', 'spk_invoice_created'])
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

        return ApiResponse::raw(['cleaned' => $expired->count()]);
    }

    // ============================================
    // SHARED ORDER CREATION HELPERS
    // ============================================

    private static function serviceOrderValidationRules(): array
    {
        return [
            'masjid_id' => ['required', Rule::exists('ac_service.masjids', 'id')],
            'meeting_person' => 'required|in:dkm,marbot',
            'phone' => 'required|string|max:20',
            'service_date' => 'required|date|after_or_equal:today',
            'notes' => 'nullable|string|max:1000',
            'details' => 'required|array|min:1',
            'details.*.pk_type' => 'required|in:1PK,2PK,5PK',
            'details.*.brand' => ['required', 'string', 'max:100', 'regex:/^[\pL\pN\s.\-+\/()]+$/u'],
            'details.*.quantity' => 'required|integer|min:1|max:100',
        ];
    }

    private function loadMasjidWithAcUnits(int $masjidId): Masjid
    {
        return Masjid::with('acUnits')->findOrFail($masjidId);
    }

    private function findExistingActiveOrder(int $masjidId): ?ServiceOrder
    {
        return ServiceOrder::query()
            ->where('masjid_id', $masjidId)
            ->active()
            ->latest('service_date')
            ->latest()
            ->first();
    }

    private function validateAcUnitAvailability(Masjid $masjid, array $details): ?string
    {
        if ($masjid->acUnits->isEmpty()) {
            return null;
        }

        foreach ($details as $detail) {
            // Use total across ALL brands for this pk_type — matches frontend display
            $available = $masjid->acUnits
                ->where('pk_type', $detail['pk_type'])
                ->sum('quantity');

            if ($detail['quantity'] > $available) {
                return "Jumlah unit {$detail['pk_type']} melebihi unit tersedia ({$available})";
            }
        }

        return null;
    }

    private function createOrderInTransaction(
        array $validated,
        Request $request,
        Masjid $masjid,
        ?ServiceOrder $existingOrder,
        array $actorInfo
    ): ServiceOrder {
        return DB::connection('ac_service')->transaction(function () use ($validated, $request, $masjid, $existingOrder, $actorInfo) {
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
                'status' => 'pending_review',
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
                'actor_id' => $actorInfo['id'],
                'actor_name' => $actorInfo['name'],
                'actor_role' => $actorInfo['role'],
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
            return ApiResponse::forbidden();
        }

        // Verify the logged-in technician is the one assigned to this order
        $assignment = $serviceOrder->technicianAssignment;
        if (!$assignment || $assignment->technician_id !== auth()->id()) {
            return ApiResponse::error('Anda tidak ditugaskan ke order ini.', 403);
        }

        if ($serviceOrder->status !== 'in_progress') {
            return ApiResponse::error('Order belum dalam status service.', 422);
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
            return ApiResponse::success(['status' => $newStatus]);
        });
    }

    // ============================================
    // APPROVE ADDITIONAL FEE (Manager)
    // ============================================
    public function approveAdditionalFee(ServiceOrder $serviceOrder): JsonResponse
    {
        if (!auth()->user()->isManager() && !auth()->user()->isAdmin()) {
            return ApiResponse::forbidden();
        }

        if (!$serviceOrder->needsAdditionalFeeApproval()) {
            return ApiResponse::error('Tidak ada biaya tambahan untuk disetujui.', 422);
        }

        // Only allow during waiting_review status (after technician submitted field report)
        if ($serviceOrder->status !== 'waiting_review') {
            return ApiResponse::error('Order harus dalam status menunggu review.', 422);
        }

        if (! $serviceOrder->invoice) {
            return ApiResponse::error('Invoice belum dibuat.', 422);
        }

        return DB::connection('ac_service')->transaction(function () use ($serviceOrder) {
            $serviceOrder->update([
                'manager_approved_additional_fee' => true,
                'additional_fee_approved_by' => auth()->id(),
                'additional_fee_approved_at' => now(),
            ]);

            $invoice = $serviceOrder->invoice;
            $newTotal = $invoice->total_price + $serviceOrder->field_report_additional_fee;
            $invoice->update([
                'total_price' => $newTotal,
                'payment_verified_at' => null,
                'payment_verified_by' => null,
                'payment_verified_by_name' => null,
                'payment_notes' => null,
                'payment_metadata' => null,
                'cash_confirmed_at' => null,
                'cash_confirmed_by' => null,
                'cash_confirmed_by_name' => null,
            ]);

            $serviceOrder->update(['status' => 'waiting_payment']);

            WorkflowStep::create([
                'service_order_id' => $serviceOrder->id,
                'step' => 'waiting_payment',
                'actor_id' => auth()->id(),
                'actor_name' => auth()->user()->name,
                'actor_role' => auth()->user()->role,
                'notes' => 'Manager menyetujui biaya tambahan: Rp ' . number_format($serviceOrder->field_report_additional_fee) . '. Menunggu pembayaran biaya tambahan.',
            ]);

            RealtimeSync::afterCommit('service_order.additional_fee_approved', [
                'resource' => 'service_order',
                'resource_id' => $serviceOrder->id,
                'masjid_id' => $serviceOrder->masjid_id,
                'payload' => ['status' => 'waiting_payment'],
            ]);

            $this->flushMonitoringCaches();
            return ApiResponse::success();
        });
    }

    // ============================================
    // FRONTDESK CONFIRM ORDER SELESAI
    // ============================================
    public function frontdeskConfirmComplete(ServiceOrder $serviceOrder): JsonResponse
    {
        if (!auth()->user()->isFrontdesk() && !auth()->user()->isAdmin()) {
            return ApiResponse::forbidden();
        }

        if ($serviceOrder->status !== 'completed') {
            return ApiResponse::error('Order belum selesai.', 422);
        }

        if ($serviceOrder->frontdesk_confirmed_complete) {
            return ApiResponse::error('Anda sudah mengkonfirmasi.', 422);
        }

        return DB::connection('ac_service')->transaction(function () use ($serviceOrder) {
            $serviceOrder->update([
                'frontdesk_confirmed_complete' => true,
                'frontdesk_confirmed_by' => auth()->id(),
                'frontdesk_confirmed_at' => now(),
            ]);

            $updatedOrder = $serviceOrder->fresh();
            if (
                $updatedOrder->frontdesk_confirmed_complete
                && $updatedOrder->manager_confirmed_complete
                && ! $updatedOrder->workflowSteps()->where('step', 'closed')->exists()
            ) {
                WorkflowStep::create([
                    'service_order_id' => $serviceOrder->id,
                    'step' => 'closed',
                    'actor_id' => auth()->id(),
                    'actor_name' => auth()->user()->name,
                    'actor_role' => auth()->user()->role,
                    'notes' => 'Frontdesk dan manager telah mengonfirmasi order selesai.',
                ]);
            }

            $this->updateMasjidLastService($serviceOrder);

            RealtimeSync::afterCommit('service_order.frontdesk_confirmed', [
                'resource' => 'service_order',
                'resource_id' => $serviceOrder->id,
                'payload' => ['dual_confirmed' => true],
            ]);

            $this->flushMonitoringCaches();
            return ApiResponse::success();
        });
    }

    // ============================================
    // MANAGER CONFIRM ORDER SELESAI
    // ============================================
    public function managerConfirmComplete(ServiceOrder $serviceOrder): JsonResponse
    {
        if (!auth()->user()->isManager() && !auth()->user()->isAdmin()) {
            return ApiResponse::forbidden();
        }

        if ($serviceOrder->status !== 'completed') {
            return ApiResponse::error('Order belum selesai.', 422);
        }

        if ($serviceOrder->manager_confirmed_complete) {
            return ApiResponse::error('Anda sudah mengkonfirmasi.', 422);
        }

        return DB::connection('ac_service')->transaction(function () use ($serviceOrder) {
            $serviceOrder->update([
                'manager_confirmed_complete' => true,
                'manager_confirmed_by' => auth()->id(),
                'manager_confirmed_at' => now(),
            ]);

            $updatedOrder = $serviceOrder->fresh();
            if (
                $updatedOrder->frontdesk_confirmed_complete
                && $updatedOrder->manager_confirmed_complete
                && ! $updatedOrder->workflowSteps()->where('step', 'closed')->exists()
            ) {
                WorkflowStep::create([
                    'service_order_id' => $serviceOrder->id,
                    'step' => 'closed',
                    'actor_id' => auth()->id(),
                    'actor_name' => auth()->user()->name,
                    'actor_role' => auth()->user()->role,
                    'notes' => 'Frontdesk dan manager telah mengonfirmasi order selesai.',
                ]);
            }

            $this->updateMasjidLastService($serviceOrder);

            RealtimeSync::afterCommit('service_order.manager_confirmed', [
                'resource' => 'service_order',
                'resource_id' => $serviceOrder->id,
                'payload' => ['dual_confirmed' => true],
            ]);

            $this->flushMonitoringCaches();
            return ApiResponse::success();
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
