<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowStep extends Model
{
    protected $connection = 'ac_service';

    protected $fillable = [
        'service_order_id',
        'step',
        'actor_id',
        'actor_name',
        'actor_role',
        'notes',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function serviceOrder()
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    public static function stepLabel(string $step): string
    {
        return match($step) {
            'guest_created' => 'Order Tamu Dibuat',
            'frontdesk_created' => 'Order Frontdesk Dibuat',
            'spk_invoice_created' => 'SPK & Invoice Dibuat',
            'spk_invoice_approved' => 'SPK & Invoice Disetujui',
            'edited_invoice_created' => 'Invoice Edit Dibuat',
            'edited_invoice_approved' => 'Invoice Edit Disetujui',
            'invoice_generated' => 'Invoice Diterbitkan',
            'assigned'  => 'Teknisi Ditugaskan',
            'in_progress' => 'Sedang Dikerjakan',
            'technician_reported' => 'Laporan Teknisi (Biaya Tambahan)',
            'invoice_edited' => 'Invoice Diedit (Biaya Tambahan)',
            'payment_received' => 'Pembayaran Diterima',
            'printed' => 'Invoice & Receipt Dicetak',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            default     => ucfirst($step),
        };
    }

    public static function stepIcon(string $step): string
    {
        return match($step) {
            'guest_created' => 'fas fa-user-plus',
            'frontdesk_created' => 'fas fa-user-tie',
            'spk_invoice_created' => 'fas fa-file-invoice',
            'spk_invoice_approved' => 'fas fa-file-invoice-dollar',
            'edited_invoice_created' => 'fas fa-file-import',
            'edited_invoice_approved' => 'fas fa-file-circle-check',
            'assigned'    => 'fas fa-user-hard-hat',
            'in_progress' => 'fas fa-tools',
            'technician_reported' => 'fas fa-file-alt',
            'invoice_edited' => 'fas fa-edit',
            'payment_received' => 'fas fa-credit-card',
            'printed' => 'fas fa-print',
            'completed'   => 'fas fa-check-double',
            'cancelled'   => 'fas fa-times-circle',
            default       => 'fas fa-circle',
        };
    }

    public static function stepColor(string $step): string
    {
        return match($step) {
            'guest_created' => '#1a73e8',
            'frontdesk_created' => '#1976d2',
            'spk_invoice_created' => '#1565c0',
            'spk_invoice_approved' => '#1e8e3e',
            'edited_invoice_created' => '#f57c00',
            'edited_invoice_approved' => '#388e3c',
            'assigned'    => '#9c27b0',
            'in_progress' => '#f9ab00',
            'technician_reported' => '#ff6f00',
            'invoice_edited' => '#f57c00',
            'payment_received' => '#388e3c',
            'printed' => '#2e7d32',
            'completed'   => '#137333',
            'cancelled'   => '#c5221f',
            default       => '#5f6368',
        };
    }
}
