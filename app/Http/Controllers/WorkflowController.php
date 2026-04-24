<?php

namespace App\Http\Controllers;

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

        // Check if invoice exists - manager must create invoice before assigning
        if (!$serviceOrder->invoice) {
            return response()->json(['success' => false, 'message' => 'SPK dan Invoice harus dibuat sebelum menugaskan teknisi. Silakan buat Invoice terlebih dahulu.'], 422);
        }

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

    public function close(Request $request, ServiceOrder $serviceOrder): JsonResponse
    {
        if (! auth()->user()->isManager() && ! auth()->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Akses tidak diizinkan.'], 403);
        }

        $validated = $request->validate(['notes' => 'nullable|string|max:500']);

        return DB::connection('ac_service')->transaction(function () use ($validated, $serviceOrder) {
            $serviceOrder->update(['status' => 'completed']);

            WorkflowStep::create([
                'service_order_id' => $serviceOrder->id,
                'step' => 'closed',
                'actor_id' => auth()->id(),
                'actor_name' => auth()->user()->name,
                'actor_role' => auth()->user()->role,
                'notes' => $validated['notes'] ?? 'Order ditutup.',
            ]);

            RealtimeSync::afterCommit('workflow.closed', [
                'resource' => 'service_order',
                'resource_id' => $serviceOrder->id,
                'masjid_id' => $serviceOrder->masjid_id,
                'service_order_id' => $serviceOrder->id,
                'payload' => [
                    'status' => 'completed',
                ],
            ]);

            $this->flushMonitoringCaches();
            return response()->json(['success' => true, 'message' => 'Order berhasil ditutup.']);
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

    protected function flushMonitoringCaches(): void
    {
        Cache::forget('monitoring:status_counts');
        Cache::forget('monitoring:status_totals');
    }
}
