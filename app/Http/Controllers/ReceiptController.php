<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use App\Models\ServiceOrder;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    /**
     * List all receipts.
     */
    public function index(Request $request)
    {
        $query = Receipt::with(['serviceOrder.masjid', 'invoice'])
            ->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('receipt_number', 'like', "%{$search}%")
                  ->orWhereHas('serviceOrder', fn ($sq) => $sq->where('order_number', 'like', "%{$search}%"))
                  ->orWhereHas('serviceOrder.masjid', fn ($mq) => $mq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('method')) {
            $query->where('payment_method', $request->method);
        }

        $receipts = $query->paginate(15);

        return view('manager.receipts', compact('receipts'));
    }

    /**
     * Show receipt details.
     */
    public function show(Receipt $receipt)
    {
        $receipt->load(['serviceOrder.masjid', 'serviceOrder.serviceDetails', 'invoice']);

        return view('receipt.template', compact('receipt'));
    }

    /**
     * Print receipt (same as show but for print).
     */
    public function print(Receipt $receipt)
    {
        $receipt->load(['serviceOrder.masjid', 'serviceOrder.serviceDetails', 'invoice']);

        return view('receipt.template', compact('receipt'));
    }
}
