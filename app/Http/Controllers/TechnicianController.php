<?php

namespace App\Http\Controllers;

use App\Models\ServiceOrder;
use App\Models\TechnicianAssignment;
use Illuminate\Http\Request;

class TechnicianController extends Controller
{
    public function dashboard()
    {
        $techId = auth()->id();

        $assignments = TechnicianAssignment::where('technician_id', $techId)
            ->with('serviceOrder.masjid', 'serviceOrder.serviceDetails', 'serviceOrder.workflowSteps')
            ->latest()
            ->get();

        $active    = $assignments->whereIn('status', ['assigned', 'in_progress']);
        $completed = $assignments->where('status', 'done')->take(10);

        return view('technician.dashboard', compact('active', 'completed'));
    }

    public function spkView(ServiceOrder $serviceOrder)
    {
        // Verify this technician is assigned to this order
        $assignment = $serviceOrder->technicianAssignment;

        if (!$assignment || $assignment->technician_id !== auth()->id()) {
            abort(403, 'Anda tidak ditugaskan ke order ini.');
        }

        $serviceOrder->load('masjid', 'serviceDetails', 'workflowSteps');

        return view('technician.spk_view', compact('serviceOrder', 'assignment'));
    }
}

