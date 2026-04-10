<?php

namespace App\Http\Controllers;

use App\Models\ServiceOrder;
use App\Models\TechnicianAssignment;
use Illuminate\Http\JsonResponse;

class TechnicianController extends Controller
{
    public function dashboard()
    {
        return view('technician.dashboard', $this->buildDashboardViewData());
    }

    public function snapshot(): JsonResponse
    {
        return response()->json([
            'html' => view('technician.dashboard', $this->buildDashboardViewData())->render(),
        ]);
    }

    public function spkView(ServiceOrder $serviceOrder)
    {
        $assignment = $serviceOrder->technicianAssignment;

        if (! $assignment || $assignment->technician_id !== auth()->id()) {
            abort(403, 'Anda tidak ditugaskan ke order ini.');
        }

        $serviceOrder->load('masjid', 'serviceDetails', 'workflowSteps');

        return view('technician.spk_view', compact('serviceOrder', 'assignment'));
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
