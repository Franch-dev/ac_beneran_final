<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ServiceOrder extends Model
{
    public const ACTIVE_STATUSES = [
        'spk_invoice_created',
        'approved',
        'waiting_payment',
        'payment_verified',
        'in_progress',
        'waiting_review',
        'completed',
    ];

    public const STATUS_LABELS = [
        'spk_invoice_created' => 'Order Dibuat (SPK & Invoice)',
        'approved' => 'Disetujui Manager',
        'waiting_payment' => 'Menunggu Pembayaran',
        'payment_verified' => 'Pembayaran Terverifikasi',
        'in_progress' => 'Sedang Dikerjakan',
        'waiting_review' => 'Menunggu Review Akhir',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan',
    ];

    protected $connection = 'ac_service';

    protected $fillable = [
        'masjid_id', 'order_number', 'meeting_person',
        'phone', 'service_date', 'notes', 'status',
        // Field report fields
        'field_report_notes', 'field_report_additional_fee', 'field_report_tools_materials', 'field_report_submitted_at',
        // Additional fee approval
        'manager_approved_additional_fee', 'additional_fee_approved_by', 'additional_fee_approved_at',
        // Dual confirmation
        'frontdesk_confirmed_complete', 'frontdesk_confirmed_by', 'frontdesk_confirmed_at',
        'manager_confirmed_complete', 'manager_confirmed_by', 'manager_confirmed_at',
    ];

    protected $casts = [
        'service_date' => 'date',
        'field_report_additional_fee' => 'decimal:2',
        'manager_approved_additional_fee' => 'boolean',
        'frontdesk_confirmed_complete' => 'boolean',
        'manager_confirmed_complete' => 'boolean',
        'field_report_submitted_at' => 'datetime',
        'additional_fee_approved_at' => 'datetime',
        'frontdesk_confirmed_at' => 'datetime',
        'manager_confirmed_at' => 'datetime',
    ];

    public function masjid()
    {
        return $this->belongsTo(Masjid::class);
    }

    public function serviceDetails()
    {
        return $this->hasMany(ServiceDetail::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    public function workflowSteps()
    {
        return $this->hasMany(\App\Models\WorkflowStep::class)->orderBy('created_at');
    }

    public function latestWorkflowStep()
    {
        return $this->hasOne(\App\Models\WorkflowStep::class)->latestOfMany('created_at');
    }

    public function technicianAssignment()
    {
        return $this->hasOne(\App\Models\TechnicianAssignment::class);
    }

    public function histories()
    {
        return $this->hasMany(\App\Models\ServiceOrderHistory::class, 'service_order_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', self::activeStatuses());
    }

    public static function activeStatuses(): array
    {
        return self::ACTIVE_STATUSES;
    }

    public static function statusLabel(?string $status): string
    {
        if (! is_string($status) || $status === '') {
            return 'Unknown';
        }

        return self::STATUS_LABELS[$status] ?? ucwords(str_replace('_', ' ', $status));
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::activeStatuses(), true);
    }

    public static function generateOrderNumber(): string
    {
        $prefix = 'SO-' . date('Ymd') . '-';
        $last = self::where('order_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->first();

        $next = $last ? ((int) substr($last->order_number, -3)) + 1 : 1;
        return $prefix . str_pad($next, 3, '0', STR_PAD_LEFT);
    }

    public function isExpired(): bool
    {
        return $this->service_date < now()->toDateString() && $this->status === 'spk_invoice_created';
    }

    public function hasFieldReport(): bool
    {
        return !empty($this->field_report_notes) || !empty($this->field_report_additional_fee);
    }

    public function isReadyForDualConfirmation(): bool
    {
        return $this->status === 'completed' &&
               $this->frontdesk_confirmed_complete &&
               $this->manager_confirmed_complete;
    }

    public function needsFieldReport(): bool
    {
        return in_array($this->status, ['in_progress']) &&
               is_null($this->field_report_submitted_at);
    }

    public function needsAdditionalFeeApproval(): bool
    {
        return !is_null($this->field_report_additional_fee) &&
               $this->field_report_additional_fee > 0 &&
               !$this->manager_approved_additional_fee;
    }

    public function canTechnicianSubmitReport(): bool
    {
        return in_array($this->status, ['in_progress']);
    }

    public function canManagerApproveFee(): bool
    {
        return $this->needsAdditionalFeeApproval();
    }

    public function canConfirmOrderSelesai(string $role): bool
    {
        if ($this->status !== 'completed') {
            return false;
        }

        if ($role === 'frontdesk' || $role === 'admin') {
            return !$this->frontdesk_confirmed_complete;
        }

        if ($role === 'manager' || $role === 'admin') {
            return !$this->manager_confirmed_complete;
        }

        return false;
    }

    public function needsSpkInvoiceCreation(): bool
    {
        return false; // SPK is created when order is created
    }

    public function needsSpkInvoiceApproval(): bool
    {
        return $this->status === 'spk_invoice_created';
    }

    public function needsTechnicianReport(): bool
    {
        return $this->status === 'in_progress' && is_null($this->field_report_submitted_at);
    }

    public function needsInvoiceEdit(): bool
    {
        return $this->hasFieldReport() && !$this->manager_approved_additional_fee;
    }

    public function needsPayment(): bool
    {
        return $this->status === 'waiting_payment';
    }

    public function canPrintDocuments(): bool
    {
        return $this->status === 'completed' && $this->isReadyForDualConfirmation();
    }
}
