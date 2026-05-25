<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Receipt;
use App\Models\ServiceOrder;
use App\Models\WorkflowStep;
use App\Support\WorkflowLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * List orders awaiting payment.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:100',
        ]);

        $query = ServiceOrder::whereIn('status', ['waiting_payment', 'payment_verified'])
            ->with(['masjid', 'invoice', 'serviceDetails'])
            ->latest();

        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('masjid', fn ($mq) => $mq->where('name', 'like', "%{$search}%"));
            });
        }

        $orders = $query->paginate(15);

        return view('manager.payment-dashboard', compact('orders'));
    }

    /**
     * Verify cash payment — marks as paid but waits for manager to confirm money received.
     */
    public function verifyCash(ServiceOrder $order, Request $request): JsonResponse
    {
        if ($order->status !== 'waiting_payment') {
            return response()->json(['success' => false, 'message' => 'Order tidak dalam status menunggu pembayaran.'], 422);
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        return DB::connection('ac_service')->transaction(function () use ($order, $validated) {
            $user = auth()->user();
            $invoice = $this->payableInvoice($order);

            if (!$invoice) {
                return response()->json(['success' => false, 'message' => 'Invoice tidak ditemukan.'], 422);
            }

            $this->markInvoicePaid($invoice, 'cash', $validated['notes'] ?? null);

            // Don't complete yet — wait for cash confirmation
            $order->update(['status' => 'payment_verified']);

            WorkflowStep::create([
                'service_order_id' => $order->id,
                'step' => 'payment_verified',
                'actor_id' => $user->id,
                'actor_name' => $user->name,
                'actor_role' => $user->role,
                'notes' => 'Pembayaran tunai dicatat. Menunggu konfirmasi uang diterima.',
            ]);

            return response()->json(['success' => true, 'message' => 'Pembayaran tunai dicatat. Lanjutkan penyelesaian order setelah uang diterima.']);
        });
    }

    /**
     * Confirm cash money received — completes order + generates receipt.
     */
    public function confirmCash(ServiceOrder $order): JsonResponse
    {
        if ($order->status !== 'payment_verified') {
            return response()->json(['success' => false, 'message' => 'Order harus dalam status pembayaran diverifikasi.'], 422);
        }

        $invoice = $order->invoice;
        if (!$invoice || $invoice->payment_method !== 'cash') {
            return response()->json(['success' => false, 'message' => 'Invoice tidak ditemukan atau bukan pembayaran tunai.'], 422);
        }

        return DB::connection('ac_service')->transaction(function () use ($order, $invoice) {
            $user = auth()->user();

            $invoice->update([
                'cash_confirmed_at' => now(),
                'cash_confirmed_by' => $user->id,
                'cash_confirmed_by_name' => $user->name,
            ]);

            Receipt::firstOrCreate(
                ['invoice_id' => $invoice->id],
                [
                    'service_order_id' => $order->id,
                    'receipt_number' => Receipt::generateReceiptNumber(),
                    'payment_method' => 'cash',
                    'payment_amount' => $invoice->total_price,
                    'payment_date' => now()->toDateString(),
                    'verified_by' => $user->id,
                    'verified_by_name' => $user->name,
                    'printed_name' => $user->name,
                    'notes' => 'Uang tunai dikonfirmasi diterima oleh manager.',
                ]
            );

            WorkflowLogger::logPaymentVerified($order, 'tunai (uang diterima)', $invoice->total_price);

            return response()->json(['success' => true, 'message' => 'Uang tunai dikonfirmasi diterima. Selesaikan order dari monitoring saat semua administrasi final sudah siap.']);
        });
    }

    /**
     * Verify bank transfer payment.
     */
    public function verifyTransfer(ServiceOrder $order, Request $request): JsonResponse
    {
        if ($order->status !== 'waiting_payment') {
            return response()->json(['success' => false, 'message' => 'Order tidak dalam status menunggu pembayaran.'], 422);
        }

        $validated = $request->validate([
            'transfer_amount' => 'required|numeric|min:1',
            'transfer_date' => 'required|date|before_or_equal:today',
            'bank_name' => 'required|string|max:100',
            'reference_number' => 'required|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        return DB::connection('ac_service')->transaction(function () use ($order, $validated) {
            $user = auth()->user();
            $invoice = $this->payableInvoice($order);

            if (!$invoice) {
                return response()->json(['success' => false, 'message' => 'Invoice tidak ditemukan.'], 422);
            }

            if ((float) $validated['transfer_amount'] < (float) $invoice->total_price) {
                return response()->json(['success' => false, 'message' => 'Jumlah transfer kurang dari total invoice.'], 422);
            }

            $this->markInvoicePaid($invoice, 'transfer', $validated['notes'] ?? null, [
                'transfer_amount' => $validated['transfer_amount'],
                'transfer_date' => $validated['transfer_date'],
                'bank_name' => $validated['bank_name'],
                'reference_number' => $validated['reference_number'],
            ]);

            $order->update(['status' => 'payment_verified']);

            Receipt::create([
                'service_order_id' => $order->id,
                'invoice_id' => $invoice->id,
                'receipt_number' => Receipt::generateReceiptNumber(),
                'payment_method' => 'transfer',
                'payment_amount' => $validated['transfer_amount'],
                'payment_date' => $validated['transfer_date'],
                'transfer_bank' => $validated['bank_name'],
                'transfer_reference' => $validated['reference_number'],
                'verified_by' => $user->id,
                'verified_by_name' => $user->name,
                'printed_name' => $user->name,
                'notes' => $validated['notes'] ?? null,
            ]);

            WorkflowLogger::logPaymentVerified($order, 'transfer - ' . $validated['bank_name'], $validated['transfer_amount']);

            return response()->json(['success' => true, 'message' => 'Pembayaran transfer berhasil diverifikasi. Lanjutkan penyelesaian order dari monitoring.']);
        });
    }

    /**
     * Verify QRIS payment.
     */
    public function verifyQris(ServiceOrder $order, Request $request): JsonResponse
    {
        if ($order->status !== 'waiting_payment') {
            return response()->json(['success' => false, 'message' => 'Order tidak dalam status menunggu pembayaran.'], 422);
        }

        $validated = $request->validate([
            'qris_reference' => 'required|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        return DB::connection('ac_service')->transaction(function () use ($order, $validated) {
            $user = auth()->user();
            $invoice = $this->payableInvoice($order);

            if (!$invoice) {
                return response()->json(['success' => false, 'message' => 'Invoice tidak ditemukan.'], 422);
            }

            $qrisRef = $validated['qris_reference'];
            $this->markInvoicePaid($invoice, 'qris', $validated['notes'] ?? 'Pembayaran QRIS diverifikasi manual.', [
                'qris_reference' => $qrisRef,
            ]);

            $order->update(['status' => 'payment_verified']);

            Receipt::create([
                'service_order_id' => $order->id,
                'invoice_id' => $invoice->id,
                'receipt_number' => Receipt::generateReceiptNumber(),
                'payment_method' => 'qris',
                'payment_amount' => $invoice->total_price,
                'payment_date' => now()->toDateString(),
                'qris_reference' => $qrisRef,
                'verified_by' => $user->id,
                'verified_by_name' => $user->name,
                'printed_name' => $user->name,
            ]);

            WorkflowLogger::logPaymentVerified($order, 'QRIS', $invoice->total_price);

            return response()->json(['success' => true, 'message' => 'Pembayaran QRIS berhasil diverifikasi. Lanjutkan penyelesaian order dari monitoring.']);
        });
    }

    private function payableInvoice(ServiceOrder $order): ?Invoice
    {
        return $order->invoice()
            ->whereNull('payment_verified_at')
            ->first();
    }

    private function markInvoicePaid(Invoice $invoice, string $method, ?string $notes, ?array $metadata = null): void
    {
        $user = auth()->user();

        $invoice->update([
            'payment_method' => $method,
            'payment_verified_at' => now(),
            'payment_verified_by' => $user->id,
            'payment_verified_by_name' => $user->name,
            'payment_notes' => $notes,
            'payment_metadata' => $metadata,
        ]);
    }
}
