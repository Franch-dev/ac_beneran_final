<?php

namespace App\Http\Controllers;

use App\Models\SyncEvent;
use App\Models\WorkflowStep;
use Illuminate\Http\Request;

class AdminLogController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));

        $syncEventsQuery = SyncEvent::query()->latest('id');
        $workflowStepsQuery = WorkflowStep::query()
            ->with(['serviceOrder.masjid'])
            ->latest('id');

        if ($search !== '') {
            $syncEventsQuery->where(function ($query) use ($search): void {
                $query->where('type', 'like', "%{$search}%")
                    ->orWhere('resource', 'like', "%{$search}%")
                    ->orWhere('actor_name', 'like', "%{$search}%")
                    ->orWhere('actor_role', 'like', "%{$search}%");
            });

            $workflowStepsQuery->where(function ($query) use ($search): void {
                $query->where('step', 'like', "%{$search}%")
                    ->orWhere('actor_name', 'like', "%{$search}%")
                    ->orWhere('actor_role', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('serviceOrder', function ($orderQuery) use ($search): void {
                        $orderQuery->where('order_number', 'like', "%{$search}%");
                    });
            });
        }

        $syncEvents = $syncEventsQuery
            ->paginate(25, ['*'], 'sync_page')
            ->withQueryString();

        $workflowSteps = $workflowStepsQuery
            ->paginate(25, ['*'], 'workflow_page')
            ->withQueryString();

        $todaySyncCount = SyncEvent::query()
            ->whereDate('created_at', now()->toDateString())
            ->count();

        $todayWorkflowCount = WorkflowStep::query()
            ->whereDate('created_at', now()->toDateString())
            ->count();

        return view('admin.logs', compact(
            'syncEvents',
            'workflowSteps',
            'todaySyncCount',
            'todayWorkflowCount',
            'search'
        ));
    }
}
