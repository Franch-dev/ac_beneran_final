<?php

namespace App\Support;

use App\Models\ServiceOrder;
use Illuminate\Support\Collection;

class MonitoringStatusSummary
{
    public const PENDING_BUCKET = [
        'pending_review',
        'approved',
        'spk_invoice_created',
        'spk_invoice_approved',
    ];

    public const INVOICE_BUCKET = [
        'approved',
        'invoice_editing',
        'fee_review',
    ];

    public static function activeTotals(): Collection
    {
        return ServiceOrder::query()
            ->whereNull('archived_at')
            ->select('status')
            ->selectRaw('count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
    }

    public static function withAliases(Collection $totals): Collection
    {
        $pending = self::sum($totals, self::PENDING_BUCKET);
        $invoice = self::sum($totals, self::INVOICE_BUCKET);
        $waitingReview = (int) ($totals['waiting_review'] ?? 0);
        $waitingPayment = (int) ($totals['waiting_payment'] ?? 0);

        return $totals->merge([
            'pending' => $pending,
            'waiting_invoice' => $invoice,
            'invoice_queue' => $invoice,
            'waiting_review' => $waitingReview,
            'review_queue' => $waitingReview,
            'waiting_payment' => $waitingPayment,
            'payment_queue' => $waitingPayment,
        ]);
    }

    private static function sum(Collection $totals, array $statuses): int
    {
        return collect($statuses)->sum(fn (string $status): int => (int) ($totals[$status] ?? 0));
    }
}
