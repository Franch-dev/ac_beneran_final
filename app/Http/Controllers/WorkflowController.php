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
use App\Support\ServiceOrderWorkflow;
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

        $technician = User::findOrFail($validated['technician_id']);
        app(ServiceOrderWorkflow::class)->assignTechnician($serviceOrder, $technician, $validated['notes'] ?? null);

        return ApiResponse::success([], "Order berhasil ditugaskan ke {$technician->name}.");
    }

    public function updateProgress(Request $request, ServiceOrder $serviceOrder): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:in_progress,done',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validated['status'] === 'done') {
            return ApiResponse::error('Gunakan form penyelesaian pekerjaan agar foto bukti wajib terpenuhi.', 422);
        }

        app(ServiceOrderWorkflow::class)->startWork($serviceOrder, $validated['notes'] ?? null);

        return ApiResponse::success([], 'Progress berhasil diperbarui.');
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

        app(ServiceOrderWorkflow::class)->archiveClosed($serviceOrder, $validated['notes'] ?? 'Order ditutup oleh manager.');

        return ApiResponse::success([], 'Order berhasil ditutup dan diarsipkan.');
    }

    public function createSpkInvoice(Request $request, ServiceOrder $serviceOrder): JsonResponse
    {
        $validated = $request->validate(['notes' => 'nullable|string|max:500']);
        app(ServiceOrderWorkflow::class)->createSpkInvoice($serviceOrder, $validated['notes'] ?? null);

        return ApiResponse::success([], 'SPK & Invoice diterbitkan. Menunggu persetujuan manager.');
    }

    /**
     * Manager approves SPK & Invoice → spk_invoice_approved
     */
    public function approveSpkInvoice(Request $request, ServiceOrder $serviceOrder): JsonResponse
    {
        $validated = $request->validate(['notes' => 'nullable|string|max:500']);
        app(ServiceOrderWorkflow::class)->approveSpkInvoice($serviceOrder, $validated['notes'] ?? null);

        return ApiResponse::success([], 'SPK & Invoice disetujui. Silakan tugaskan teknisi.');
    }

    public function rejectSpkInvoice(Request $request, ServiceOrder $serviceOrder): JsonResponse
    {
        $validated = $request->validate(['notes' => 'required|string|max:500']);
        app(ServiceOrderWorkflow::class)->rejectSpkInvoice($serviceOrder, $validated['notes']);

        return ApiResponse::success([], 'SPK & Invoice ditolak.');
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
        $archivedOrder = app(ServiceOrderWorkflow::class)->archiveClosed($serviceOrder, 'Archived to service history');

        $snapshot = [
            'order_id' => $serviceOrder->id,
            'masjid_name' => $archivedOrder->masjid?->name,
            'musholla_name' => method_exists($archivedOrder, 'musholla') ? ($archivedOrder->musholla?->name) : null,
            'status' => $archivedOrder->status,
            'spk_id' => $archivedOrder->spk_id ?? null,
            'invoice_id' => $archivedOrder->invoice?->id ?? null,
        ];

        ServiceOrderHistory::firstOrCreate(
            ['service_order_id' => $serviceOrder->id],
            [
                'archived_at' => now(),
                'summary' => 'Archived to service history',
                'order_snapshot' => $snapshot,
                'archived_by_id' => auth()->id(),
            ]
        );

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
