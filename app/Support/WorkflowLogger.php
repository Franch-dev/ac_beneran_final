<?php

namespace App\Support;

use App\Models\ServiceOrder;
use App\Models\WorkflowStep;

class WorkflowLogger
{
    /**
     * Log a workflow action with automatic actor capture.
     */
    public static function log(ServiceOrder $order, string $step, string $notes = '', ?int $actorId = null, ?string $actorName = null, ?string $actorRole = null): WorkflowStep
    {
        return WorkflowStep::create([
            'service_order_id' => $order->id,
            'step' => $step,
            'actor_id' => $actorId ?? auth()->id(),
            'actor_name' => $actorName ?? auth()->user()?->name ?? 'System',
            'actor_role' => $actorRole ?? auth()->user()?->role ?? 'system',
            'notes' => $notes,
        ]);
    }

    /**
     * Log order creation.
     */
    public static function logCreated(ServiceOrder $order, string $source = 'frontdesk'): WorkflowStep
    {
        return self::log($order, 'frontdesk_created', "Order dibuat dari {$source}");
    }

    /**
     * Log order approval.
     */
    public static function logApproved(ServiceOrder $order, string $notes = ''): WorkflowStep
    {
        return self::log($order, 'approved', $notes ?: 'Order disetujui');
    }

    /**
     * Log order rejection.
     */
    public static function logRejected(ServiceOrder $order, string $reason): WorkflowStep
    {
        return self::log($order, 'cancelled', "Order ditolak: {$reason}");
    }

    /**
     * Log technician assignment.
     */
    public static function logAssigned(ServiceOrder $order, int $technicianId, string $technicianName): WorkflowStep
    {
        return self::log($order, 'assigned', "Teknisi {$technicianName} (ID: {$technicianId}) ditugaskan");
    }

    /**
     * Log job completion with photo proof.
     */
    public static function logJobCompleted(ServiceOrder $order, int $photoCount): WorkflowStep
    {
        return self::log($order, 'completed', "Pekerjaan selesai dengan {$photoCount} foto bukti");
    }

    /**
     * Log fee report from technician.
     */
    public static function logFeeReported(ServiceOrder $order, float $amount, string $description): WorkflowStep
    {
        return self::log($order, 'technician_reported', "Biaya tambahan dilaporkan: Rp " . number_format($amount, 0, ',', '.') . " - {$description}");
    }

    /**
     * Log invoice edit by frontdesk.
     */
    public static function logInvoiceEdited(ServiceOrder $order, string $editType, array $details): WorkflowStep
    {
        return self::log($order, 'invoice_edited', "Invoice diubah: {$editType} - " . json_encode($details));
    }

    /**
     * Log fee approval by manager.
     */
    public static function logFeeApproved(ServiceOrder $order): WorkflowStep
    {
        return self::log($order, 'fee_review', 'Biaya tambahan disetujui manager');
    }

    /**
     * Log fee rejection by manager.
     */
    public static function logFeeRejected(ServiceOrder $order, string $reason): WorkflowStep
    {
        return self::log($order, 'invoice_editing', "Biaya tambahan ditolak manager: {$reason}");
    }

    public static function logSpkInvoiceRejected(ServiceOrder $order, string $reason): WorkflowStep
    {
        return self::log($order, 'approved', "SPK & Invoice ditolak manager: {$reason}");
    }

    /**
     * Log payment verification.
     */
    public static function logPaymentVerified(ServiceOrder $order, string $method, float $amount): WorkflowStep
    {
        return self::log($order, 'payment_verified', "Pembayaran {$method} sebesar Rp " . number_format($amount, 0, ',', '.') . " diverifikasi");
    }
}
