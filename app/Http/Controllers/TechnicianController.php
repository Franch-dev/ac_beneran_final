<?php

namespace App\Http\Controllers;

use App\Models\PhotoProof;
use App\Models\ServiceOrder;
use App\Models\TechnicianAssignment;
use App\Support\ApiResponse;
use App\Support\ServiceOrderWorkflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class TechnicianController extends Controller
{
    public function dashboard()
    {
        return view('technician.dashboard', $this->buildDashboardViewData());
    }

    public function snapshot(): JsonResponse
    {
        return ApiResponse::snapshot(view('technician.dashboard', $this->buildDashboardViewData())->render());
    }

    public function spkView(ServiceOrder $serviceOrder)
    {
        $assignment = $serviceOrder->technicianAssignment;

        if (! $assignment || $assignment->technician_id !== auth()->id()) {
            abort(403, 'Anda tidak ditugaskan ke order ini.');
        }

        if (! in_array($serviceOrder->status, [
            'technician_assigned', 'in_progress', 'waiting_review', 'invoice_editing',
            'fee_review', 'waiting_payment', 'payment_verified', 'completed', 'closed',
        ], true)) {
            abort(403, 'Dokumen belum tersedia.');
        }

        $serviceOrder->load('masjid', 'serviceDetails', 'workflowSteps', 'invoice');

        return view('technician.spk_view', compact('serviceOrder', 'assignment'));
    }

    public function invoiceView(ServiceOrder $serviceOrder)
    {
        $assignment = $serviceOrder->technicianAssignment;

        if (! $assignment || $assignment->technician_id !== auth()->id()) {
            abort(403, 'Anda tidak ditugaskan ke order ini.');
        }

        if (! in_array($serviceOrder->status, [
            'technician_assigned', 'in_progress', 'waiting_review', 'invoice_editing',
            'fee_review', 'waiting_payment', 'payment_verified', 'completed', 'closed',
        ], true)) {
            abort(403, 'Dokumen belum tersedia.');
        }

        $serviceOrder->load('masjid.acUnits', 'serviceDetails', 'invoice');

        if (! $serviceOrder->invoice) {
            abort(404, 'Invoice belum tersedia untuk order ini.');
        }

        return view('technician.invoice_view', compact('serviceOrder', 'assignment'));
    }

    /**
     * Show job completion form with photo upload and optional fee reporting.
     */
    public function jobCompletionForm(ServiceOrder $serviceOrder)
    {
        $assignment = $serviceOrder->technicianAssignment;

        if (!$assignment || $assignment->technician_id !== auth()->id()) {
            abort(403, 'Anda tidak ditugaskan ke order ini.');
        }

        if ($serviceOrder->status !== 'in_progress') {
            abort(422, 'Order tidak dalam status yang bisa diselesaikan.');
        }

        $serviceOrder->load('masjid', 'serviceDetails', 'invoice');

        return view('technician.job-completion', compact('serviceOrder', 'assignment'));
    }

    /**
     * Submit job completion with photo proof and optional fee report.
     */
    public function completeJob(Request $request, ServiceOrder $serviceOrder): JsonResponse
    {
        $assignment = $serviceOrder->technicianAssignment;

        // Security: only assigned technician can complete
        if (!$assignment || $assignment->technician_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Anda tidak ditugaskan ke order ini.'], 403);
        }

        // Security: cannot complete twice
        if ($assignment->completed_at) {
            return response()->json(['success' => false, 'message' => 'Order sudah diselesaikan sebelumnya.'], 422);
        }

        // Validate status
        if ($serviceOrder->status !== 'in_progress') {
            return response()->json(['success' => false, 'message' => 'Order tidak dalam status yang bisa diselesaikan.'], 422);
        }

        // Validate request
        $validated = $request->validate([
            'photos' => 'required|array|min:1|max:10',
            'photos.*' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120', // 5MB max
            'completion_notes' => 'nullable|string|max:1000',
            'has_fees' => 'boolean',
            'fee_description' => 'required_if:has_fees,true|nullable|string|max:500',
            'fee_amount' => 'required_if:has_fees,true|nullable|numeric|min:0',
            'fee_tools_materials' => 'nullable|string|max:500',
        ]);

        $hasFees = $request->boolean('has_fees');
        $storedPaths = [];

        try {
            return DB::connection('ac_service')->transaction(function () use ($validated, $serviceOrder, $assignment, $hasFees, &$storedPaths) {
                // Store photos
                $photoRecords = [];
                $orderId = $serviceOrder->id;

                foreach ($validated['photos'] as $photo) {
                    $path = $photo->store("proofs/{$orderId}", 'local');
                    $storedPaths[] = $path;
                    $photoRecords[] = PhotoProof::create([
                        'service_order_id' => $orderId,
                        'technician_assignment_id' => $assignment->id,
                        'file_path' => $path,
                        'file_name' => basename($path),
                        'file_size' => $photo->getSize(),
                        'mime_type' => $photo->getMimeType(),
                        'taken_at' => now(),
                        'created_by' => auth()->id(),
                    ]);
                }

                $toolsMaterialsPayload = null;
                if ($hasFees && ! empty($validated['fee_tools_materials'])) {
                    $toolsMaterialsPayload = [[
                        'name' => 'Alat/Bahan tambahan',
                        'quantity' => 1,
                        'price' => (float) ($validated['fee_amount'] ?? 0),
                        'notes' => $validated['fee_tools_materials'],
                    ]];
                }

                app(ServiceOrderWorkflow::class)->submitTechnicianReport($serviceOrder, [
                    'notes' => $validated['completion_notes'] ?? null,
                    'additional_fee' => $hasFees ? (float) $validated['fee_amount'] : 0,
                    'tools_materials' => $toolsMaterialsPayload,
                    'fee_description' => $hasFees ? $validated['fee_description'] : null,
                    'fee_tools_materials' => $hasFees ? ($validated['fee_tools_materials'] ?? null) : null,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => $hasFees
                        ? 'Pekerjaan selesai. Biaya tambahan dilaporkan untuk persetujuan manager.'
                        : 'Pekerjaan selesai. Menunggu review akhir manager.',
                    'status' => $serviceOrder->fresh()->status,
                ]);
            });
        } catch (Throwable $exception) {
            foreach ($storedPaths as $path) {
                Storage::disk('local')->delete($path);
            }

            throw $exception;
        }
    }

    protected function buildDashboardViewData(): array
    {
        $techId = auth()->id();

        $assignments = TechnicianAssignment::where('technician_id', $techId)
            ->with('serviceOrder.masjid', 'serviceOrder.serviceDetails', 'serviceOrder.workflowSteps')
            ->latest()
            ->get();

        $active = $assignments->whereIn('status', ['assigned', 'in_progress']);
        $completed = $assignments->where('status', 'done')->take(10);

        return compact('active', 'completed');
    }
}
