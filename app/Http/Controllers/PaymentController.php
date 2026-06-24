<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use App\Models\ServiceOrder;
use App\Models\WorkflowStep;
use App\Support\RealtimeSync;
use App\Support\QrisPaymentPayload;
use App\Support\ServiceOrderWorkflow;
use App\Support\TransferPaymentInstructions;
use App\Support\WorkflowLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PaymentController extends Controller
{
    private const INTERNAL_ACCESS_TTL_MINUTES = 5;

    private const INTERNAL_SESSION_TTL_MINUTES = 15;

    /**
     * List orders awaiting payment.
     */
    public function index(Request $request): View
    {
        abort(Response::HTTP_FORBIDDEN, 'Halaman pembayaran hanya dapat diakses dari chip monitoring.');
    }

    public function accessLink(Request $request, ServiceOrder $order): JsonResponse
    {
        $user = auth()->user();
        $order->loadMissing(['invoice', 'technicianAssignment']);

        if (! $order->canAccessInternalPayment($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Order belum memenuhi syarat akses pembayaran internal.',
            ], 403);
        }

        $nonce = Str::random(40);
        $expiresAt = now()->addMinutes(self::INTERNAL_ACCESS_TTL_MINUTES);

        Cache::put($this->entryCacheKey($nonce), [
            'order_id' => $order->id,
            'user_id' => $user->id,
            'role' => $user->role,
            'origin_url' => $this->sanitizeInternalOriginUrl($request->headers->get('referer')),
        ], $expiresAt);

        return response()->json([
            'success' => true,
            'url' => URL::temporarySignedRoute(
                'payments.internal.entry',
                $expiresAt,
                ['order' => $order->id, 'nonce' => $nonce]
            ),
        ]);
    }

    public function entry(Request $request, ServiceOrder $order): RedirectResponse
    {
        $user = auth()->user();
        $order->loadMissing(['invoice', 'technicianAssignment']);

        if (! $order->canAccessInternalPayment($user)) {
            abort(403, 'Akses pembayaran internal ditolak.');
        }

        $payload = Cache::pull($this->entryCacheKey((string) $request->query('nonce')));

        if (! is_array($payload)
            || (int) ($payload['order_id'] ?? 0) !== (int) $order->id
            || (int) ($payload['user_id'] ?? 0) !== (int) $user->id
            || ($payload['role'] ?? null) !== $user->role) {
            abort(403, 'Tautan akses pembayaran sudah tidak berlaku.');
        }

        session()->put($this->sessionAccessKey($order->id), [
            'user_id' => $user->id,
            'role' => $user->role,
            'origin_url' => $payload['origin_url'] ?? route('monitoring'),
            'show_available' => true,
            'actions_expires_at' => now()->addMinutes(self::INTERNAL_SESSION_TTL_MINUTES)->timestamp,
        ]);

        return redirect()->route('payments.internal.show', $order);
    }

    public function show(ServiceOrder $order): View
    {
        $user = auth()->user();
        $order->loadMissing([
            'masjid:id,name,address,custom_id',
            'serviceDetails:id,service_order_id,pk_type,brand,quantity,price_per_unit',
            'invoice:id,service_order_id,invoice_number,total_price,payment_verified_at,payment_method,payment_verified_by_name,payment_notes,payment_metadata,cash_confirmed_at',
            'receipt:id,service_order_id,invoice_id,receipt_number,payment_method,payment_amount,payment_date,qris_reference,transfer_bank,transfer_reference',
            'technicianAssignment:id,service_order_id,technician_id,technician_name',
        ]);

        $access = $this->consumeInternalShowAccess($order, $user?->id);

        if (! $access || ! $order->canAccessInternalPayment($user)) {
            abort(403, 'Halaman pembayaran internal hanya bisa dibuka dari monitoring.');
        }

        return view('payments.internal', [
            'order' => $order,
            'canManagePayment' => $order->canManageInternalPayment($user),
            'canRecordCashPayment' => $order->canRecordCashInternalPayment($user),
            'paymentAccessTtlMinutes' => self::INTERNAL_SESSION_TTL_MINUTES,
            'backToMonitoringUrl' => $access['origin_url'] ?? route('monitoring'),
            'qrisPayment' => QrisPaymentPayload::forOrder($order),
            'transferPayment' => TransferPaymentInstructions::forOrder($order),
        ]);
    }

    /**
     * Verify cash payment and wait for cash confirmation.
     */
    public function verifyCash(ServiceOrder $order, Request $request): JsonResponse
    {
        $this->ensureCashPaymentActionAccess($order);

        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        if ($order->status !== 'waiting_payment') {
            return response()->json(['success' => false, 'message' => 'Order tidak dalam status menunggu pembayaran.'], 422);
        }

        if (! $order->invoice) {
            return response()->json(['success' => false, 'message' => 'Invoice tidak ditemukan.'], 422);
        }

        DB::connection('ac_service')->transaction(function () use ($order, $validated) {
            $user = auth()->user();

            $order->invoice->update([
                'payment_method' => 'cash',
                'payment_verified_at' => now(),
                'payment_verified_by' => $user->id,
                'payment_verified_by_name' => $user->name,
                'payment_notes' => $validated['notes'] ?? null,
                'payment_metadata' => null,
            ]);

            $order->update(['status' => 'payment_verified']);
            WorkflowLogger::logPaymentVerified($order, 'tunai', $order->invoice->total_price);
            $this->broadcastPaymentStatus($order, 'payment_verified');
        });

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran tunai dicatat. Lanjutkan penyelesaian order setelah uang diterima.',
        ]);
    }

    /**
     * Confirm cash money received and generate receipt.
     */
    public function confirmCash(ServiceOrder $order): JsonResponse
    {
        $this->ensureInternalPaymentActionAccess($order);

        if ($order->status !== 'payment_verified') {
            return response()->json(['success' => false, 'message' => 'Order harus dalam status pembayaran diverifikasi.'], 422);
        }

        $invoice = $order->invoice;
        if (! $invoice || $invoice->payment_method !== 'cash') {
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
            $this->broadcastPaymentStatus($order, 'payment_verified');

            return response()->json([
                'success' => true,
                'message' => 'Uang tunai dikonfirmasi diterima. Selesaikan order dari monitoring saat semua administrasi final sudah siap.',
            ]);
        });
    }

    /**
     * Verify bank transfer payment.
     */
    public function verifyTransfer(ServiceOrder $order, Request $request): JsonResponse
    {
        $this->ensureInternalPaymentActionAccess($order);

        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        $invoice = $order->invoice;
        if (! $invoice) {
            return response()->json(['success' => false, 'message' => 'Invoice tidak ditemukan.'], 422);
        }

        $transferPayment = TransferPaymentInstructions::forOrder($order);
        if (! $transferPayment['configured']) {
            return response()->json(['success' => false, 'message' => 'Data rekening transfer belum dikonfigurasi di server.'], 422);
        }

        app(ServiceOrderWorkflow::class)->verifyPayment($order, 'transfer', $validated['notes'] ?? null, [
            'transfer_amount' => $transferPayment['amount'],
            'transfer_date' => now()->toDateString(),
            'bank_name' => $transferPayment['bank_name'],
            'account_number' => $transferPayment['account_number'],
            'account_name' => $transferPayment['account_name'],
            'reference_number' => $transferPayment['reference'],
        ]);

        $freshInvoice = $order->fresh('invoice')->invoice;
        Receipt::create([
            'service_order_id' => $order->id,
            'invoice_id' => $freshInvoice->id,
            'receipt_number' => Receipt::generateReceiptNumber(),
            'payment_method' => 'transfer',
            'payment_amount' => $freshInvoice->total_price,
            'payment_date' => now()->toDateString(),
            'transfer_bank' => $transferPayment['bank_name'],
            'transfer_reference' => $transferPayment['reference'],
            'verified_by' => auth()->id(),
            'verified_by_name' => auth()->user()->name,
            'printed_name' => auth()->user()->name,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran transfer berhasil diverifikasi. Lanjutkan penyelesaian order dari monitoring.',
        ]);
    }

    /**
     * Verify QRIS payment.
     */
    public function verifyQris(ServiceOrder $order, Request $request): JsonResponse
    {
        $this->ensureInternalPaymentActionAccess($order);

        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        $invoice = $order->invoice;
        if (! $invoice) {
            return response()->json(['success' => false, 'message' => 'Invoice tidak ditemukan.'], 422);
        }

        $qrisPayment = QrisPaymentPayload::forOrder($order);
        if (! $qrisPayment['configured'] || ! $qrisPayment['payload']) {
            return response()->json(['success' => false, 'message' => 'QRIS dinamis belum dikonfigurasi di server.'], 422);
        }

        app(ServiceOrderWorkflow::class)->verifyPayment($order, 'qris', $validated['notes'] ?? 'Pembayaran QRIS diverifikasi manual.', [
            'qris_reference' => $qrisPayment['reference'],
            'qris_amount' => $qrisPayment['amount'],
            'qris_merchant_name' => $qrisPayment['merchant_name'],
        ]);

        $freshInvoice = $order->fresh('invoice')->invoice;
        Receipt::create([
            'service_order_id' => $order->id,
            'invoice_id' => $freshInvoice->id,
            'receipt_number' => Receipt::generateReceiptNumber(),
            'payment_method' => 'qris',
            'payment_amount' => $freshInvoice->total_price,
            'payment_date' => now()->toDateString(),
            'qris_reference' => $qrisPayment['reference'],
            'verified_by' => auth()->id(),
            'verified_by_name' => auth()->user()->name,
            'printed_name' => auth()->user()->name,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran QRIS berhasil diverifikasi. Lanjutkan penyelesaian order dari monitoring.',
        ]);
    }

    private function consumeInternalShowAccess(ServiceOrder $order, ?int $userId): ?array
    {
        $payload = session($this->sessionAccessKey($order->id));

        if (! is_array($payload) || (int) ($payload['user_id'] ?? 0) !== (int) $userId) {
            return null;
        }

        if (! ($payload['show_available'] ?? false)) {
            return null;
        }

        if ((int) ($payload['actions_expires_at'] ?? 0) < now()->timestamp) {
            session()->forget($this->sessionAccessKey($order->id));

            return null;
        }

        $payload['show_available'] = false;
        session()->put($this->sessionAccessKey($order->id), $payload);

        return $payload;
    }

    private function entryCacheKey(string $nonce): string
    {
        return 'payments:internal-entry:'.$nonce;
    }

    private function sessionAccessKey(int $orderId): string
    {
        return 'payments.internal_access.'.$orderId;
    }

    private function ensureInternalPaymentActionAccess(ServiceOrder $order): void
    {
        $user = auth()->user();
        $payload = session($this->sessionAccessKey($order->id));

        if (! is_array($payload)
            || (int) ($payload['user_id'] ?? 0) !== (int) $user?->id
            || ($payload['role'] ?? null) !== $user?->role
            || (int) ($payload['actions_expires_at'] ?? 0) < now()->timestamp
            || ! $order->canManageInternalPayment($user)) {
            abort(Response::HTTP_FORBIDDEN, 'Aksi pembayaran hanya dapat dilakukan dari sesi monitoring yang aktif.');
        }
    }

    private function ensureCashPaymentActionAccess(ServiceOrder $order): void
    {
        $user = auth()->user();
        $payload = session($this->sessionAccessKey($order->id));
        $order->loadMissing(['invoice', 'technicianAssignment']);

        if (! is_array($payload)
            || (int) ($payload['user_id'] ?? 0) !== (int) $user?->id
            || ($payload['role'] ?? null) !== $user?->role
            || (int) ($payload['actions_expires_at'] ?? 0) < now()->timestamp
            || ! $order->canRecordCashInternalPayment($user)) {
            abort(Response::HTTP_FORBIDDEN, 'Aksi pembayaran tunai hanya dapat dilakukan dari sesi monitoring yang aktif.');
        }
    }

    private function broadcastPaymentStatus(ServiceOrder $order, string $status): void
    {
        RealtimeSync::afterCommit('service_order.payment_verified', [
            'resource' => 'service_order',
            'resource_id' => $order->id,
            'masjid_id' => $order->masjid_id,
            'service_order_id' => $order->id,
            'payload' => ['status' => $status],
        ]);

        $this->flushMonitoringCaches();
    }

    private function flushMonitoringCaches(): void
    {
        Cache::forget('monitoring:status_counts');
        Cache::forget('monitoring:status_totals');
        Cache::forget('monitoring:status_totals:mm');
    }

    private function sanitizeInternalOriginUrl(?string $referer): string
    {
        if (! is_string($referer) || $referer === '') {
            return route('monitoring');
        }

        $appUrl = rtrim((string) config('app.url'), '/');

        if ($appUrl !== '' && str_starts_with($referer, $appUrl.'/')) {
            return $referer;
        }

        return route('monitoring');
    }
}
