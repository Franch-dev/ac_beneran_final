<?php

namespace Modules\AcMasjidMusholla\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AcUnit;
use App\Models\Masjid;
use App\Models\ServiceOrder;
use App\Support\ApiResponse;
use App\Support\MonitoringStatusSummary;
use App\Support\SqlDateExpressions;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Throwable;

class AcMasjidMushollaMonitoringController extends Controller
{
    public function __invoke(Request $request)
    {
        session(['show_role_buttons' => true, 'current_module' => 'ac-masjid-musholla']);

        $data = $this->buildMonitoringViewData($request);

        return view('ac-masjid-musholla::monitoring', $data);
    }

    public function snapshot(Request $request)
    {
        $data = $this->buildMonitoringViewData($request);

        return ApiResponse::raw($data);
    }

    protected function buildMonitoringViewData(Request $request): array
    {
        try {
            $query = ServiceOrder::query()
                ->whereNull('archived_at')
                ->select([
                    'id',
                    'masjid_id',
                    'order_number',
                    'service_date',
                    'status',
                    'created_at',
                    'field_report_notes',
                    'field_report_additional_fee',
                    'manager_approved_additional_fee',
                    'frontdesk_confirmed_complete',
                    'manager_confirmed_complete',
                ])
                ->with([
                    'masjid:id,custom_id,name',
                    'masjid.acUnits:id,masjid_id,quantity,last_service_date',
                    'serviceDetails:id,service_order_id,pk_type,brand,quantity',
                    'invoice:id,service_order_id,invoice_number,total_price,payment_method,payment_verified_at,cash_confirmed_at',
                    'receipt:id,service_order_id,invoice_id,receipt_number,payment_method,payment_amount,payment_date',
                    'latestWorkflowStep',
                    'technicianAssignment:id,service_order_id,technician_id,technician_name,status,started_at,completed_at,technician_notes',
                ])
                ->withCount('photoProofs');

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($innerQuery) use ($search): void {
                    $innerQuery->whereHas('masjid', function ($builder) use ($search): void {
                        $builder->where('name', 'like', "%{$search}%")
                            ->orWhere('custom_id', 'like', "%{$search}%");
                    })->orWhere('order_number', 'like', "%{$search}%");
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }

            $orders = $query->latest()->paginate(30)->withQueryString();
            $statusTotals = Cache::remember('monitoring:status_totals:mm', now()->addSeconds(20), function () {
                return MonitoringStatusSummary::activeTotals();
            });

            $totalLokasi = Masjid::count();
            $totalUnit = (int) AcUnit::sum('quantity');
            $unitAges = AcUnit::query()
                ->selectRaw('masjid_id, MAX(last_service_date) as max_last_service_date')
                ->groupBy('masjid_id');

            $overdue = Masjid::query()
                ->joinSub($unitAges, 'unit_ages', function ($join): void {
                    $join->on('masjids.id', '=', 'unit_ages.masjid_id');
                })
                ->where(function ($query): void {
                    $query->whereNull('unit_ages.max_last_service_date')
                        ->orWhereRaw(SqlDateExpressions::daysSince('unit_ages.max_last_service_date', 'ac_service').' >= 120');
                })
                ->count();

            $masjids = Masjid::query()
                ->select(['id', 'custom_id', 'name', 'type', 'address', 'dkm_name', 'marbot_name', 'phone_numbers', 'setup_status', 'setup_completed_at', 'created_at'])
                ->with(['acUnits:id,masjid_id,quantity,last_service_date,pk_type,brand'])
                ->latest()
                ->paginate(30)
                ->withQueryString();

            return compact('orders', 'totalLokasi', 'totalUnit', 'overdue', 'masjids', 'statusTotals');
        } catch (Throwable $e) {
            report($e);

            return [
                'orders' => new LengthAwarePaginator([], 0, 30),
                'totalLokasi' => 0,
                'totalUnit' => 0,
                'overdue' => 0,
                'masjids' => new LengthAwarePaginator([], 0, 30),
                'statusTotals' => collect(),
            ];
        }
    }
}
