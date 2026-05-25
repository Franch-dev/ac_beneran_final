<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceEdit;
use App\Models\ServiceDetail;
use App\Models\ServiceOrder;
use App\Support\WorkflowLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function print(ServiceOrder $serviceOrder)
    {
        $serviceOrder->load('masjid.acUnits', 'serviceDetails', 'invoice');
        if (!$serviceOrder->invoice) {
            abort(404, 'Invoice tidak ditemukan');
        }
        return view('invoice', compact('serviceOrder'));
    }

    public function spk(ServiceOrder $serviceOrder)
    {
        $serviceOrder->load('masjid', 'serviceDetails');
        return view('spk', compact('serviceOrder'));
    }

    /**
     * Show invoice editor for frontdesk.
     */
    public function showEditor(Invoice $invoice)
    {
        $serviceOrder = $invoice->serviceOrder;

        // Security: cannot edit after payment
        if (in_array($serviceOrder->status, ['payment_verified', 'completed'])) {
            abort(403, 'Invoice tidak dapat diubah setelah pembayaran.');
        }

        $serviceOrder->load('masjid', 'serviceDetails', 'technicianAssignment');
        $auditLogs = InvoiceEdit::where('invoice_id', $invoice->id)->latest()->get();

        return view('frontdesk.invoice-editor', compact('invoice', 'serviceOrder', 'auditLogs'));
    }

    /**
     * Save invoice edits with audit trail.
     */
    public function editInvoice(Request $request, Invoice $invoice): JsonResponse
    {
        $serviceOrder = $invoice->serviceOrder;

        // Security: cannot edit after payment
        if (in_array($serviceOrder->status, ['payment_verified', 'completed'])) {
            return response()->json(['success' => false, 'message' => 'Invoice tidak dapat diubah setelah pembayaran.'], 403);
        }

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:500',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        return DB::connection('ac_service')->transaction(function () use ($request, $invoice, $serviceOrder) {
            $user = auth()->user();
            $newItems = $request->items;
            $existingDetails = $serviceOrder->serviceDetails()->get();

            // Track changes for audit log
            $changes = [];

            // Detect removed items: IDs in DB but not in submitted list
            $submittedIds = collect($newItems)->filter(fn ($i) => !empty($i['id']))->pluck('id')->toArray();
            $removedDetails = $existingDetails->reject(fn ($d) => in_array($d->id, $submittedIds));

            foreach ($removedDetails as $removed) {
                $changes[] = [
                    'type' => 'remove_item',
                    'old' => ['description' => $removed->description, 'quantity' => $removed->quantity, 'price' => $removed->price],
                    'new' => null,
                ];
                $removed->delete();
            }

            // Update existing items
            foreach ($newItems as $item) {
                if (!empty($item['id'])) {
                    $detail = $existingDetails->find($item['id']);
                    if ($detail) {
                        // Check for price change
                        if ($detail->price != $item['price']) {
                            $changes[] = [
                                'type' => 'update_price',
                                'old' => ['price' => $detail->price],
                                'new' => ['price' => $item['price']],
                            ];
                        }
                        // Check for quantity change
                        if ($detail->quantity != $item['quantity']) {
                            $changes[] = [
                                'type' => 'update_quantity',
                                'old' => ['quantity' => $detail->quantity],
                                'new' => ['quantity' => $item['quantity']],
                            ];
                        }
                        $detail->update([
                            'description' => $item['description'],
                            'quantity' => $item['quantity'],
                            'price' => $item['price'],
                        ]);
                    }
                } else {
                    // New item
                    ServiceDetail::create([
                        'service_order_id' => $serviceOrder->id,
                        'description' => $item['description'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                    ]);
                    $changes[] = [
                        'type' => 'add_item',
                        'old' => null,
                        'new' => ['description' => $item['description'], 'quantity' => $item['quantity'], 'price' => $item['price']],
                    ];
                }
            }

            // Log each change
            foreach ($changes as $change) {
                InvoiceEdit::create([
                    'invoice_id' => $invoice->id,
                    'service_order_id' => $serviceOrder->id,
                    'edited_by' => $user->id,
                    'edited_by_name' => $user->name,
                    'edited_by_role' => $user->role,
                    'edit_type' => $change['type'],
                    'old_value' => $change['old'],
                    'new_value' => $change['new'],
                    'created_at' => now(),
                ]);
            }

            // Recalculate total
            $total = $serviceOrder->fresh()->serviceDetails->sum(fn ($d) => $d->quantity * $d->price);
            $invoice->update(['total_price' => $total]);

            // Update order status to pending_fee_approval if technician reported fees
            if ($serviceOrder->technicianAssignment && $serviceOrder->technicianAssignment->fee_reported) {
                $serviceOrder->update(['status' => 'pending_fee_approval']);
            }

            // Log workflow step
            WorkflowLogger::logInvoiceEdited($serviceOrder, count($changes) . ' perubahan', []);

            return response()->json([
                'success' => true,
                'message' => 'Invoice berhasil disimpan.',
                'total' => $total,
            ]);
        });
    }
}
