<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\ServiceOrderHistory;
use App\Models\ServiceOrder;
use App\Models\TechnicianAssignment;
use App\Models\User;
use App\Models\WorkflowStep;
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

        return response()->json([
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

        if ($serviceOrder->status !== 'approved') {
            return response()->json(['success' => false, 'message' => 'Order harus approved sebelum bisa ditugaskan.'], 422);
        }

        // SPK & Invoice sudah dibuat dan disetujui sebelum assign

        $technician = User::findOrFail($validated['technician_id']);

        if (! $technician->isTechnician()) {
            return response()->json(['success' => false, 'message' => 'User yang dipilih bukan teknisi.'], 422);
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
            ]);

            $serviceOrder->update(['status' => 'approved']);

            WorkflowStep::create([
                'service_order_id' => $serviceOrder->id,
                'step' => 'assigned',
                'actor_id' => auth()->id(),
                'actor_name' => auth()->user()->name,
                'actor_role' => auth()->user()->role,
                'notes' => "Ditugaskan ke {$technician->name}".($validated['notes'] ? ". {$validated['notes']}" : ''),
            ]);

            RealtimeSync::afterCommit('workflow.assigned', [
                'resource' => 'service_order',
                'resource_id' => $serviceOrder->id,
                'masjid_id' => $serviceOrder->masjid_id,
                'service_order_id' => $serviceOrder->id,
                'payload' => [
                    'technician_id' => $technician->id,
                    'status' => 'approved',
                ],
            ]);

            $this->flushMonitoringCaches();
            return response()->json(['success' => true, 'message' => "Order berhasil ditugaskan ke {$technician->name}."]);
        });
    }

    public function updateProgress(Request $request, ServiceOrder $serviceOrder): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:in_progress,done',
            'notes' => 'nullable|string|max:500',
        ]);

        $assignment = $serviceOrder->technicianAssignment;

        if (! $assignment || $assignment->technician_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Anda tidak ditugaskan ke order ini.'], 403);
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

            $step = $validated['status'] === 'in_progress' ? 'in_progress' : 'completed';
            $orderStatus = $validated['status'] === 'in_progress' ? 'in_progress' : 'waiting_review';

            WorkflowStep::create([
                'service_order_id' => $serviceOrder->id,
                'step' => $step,
                'actor_id' => auth()->id(),
                'actor_name' => auth()->user()->name,
                'actor_role' => 'technician',
                'notes' => $validated['notes'] ?? null,
            ]);

            $serviceOrder->update(['status' => $orderStatus]);

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
            return response()->json(['success' => true, 'message' => 'Progress berhasil diperbarui.']);
        });
    }

    public function createSpkInvoice(Request $request, ServiceOrder $serviceOrder): JsonResponse
    {
        if (!auth()->user()->isFrontdesk() && !auth()->user()->isManager() && !auth()->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Hanya frontdesk, manager, atau admin yang bisa membuat SPK & Invoice.'], 403);
        }

        if ($serviceOrder->invoice || $serviceOrder->status === 'approved') {
            return response()->json(['success' => false, 'message' => 'SPK & Invoice sudah dibuat dan disetujui.'], 422);
        }

        $validated = $request->validate(['notes' => 'nullable|string|max:500']);

        $total = $serviceOrder->serviceDetails->sum(fn ($detail) => ($detail->quantity ?? 0) * ($detail->price_per_unit ?? 0));

        // Create Invoice
        $serviceOrder->invoice()->create([
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'total_price' => $total,
        ]);

        return DB::connection('ac_service')->transaction(function () use ($validated, $serviceOrder) {
            $serviceOrder->update(['status' => 'approved']);

            WorkflowStep::create([
                'service_order_id' => $serviceOrder->id,
                'step' => 'spk_invoice_created_approved',
                'actor_id' => auth()->id(),
                'actor_name' => auth()->user()->name,
                'actor_role' => auth()->user()->role,
                'notes' => $validated['notes'] ?? 'SPK & Invoice dibuat dan disetujui.',
            ]);

            $this->flushMonitoringCaches();
            RealtimeSync::afterCommit('workflow.spk_invoice_created_approved', [
                'resource' => 'service_order',
                'resource_id' => $serviceOrder->id,
                'masjid_id' => $serviceOrder->masjid_id,
            ]);

            return response()->json(['success' => true, 'message' => 'SPK & Invoice berhasil dibuat dan disetujui.']);
        });
    }

    public function technicians(): JsonResponse
    {
        $techs = User::where('role', 'technician')
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return response()->json($techs);
    }

    // New: Retrieve history entries for a specific service order
    public function orderHistory(ServiceOrder $serviceOrder): JsonResponse
    {
        $histories = $serviceOrder->histories()
            ->orderByDesc('archived_at')
            ->get(['archived_at', 'summary', 'order_snapshot']);

        return response()->json(['histories' => $histories]);
    }

    // New: Archive a service order into the service_order_histories table
    public function archiveOrder(Request $request, ServiceOrder $serviceOrder): JsonResponse
    {
        if ($serviceOrder->archived_at) {
            return response()->json(['success' => true, 'archived' => true]);
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

        return response()->json(['success' => true, 'archived' => true]);
    }

    // New: Approve SPK & Invoice via existing flow (wrapper for reuse)
    public function approveSpkInvoice(Request $request, ServiceOrder $serviceOrder): JsonResponse
    {
        return $this->createSpkInvoice($request, $serviceOrder);
    }

    protected function flushMonitoringCaches(): void
    {
        Cache::forget('monitoring:status_counts');
        Cache::forget('monitoring:status_totals');
    }
}
