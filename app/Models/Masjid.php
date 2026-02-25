<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Masjid extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'custom_id',
        'type',
        'name',
        'address',
        'dkm_name',
        'marbot_name',
        'phone_numbers',
    ];

    protected $casts = [
        'phone_numbers' => 'array',
    ];

    protected $appends = [
        'urgency_status',
        'max_days_since_service',
    ];

    public function acUnits()
    {
        return $this->hasMany(AcUnit::class);
    }

    public function serviceOrders()
    {
        return $this->hasMany(ServiceOrder::class);
    }

    public static function generateCustomId(string $type): string
    {
        // Prefix 001 for Masjid, 002 for Musholla (as requested).
        $prefix = $type === 'musholla' ? '002' : '001';

        $last = self::where('custom_id', 'like', "{$prefix}-%")
            ->orderByDesc('id')
            ->first();

        $next = $last
            ? ((int) substr($last->custom_id, strlen($prefix) + 1)) + 1
            : 1;

        return sprintf('%s-%04d', $prefix, $next);
    }

    public function getMaxDaysSinceServiceAttribute(): ?int
    {
        // If there are no AC units, we can't compute urgency.
        if ($this->acUnits->isEmpty()) {
            return null;
        }

        $dates = $this->acUnits
            ->pluck('last_service_date')
            ->filter(); // drop nulls

        if ($dates->isEmpty()) {
            return null;
        }

        return $dates
            ->map(fn($d) => Carbon::parse($d)->diffInDays(now()))
            ->max();
    }

    public function getUrgencyStatusAttribute(): string
    {
        $days = $this->max_days_since_service;
        // No units => unknown. Units without service date => treat as overdue (needs attention).
        if ($this->acUnits->isEmpty()) return 'unknown';
        if ($days === null) return 'overdue';
        if ($days < 90) return 'aman';
        if ($days <= 120) return 'harus_servis';
        return 'overdue';
    }
}
