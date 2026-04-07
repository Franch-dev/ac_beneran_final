<?php

namespace App\Http\Controllers;

use App\Models\ServiceOrder;
use App\Models\WorkflowStep;
use App\Models\TechnicianAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class WorkflowController extends Controller
{
    /**
     * Get full workflow timeline for an order (JSON)
     */
    public function timeline(ServiceOrder $serviceOrder)
    {
        $steps = $serviceOrder->workflowSteps()->orderBy('created_at')->get()->map(fn($s) => [
            'id'         => $s->id,
            'step'       => $s->step,
            'label'      => WorkflowStep::stepLabel($s->step),
            'icon'       => WorkflowStep::stepIcon($s->step),
            'color'      => WorkflowStep::stepColor($s->step),
            'actor_name' => $s->actor_name,
            'actor_role' => $s->actor_role,
            'notes'      => $s->notes,
            'time'       => $s->created_at->format('d M Y, H:i'),
        ]);

        $assignment = $serviceOrder->technicianAssignment;

        return response()->json([
            'steps'      => $steps,
            'assignment' => $assignment ? [
                'technician_name' => $assignment->technician_name,
                'status'          => $assignment->status,
                'started_at'      => $assignment->started_at?->format('d M Y, H:i'),
                'completed_at'    => $assignment->completed_at?->format('d M Y, H:i'),
                'notes'           => $assignment->technician_notes,
            ] : null,
        ]);
    }

    /**
     * Manager assigns technician to a service order
     */
    public function assign(Request $request, ServiceOrder $serviceOrder)
    {
        $request->validate([
            'technician_id' => ['required', Rule::exists('main.users', 'id')],
            'notes'         => 'nullable|string|max:500',
        ]);

        if ($serviceOrder->status !== 'approved') {
            return response()->json(['success' => false, 'message' => 'Order harus approved sebelum bisa ditugaskan.'], 422);
        }

        $technician = User::findOrFail($request->technician_id);

        if (!$technician->isTechnician()) {
            return response()->json(['success' => false, 'message' => 'User yang dipilih bukan teknisi.'], 422);
        }

        return DB::connection('ac_service')->transaction(function () use ($request, $serviceOrder, $technician) {
            // Remove existing assignment if any
            $serviceOrder->technicianAssignment()->delete();

            TechnicianAssignment::create([
                'service_order_id' => $serviceOrder->id,
                'technician_id'    => $technician->id,
                'technician_name'  => $technician->name,
                'assigned_by'      => auth()->id(),
                'assigned_by_name' => auth()->user()->name,
                'status'           => 'assigned',
            ]);

            $serviceOrder->update(['status' => 'approved']);

            WorkflowStep::create([
                'service_order_id' => $serviceOrder->id,
                'step'             => 'assigned',
                'actor_id'         => auth()->id(),
                'actor_name'       => auth()->user()->name,
                'actor_role'       => auth()->user()->role,
                'notes'            => "Ditugaskan ke {$technician->name}" . ($request->notes ? ". {$request->notes}" : ''),
            ]);

            return response()->json(['success' => true, 'message' => "Order berhasil ditugaskan ke {$technician->name}."]);
        });
    }

    /**
     * Technician updates their own assignment progress
     */
    public function updateProgress(Request $request, ServiceOrder $serviceOrder)
    {
        $request->validate([
            'status' => 'required|in:in_progress,done',
            'notes'  => 'nullable|string|max:500',
        ]);

        $assignment = $serviceOrder->technicianAssignment;

        if (!$assignment || $assignment->technician_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Anda tidak ditugaskan ke order ini.'], 403);
        }

        $updateData = ['status' => $request->status, 'technician_notes' => $request->notes];

        if ($request->status === 'in_progress' && !$assignment->started_at) {
            $updateData['started_at'] = now();
        }

        if ($request->status === 'done') {
            $updateData['completed_at'] = now();
        }

        DB::connection('ac_service')->transaction(function () use ($assignment, $updateData, $request, $serviceOrder) {
            $assignment->update($updateData);

            $step = $request->status === 'in_progress' ? 'in_progress' : 'completed';

            WorkflowStep::create([
                'service_order_id' => $serviceOrder->id,
                'step'             => $step,
                'actor_id'         => auth()->id(),
                'actor_name'       => auth()->user()->name,
                'actor_role'       => 'technician',
                'notes'            => $request->notes,
            ]);

            if ($request->status === 'in_progress') {
                $serviceOrder->update(['status' => 'in_progress']);
            }

            if ($request->status === 'done') {
                $serviceOrder->update(['status' => 'waiting_invoice']);
            }
        });

        return response()->json(['success' => true, 'message' => 'Progress berhasil diperbarui.']);
    }

    /**
     * Manager closes/completes a finished order
     */
    public function close(Request $request, ServiceOrder $serviceOrder)
    {
        if (!auth()->user()->isManager() && !auth()->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Akses tidak diizinkan.'], 403);
        }

        $request->validate(['notes' => 'nullable|string|max:500']);

        DB::connection('ac_service')->transaction(function () use ($request, $serviceOrder) {
            $serviceOrder->update(['status' => 'completed']);

            WorkflowStep::create([
                'service_order_id' => $serviceOrder->id,
                'step'             => 'closed',
                'actor_id'         => auth()->id(),
                'actor_name'       => auth()->user()->name,
                'actor_role'       => auth()->user()->role,
                'notes'            => $request->notes ?? 'Order ditutup.',
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Order berhasil ditutup.']);
    }

    /**
     * Get list of technicians for assignment dropdown
     */
    public function technicians()
    {
        $techs = User::where('role', 'technician')
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return response()->json($techs);
    }
}
