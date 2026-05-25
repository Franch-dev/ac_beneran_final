<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Masjid;
use App\Models\ServiceOrder;
use App\Models\ServiceDetail;
use App\Models\AcUnit;
use App\Support\ApiResponse;
use App\Support\SqlDateExpressions;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        [$startDate, $endDate] = $this->resolveDateRange($request);

        // Revenue summary
        $invoices = Invoice::with('serviceOrder.masjid')
            ->whereBetween('created_at', [$startDate, Carbon::parse($endDate)->endOfDay()])
            ->get();

        $totalRevenue  = $invoices->sum('total_price');
        $totalInvoices = $invoices->count();

        // Revenue by month (last 6 months)
        $monthExpression = SqlDateExpressions::monthBucket('created_at', 'ac_service');
        $monthlyRevenue = Invoice::selectRaw("{$monthExpression} as month, SUM(total_price) as total")
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupByRaw($monthExpression)
            ->orderByRaw($monthExpression)
            ->get();

        // Service orders summary
        $ordersInPeriod = ServiceOrder::whereBetween('service_date', [$startDate, $endDate])->get();
        $totalOrders     = $ordersInPeriod->count();
        $completedOrders = $ordersInPeriod->where('status', 'completed')->count();

        // Statuses are aligned to workflow (no legacy `pending` anymore)
        $pendingOrders = $ordersInPeriod
            ->whereIn('status', ['pending_review', 'approved', 'spk_invoice_created'])
            ->count();

        // Overdue masjids
        $overdueMasjids = Masjid::with('acUnits')
            ->get()
            ->filter(fn($m) => $m->urgency_status === 'overdue');

        // Top masjids by service count
        $topMasjids = ServiceOrder::with('masjid')
            ->whereBetween('service_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->get()
            ->groupBy('masjid_id')
            ->map(fn($g) => [
                'name'  => $g->first()->masjid?->name ?? 'Unknown',
                'count' => $g->count(),
                'total' => $g->sum(fn($o) => $o->invoice?->total_price ?? 0),
            ])
            ->sortByDesc('count')
            ->take(10)
            ->values();

        // Revenue by PK type
        $revenueByPK = ServiceDetail::with('serviceOrder')
            ->whereHas('serviceOrder', fn($q) =>
                $q->whereBetween('service_date', [$startDate, $endDate])
                  ->where('status', 'completed')
            )
            ->get()
            ->groupBy('pk_type')
            ->map(fn($g) => [
                'pk_type'  => $g->first()->pk_type,
                'units'    => $g->sum('quantity'),
                'revenue'  => $g->sum(fn($d) => $d->quantity * $d->price_per_unit),
            ])
            ->values();

        return view('reports.index', compact(
            'startDate',
            'endDate',
            'totalRevenue',
            'totalInvoices',
            'monthlyRevenue',
            'totalOrders',
            'completedOrders',
            'pendingOrders',
            'overdueMasjids',
            'topMasjids',
            'revenueByPK'
        ));
    }

    public function exportJson(Request $request)
    {
        [$startDate, $endDate] = $this->resolveDateRange($request);

        $orders = ServiceOrder::with('masjid', 'serviceDetails', 'invoice')
            ->whereBetween('service_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->get()
            ->map(fn($o) => [
                'order_number' => $o->order_number,
                'masjid'       => $o->masjid?->name,
                'service_date' => $o->service_date->format('d M Y'),
                'total'        => $o->invoice?->total_price ?? 0,
                'details'      => $o->serviceDetails->map(fn($d) => [
                    'pk_type'  => $d->pk_type,
                    'brand'    => $d->brand,
                    'quantity' => $d->quantity,
                    'price'    => $d->price_per_unit,
                ]),
            ]);

        return ApiResponse::raw($orders);
    }

    private function resolveDateRange(Request $request): array
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $startDate = $validated['start_date'] ?? now()->startOfMonth()->toDateString();
        $endDate = $validated['end_date'] ?? now()->toDateString();

        return [$startDate, $endDate];
    }
}
