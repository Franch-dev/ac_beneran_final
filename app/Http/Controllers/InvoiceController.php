<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceEdit;
use App\Models\ServiceDetail;
use App\Models\ServiceOrder;
use App\Support\ServiceOrderWorkflow;
use App\Support\WorkflowLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function print(ServiceOrder $serviceOrder)
    {
        $visibleStatuses = [
            'spk_invoice_approved', 'technician_assigned', 'in_progress',
            'waiting_review', 'invoice_editing', 'fee_review',
            'waiting_payment', 'payment_verified', 'completed', 'closed',
        ];
        if (!in_array($serviceOrder->status, $visibleStatuses, true)) {
            abort(403, 'Dokumen belum disetujui manager.');
        }

        $serviceOrder->load('masjid.acUnits', 'serviceDetails', 'invoice');
        if (!$serviceOrder->invoice) {
            abort(404, 'Invoice tidak ditemukan');
        }
        return view('invoice', compact('serviceOrder'));
    }

    public function spk(ServiceOrder $serviceOrder)
    {
        $visibleStatuses = [
            'spk_invoice_approved', 'technician_assigned', 'in_progress',
            'waiting_review', 'invoice_editing', 'fee_review',
            'waiting_payment', 'payment_verified', 'completed', 'closed',
        ];
        if (!in_array($serviceOrder->status, $visibleStatuses, true)) {
            abort(403, 'Dokumen belum disetujui manager.');
        }

        $serviceOrder->load('masjid', 'serviceDetails');
        return view('spk', compact('serviceOrder'));
    }

    /**
     * Show invoice editor for frontdesk.
     */
    public function showEditor(Invoice $invoice)
    {
        $serviceOrder = $invoice->serviceOrder;

        if ($serviceOrder->status !== 'invoice_editing') {
            abort(403, 'Invoice hanya dapat diubah saat status invoice_editing.');
        }

        $serviceOrder->load('masjid', 'serviceDetails', 'technicianAssignment');
        $invoice->setRelation('serviceDetails', $serviceOrder->serviceDetails);
        $auditLogs = InvoiceEdit::where('invoice_id', $invoice->id)->latest()->get();

        return view('frontdesk.invoice-editor', compact('invoice', 'serviceOrder', 'auditLogs'));
    }

    /**
     * Save invoice edits with audit trail.
     */
    public function editInvoice(Request $request, Invoice $invoice): JsonResponse
    {
        $serviceOrder = $invoice->serviceOrder;

        if ($serviceOrder->status !== 'invoice_editing') {
            return response()->json(['success' => false, 'message' => 'Invoice hanya dapat diubah saat status invoice_editing.'], 403);
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
                $removedDescription = trim("{$removed->pk_type} {$removed->brand}");
                $changes[] = [
                    'type' => 'remove_item',
                    'old' => ['description' => $removedDescription, 'quantity' => $removed->quantity, 'price' => $removed->price_per_unit],
                    'new' => null,
                ];
                $removed->delete();
            }

            // Update existing items
            foreach ($newItems as $item) {
                if (!empty($item['id'])) {
                    $detail = $existingDetails->find($item['id']);
                    if ($detail) {
                        $normalizedDescription = mb_substr(trim((string) ($item['description'] ?? '')), 0, 100);
                        if ($normalizedDescription === '') {
                            $normalizedDescription = $detail->brand;
                        }

                        // Check for price change
                        if ((float) $detail->price_per_unit !== (float) $item['price']) {
                            $changes[] = [
                                'type' => 'update_price',
                                'old' => ['price' => $detail->price_per_unit],
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
                            'brand' => $normalizedDescription,
                            'quantity' => $item['quantity'],
                            'price_per_unit' => $item['price'],
                        ]);
                    }
                } else {
                    $normalizedDescription = mb_substr(trim((string) ($item['description'] ?? '')), 0, 100);
                    if ($normalizedDescription === '') {
                        $normalizedDescription = 'Biaya layanan';
                    }

                    // New item
                    ServiceDetail::create([
                        'service_order_id' => $serviceOrder->id,
                        'pk_type' => '1PK',
                        'brand' => $normalizedDescription,
                        'quantity' => $item['quantity'],
                        'price_per_unit' => $item['price'],
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
            $total = $serviceOrder->fresh()->serviceDetails->sum(fn ($d) => $d->quantity * $d->price_per_unit);
            $invoice->update(['total_price' => $total]);

            // Log workflow step
            WorkflowLogger::logInvoiceEdited($serviceOrder, count($changes) . ' perubahan', []);
            app(ServiceOrderWorkflow::class)->submitEditedInvoiceForReview($serviceOrder);

            return response()->json([
                'success' => true,
                'message' => 'Invoice berhasil disimpan dan diajukan untuk review manager.',
                'total' => $total,
            ]);
        });
    }
}
