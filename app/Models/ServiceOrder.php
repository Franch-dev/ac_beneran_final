<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ServiceOrder extends Model
{
    public const ACTIVE_STATUSES = [
        'pending',
        'approved',
        'in_progress',
        'waiting_invoice',
        'waiting_review',
    ];

    public const STATUS_LABELS = [
        'pending' => 'Pending',
        'approved' => 'SPK Issued',
        'in_progress' => 'In Progress',
        'waiting_invoice' => 'Waiting Invoice',
        'waiting_review' => 'Waiting Review',
        'completed' => 'Completed',
    ];

    protected $connection = 'ac_service';

    protected $fillable = [
        'masjid_id', 'order_number', 'meeting_person',
        'phone', 'service_date', 'notes', 'status'
    ];

    protected $casts = ['service_date' => 'date'];

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

    public function technicianAssignment()
    {
        return $this->hasOne(\App\Models\TechnicianAssignment::class);
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
        return $this->service_date < now()->toDateString() && $this->status === 'pending';
    }
}
