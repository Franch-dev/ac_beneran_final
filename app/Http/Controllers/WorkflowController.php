<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\ServiceOrderHistory;
use App\Models\ServiceOrder;
use App\Models\TechnicianAssignment;
use App\Models\User;
use App\Models\WorkflowStep;
use App\Support\ApiResponse;
use App\Support\RealtimeSync;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class WorkflowController extends Controller
{
    public function timeline(ServiceOrder $serviceOrder): JsonResponse
    {
        $steps = $serviceOrder->workflowSteps()
            ->orderBy('created_at')
            ->get()
            ->map(fn ($step) => [
                'id' => $step->id,
                'step' => $step->step,
                'label' => WorkflowStep::stepLabel($step->step),
                'icon' => WorkflowStep::stepIcon($step->step),
                'color' => WorkflowStep::stepColor($step->step),
                'actor_name' => $step->actor_name,
                'actor_role' => $step->actor_role,
                'notes' => $step->notes,
                'time' => $step->created_at->format('d M Y, H:i'),
            ]);

        $assignment = $serviceOrder->technicianAssignment;

        return ApiResponse::success([
            'steps' => $steps,
            'assignment' => $assignment ? [
                'technician_name' => $assignment->technician_name,
                'status' => $assignment->status,
                'started_at' => $assignment->started_at?->format('d M Y, H:i'),
                'completed_at' => $assignment->completed_at?->format('d M Y, H:i'),
                'notes' => $assignment->technician_notes,
            ] : null,
        ]);
    }

    public function assign(Request $request, ServiceOrder $serviceOrder): JsonResponse
    {
        $validated = $request->validate([
            'technician_id' => ['required', Rule::exists('main.users', 'id')],
            'notes' => 'nullable|string|max:500',
        ]);

        $existingAssignment = $serviceOrder->technicianAssignment;
        $canInitialAssign = $serviceOrder->status === 'payment_verified';
        $canReassignBeforeWorkStarts = $serviceOrder->status === 'technician_assigned'
            && $existingAssignment
            && $existingAssignment->status === 'assigned'
            && is_null($existingAssignment->started_at)
            && is_null($existingAssignment->completed_at);

        if (! $canInitialAssign && ! $canReassignBeforeWorkStarts) {
            return ApiResponse::error('Order hanya bisa ditugaskan setelah pembayaran diverifikasi.', 422);
        }

        $technician = User::findOrFail($validated['technician_id']);

        if (! $technician->isTechnician()) {
            return ApiResponse::error('User yang dipilih bukan teknisi.', 422);
        }

        return DB::connection('ac_service')->transaction(function () use ($validated, $serviceOrder, $technician) {
            $serviceOrder->technicianAssignment()->delete();

            TechnicianAssignment::create([
                'service_order_id' => $serviceOrder->id,
                'technician_id' => $technician->id,
                'technician_name' => $technician->name,
                'assigned_by' => auth()->id(),
                'assigned_by_name' => auth()->user()->name,
                'status' => 'assigned',
                'assigned_at' => now(),
            ]);

            $serviceOrder->update(['status' => 'technician_assigned']);

            WorkflowStep::create([
                'service_order_id' => $serviceOrder->id,
                'step' => 'assigned',
                'actor_id' => auth()->id(),
                'actor_name' => auth()->user()->name,
                'actor_role' => auth()->user()->role,
                'notes' => "Ditugaskan ke {$technician->name}".(! empty($validated['notes']) ? ". {$validated['notes']}" : ''),
            ]);

            RealtimeSync::afterCommit('workflow.assigned', [
                'resource' => 'service_order',
                'resource_id' => $serviceOrder->id,
                'masjid_id' => $serviceOrder->masjid_id,
                'service_order_id' => $serviceOrder->id,
                'payload' => [
                    'technician_id' => $technician->id,
                    'status' => 'technician_assigned',
                ],
            ]);

            $this->flushMonitoringCaches();
            return ApiResponse::success([], "Order berhasil ditugaskan ke {$technician->name}.");
        });
    }

    public function updateProgress(Request $request, ServiceOrder $serviceOrder): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:in_progress,done',
            'notes' => 'nullable|string|max:500',
        ]);

        if (!in_array($serviceOrder->status, ['technician_assigned', 'in_progress'], true)) {
            return ApiResponse::error('Order tidak sedang dalam pengerjaan.', 422);
        }

        $assignment = $serviceOrder->technicianAssignment;

        if (! $assignment || $assignment->technician_id !== auth()->id()) {
            return ApiResponse::error('Anda tidak ditugaskan ke order ini.', 403);
        }

        if (
            $validated['status'] === 'done'
            && ($serviceOrder->status !== 'in_progress' || $assignment->status !== 'in_progress')
        ) {
            return ApiResponse::error('Teknisi hanya bisa menandai selesai setelah pekerjaan dimulai.', 422);
        }

        $updateData = ['status' => $validated['status'], 'technician_notes' => $validated['notes'] ?? null];

        if ($validated['status'] === 'in_progress' && ! $assignment->started_at) {
            $updateData['started_at'] = now();
        }

        if ($validated['status'] === 'done') {
            $updateData['completed_at'] = now();
        }

        return DB::connection('ac_service')->transaction(function () use ($assignment, $updateData, $validated, $serviceOrder) {
            $assignment->update($updateData);

            $isDone = $validated['status'] === 'done';
            $step = $isDone ? 'waiting_review' : 'in_progress';
            $orderStatus = $isDone ? 'waiting_review' : 'in_progress';

            WorkflowStep::create([
                'service_order_id' => $serviceOrder->id,
                'step' => $step,
                'actor_id' => auth()->id(),
                'actor_name' => auth()->user()->name,
                'actor_role' => 'technician',
                'notes' => $validated['notes'] ?? null,
            ]);

            if ($isDone) {
                $serviceOrder->update([
                    'status' => $orderStatus,
                    'field_report_notes' => $validated['notes'] ?? $serviceOrder->field_report_notes,
                    'field_report_additional_fee' => $serviceOrder->field_report_additional_fee ?? 0,
                    'field_report_submitted_at' => $serviceOrder->field_report_submitted_at ?? now(),
                ]);
            } else {
                $serviceOrder->update(['status' => $orderStatus]);
            }

            RealtimeSync::afterCommit('workflow.progress_updated', [
                'resource' => 'service_order',
                'resource_id' => $serviceOrder->id,
                'masjid_id' => $serviceOrder->masjid_id,
                'service_order_id' => $serviceOrder->id,
                'payload' => [
                    'assignment_status' => $validated['status'],
                    'status' => $orderStatus,
                ],
            ]);

            $this->flushMonitoringCaches();
            return ApiResponse::success([], 'Progress berhasil diperbarui.');
        });
    }

    public function close(Request $request, ServiceOrder $serviceOrder): JsonResponse
    {
        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        if ($serviceOrder->status !== 'completed') {
            return ApiResponse::error('Order harus berstatus selesai sebelum ditutup.', 422);
        }

        if (! $serviceOrder->frontdesk_confirmed_complete || ! $serviceOrder->manager_confirmed_complete) {
            return ApiResponse::error('Order harus dikonfirmasi frontdesk dan manager sebelum ditutup.', 422);
        }

        $alreadyClosed = $serviceOrder->workflowSteps()
            ->where('step', 'closed')
            ->exists();

        if ($alreadyClosed) {
            return ApiResponse::success([], 'Order sudah ditutup sebelumnya.');
        }

        return DB::connection('ac_service')->transaction(function () use ($validated, $serviceOrder) {
            WorkflowStep::create([
                'service_order_id' => $serviceOrder->id,
                'step' => 'closed',
                'actor_id' => auth()->id(),
                'actor_name' => auth()->user()->name,
                'actor_role' => auth()->user()->role,
                'notes' => $validated['notes'] ?? 'Order ditutup oleh manager.',
            ]);

            RealtimeSync::afterCommit('workflow.closed', [
                'resource' => 'service_order',
                'resource_id' => $serviceOrder->id,
                'masjid_id' => $serviceOrder->masjid_id,
                'service_order_id' => $serviceOrder->id,
                'payload' => ['status' => 'completed', 'closed' => true],
            ]);

            $this->flushMonitoringCaches();

            return ApiResponse::success([], 'Order berhasil ditutup.');
        });
    }

    public function createSpkInvoice(Request $request, ServiceOrder $serviceOrder): JsonResponse
    {
        if (!auth()->user()->isFrontdesk() && !auth()->user()->isAdmin()) {
            return ApiResponse::error('Hanya frontdesk atau admin yang bisa membuat SPK & Invoice.', 403);
        }

        if ($serviceOrder->invoice) {
            return ApiResponse::error('Invoice sudah dibuat.', 422);
        }

        if (!in_array($serviceOrder->status, ['approved'])) {
            return ApiResponse::error('Order harus dalam status Disetujui untuk membuat SPK & Invoice.', 422);
        }

        $validated = $request->validate(['notes' => 'nullable|string|max:500']);

        $total = $serviceOrder->serviceDetails->sum(fn ($detail) => ($detail->quantity ?? 0) * ($detail->price_per_unit ?? 0));

        return DB::connection('ac_service')->transaction(function () use ($validated, $serviceOrder, $total) {
            $serviceOrder->invoice()->create([
                'invoice_number' => Invoice::generateInvoiceNumber(),
                'total_price' => $total,
            ]);
            $serviceOrder->update(['status' => 'spk_invoice_created']);

            WorkflowStep::create([
                'service_order_id' => $serviceOrder->id,
                'step' => 'spk_invoice_created',
                'actor_id' => auth()->id(),
                'actor_name' => auth()->user()->name,
                'actor_role' => auth()->user()->role,
                'notes' => $validated['notes'] ?? 'SPK & Invoice diterbitkan, menunggu persetujuan manager.',
            ]);

            $this->flushMonitoringCaches();

            return ApiResponse::success([], 'SPK & Invoice diterbitkan. Menunggu persetujuan manager.');
        });
    }

    /**
     * Manager approves SPK & Invoice → spk_invoice_approved
     */
    public function approveSpkInvoice(Request $request, ServiceOrder $serviceOrder): JsonResponse
    {
        if (!auth()->user()->isManager() && !auth()->user()->isAdmin()) {
            return ApiResponse::error('Hanya manager atau admin yang bisa menyetujui SPK & Invoice.', 403);
        }

        if ($serviceOrder->status !== 'spk_invoice_created') {
            return ApiResponse::error('Order harus dalam status SPK & Invoice dibuat.', 422);
        }

        if (! $serviceOrder->invoice) {
            return ApiResponse::error('Invoice belum dibuat oleh frontdesk.', 422);
        }

        $validated = $request->validate(['notes' => 'nullable|string|max:500']);

        return DB::connection('ac_service')->transaction(function () use ($validated, $serviceOrder) {
            $serviceOrder->update(['status' => 'waiting_payment']);

            WorkflowStep::create([
                'service_order_id' => $serviceOrder->id,
                'step' => 'spk_invoice_approved',
                'actor_id' => auth()->id(),
                'actor_name' => auth()->user()->name,
                'actor_role' => auth()->user()->role,
                'notes' => $validated['notes'] ?? 'SPK & Invoice disetujui manager.',
            ]);

            WorkflowStep::create([
                'service_order_id' => $serviceOrder->id,
                'step' => 'waiting_payment',
                'actor_id' => auth()->id(),
                'actor_name' => auth()->user()->name,
                'actor_role' => auth()->user()->role,
                'notes' => 'Menunggu konfirmasi pembayaran sebelum teknisi ditugaskan.',
            ]);

            $this->flushMonitoringCaches();

            return ApiResponse::success([], 'SPK & Invoice disetujui. Order masuk tahap menunggu pembayaran.');
        });
    }

    public function technicians(): JsonResponse
    {
        $techs = User::where('role', 'technician')
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return ApiResponse::raw($techs);
    }

    // New: Retrieve history entries for a specific service order
    public function orderHistory(ServiceOrder $serviceOrder): JsonResponse
    {
        $histories = $serviceOrder->histories()
            ->orderByDesc('archived_at')
            ->get(['archived_at', 'summary', 'order_snapshot']);

        return ApiResponse::success(['histories' => $histories]);
    }

    // New: Archive a service order into the service_order_histories table
    public function archiveOrder(Request $request, ServiceOrder $serviceOrder): JsonResponse
    {
        if ($serviceOrder->archived_at) {
            return ApiResponse::success(['archived' => true]);
        }

        $snapshot = [
            'order_id' => $serviceOrder->id,
            'masjid_name' => $serviceOrder->masjid?->name,
            'musholla_name' => method_exists($serviceOrder, 'musholla') ? ($serviceOrder->musholla?->name) : null,
            'status' => $serviceOrder->status,
            'spk_id' => $serviceOrder->spk_id ?? null,
            'invoice_id' => $serviceOrder->invoice?->id ?? null,
        ];

        ServiceOrderHistory::create([
            'service_order_id' => $serviceOrder->id,
            'archived_at' => now(),
            'summary' => 'Archived to service history',
            'order_snapshot' => $snapshot,
            'archived_by_id' => auth()->id(),
        ]);

        $serviceOrder->update(['archived_at' => now()]);

        // Clear relevant caches if any
        $this->flushMonitoringCaches();

        return ApiResponse::success(['archived' => true]);
    }

    protected function flushMonitoringCaches(): void
    {
        Cache::forget('monitoring:status_counts');
        Cache::forget('monitoring:status_totals');
    }
}
