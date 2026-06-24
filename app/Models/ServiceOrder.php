<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ServiceOrder extends Model
{
    use HasFactory;

    public const ALL_STATUSES = [
        'pending_review',
        'approved',
        'spk_invoice_created',
        'spk_invoice_approved',
        'technician_assigned',
        'in_progress',
        'waiting_review',
        'invoice_editing',
        'fee_review',
        'waiting_payment',
        'payment_verified',
        'completed',
        'closed',
        'cancelled',
    ];

    public const ACTIVE_STATUSES = [
        'pending_review',
        'approved',
        'spk_invoice_created',
        'spk_invoice_approved',
        'technician_assigned',
        'in_progress',
        'waiting_review',
        'invoice_editing',
        'fee_review',
        'waiting_payment',
        'payment_verified',
        'completed',
    ];

    public const STATUS_LABELS = [
        'pending_review' => 'Menunggu Persetujuan Manager',
        'approved' => 'Disetujui Manager',
        'spk_invoice_created' => 'SPK & Invoice Dibuat',
        'spk_invoice_approved' => 'SPK & Invoice Disetujui',
        'technician_assigned' => 'Teknisi Ditugaskan',
        'in_progress' => 'Sedang Dikerjakan',
        'waiting_review' => 'Menunggu Review Akhir',
        'invoice_editing' => 'Invoice Sedang Diedit',
        'fee_review' => 'Menunggu Review Biaya',
        'waiting_payment' => 'Menunggu Pembayaran',
        'payment_verified' => 'Pembayaran Terverifikasi',
        'completed' => 'Selesai',
        'closed' => 'Ditutup',
        'cancelled' => 'Dibatalkan',
    ];

    protected $connection = 'ac_service';

    protected $fillable = [
        'masjid_id', 'order_number', 'meeting_person',
        'phone', 'service_date', 'notes', 'status',
        'archived_at',
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
        'archived_at' => 'datetime',
        'field_report_additional_fee' => 'decimal:2',
        'field_report_tools_materials' => 'array',
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

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(Receipt::class);
    }

    public function workflowSteps(): HasMany
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

    public function histories(): HasMany
    {
        return $this->hasMany(\App\Models\ServiceOrderHistory::class, 'service_order_id');
    }

    public function photoProofs(): HasMany
    {
        return $this->hasMany(PhotoProof::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->whereNull('archived_at')
            ->whereIn('status', self::activeStatuses());
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
        return is_null($this->archived_at)
            && in_array($this->status, self::activeStatuses(), true);
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
        return $this->service_date < now()->toDateString()
            && in_array($this->status, ['pending_review', 'approved'], true);
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
        return $this->status === 'approved' && ! $this->invoice;
    }

    public function needsSpkInvoiceApproval(): bool
    {
        return $this->status === 'spk_invoice_created' && (bool) $this->invoice;
    }

    public function needsTechnicianReport(): bool
    {
        return $this->status === 'in_progress' && is_null($this->field_report_submitted_at);
    }

    public function needsInvoiceEdit(): bool
    {
        return in_array($this->status, ['invoice_editing', 'fee_review'], true);
    }

    public function needsPayment(): bool
    {
        return $this->status === 'waiting_payment';
    }

    public function isInInternalPaymentWindow(): bool
    {
        return in_array($this->status, ['waiting_payment', 'payment_verified'], true)
            && (bool) $this->invoice;
    }

    public function hasPaymentProofPhoto(): bool
    {
        if (array_key_exists('photo_proofs_count', $this->attributes)) {
            return (int) $this->attributes['photo_proofs_count'] > 0;
        }

        return $this->photoProofs()->exists();
    }

    public function hasPaymentProofPhotoForTechnician(int $technicianId): bool
    {
        $assignment = $this->technicianAssignment;

        if (! $assignment || (int) $assignment->technician_id !== $technicianId) {
            return false;
        }

        return $this->photoProofs()
            ->where('technician_assignment_id', $assignment->id)
            ->where('created_by', $technicianId)
            ->exists();
    }

    public function canAccessInternalPayment(?User $user): bool
    {
        if (! $user || ! $this->isInInternalPaymentWindow()) {
            return false;
        }

        if ($user->isAdmin() || $user->isManager() || $user->isFrontdesk()) {
            return true;
        }

        return $user->isTechnician()
            && $this->technicianAssignment
            && (int) $this->technicianAssignment->technician_id === (int) $user->id
            && $this->hasPaymentProofPhotoForTechnician((int) $user->id);
    }

    public function canManageInternalPayment(?User $user): bool
    {
        return (bool) $user && ($user->isAdmin() || $user->isManager());
    }

    public function canRecordCashInternalPayment(?User $user): bool
    {
        if (! $user || ! $this->isInInternalPaymentWindow()) {
            return false;
        }

        if ($this->canManageInternalPayment($user)) {
            return true;
        }

        return $user->isTechnician()
            && $this->technicianAssignment
            && (int) $this->technicianAssignment->technician_id === (int) $user->id
            && $this->hasPaymentProofPhotoForTechnician((int) $user->id);
    }

    public function canViewSpkInvoice(): bool
    {
        return in_array($this->status, [
            'spk_invoice_approved', 'technician_assigned', 'in_progress',
            'waiting_review', 'invoice_editing', 'fee_review',
            'waiting_payment', 'payment_verified', 'completed', 'closed',
        ], true);
    }

    public function canPrintDocuments(): bool
    {
        return $this->canViewSpkInvoice();
    }
}
