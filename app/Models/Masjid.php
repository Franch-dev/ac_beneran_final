<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Masjid extends Model
{
    use HasFactory;

    protected $connection = 'ac_service';

    protected $fillable = [
        'custom_id',
        'type',
        'name',
        'address',
        'dkm_name',
        'marbot_name',
        'phone_numbers',
        'setup_status',
        'setup_completed_at',
    ];

    protected $casts = [
        'phone_numbers' => 'array',
        'setup_completed_at' => 'datetime',
    ];

    protected $appends = [
        'urgency_status',
        'max_days_since_service',
        'setup_status_label',
    ];

    protected static function booted(): void
    {
        static::creating(function (Masjid $masjid): void {
            $type = strtolower((string) ($masjid->type ?? 'masjid'));
            $masjid->type = in_array($type, ['masjid', 'musholla'], true) ? $type : 'masjid';
            $masjid->custom_id ??= self::generateCustomId($masjid->type);
            $masjid->setup_status ??= 'pending_ac';
        });
    }

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
        $prefix = $type === 'musholla' ? '002' : '001';

        $last = self::where('custom_id', 'like', "{$prefix}-%")
            ->orderByDesc('id')
            ->first();

        $next = $last
            ? ((int) substr($last->custom_id, strlen($prefix) + 1)) + 1
            : 1;

        return sprintf('%s-%04d', $prefix, $next);
    }

    public function syncSetupStatus(): void
    {
        $hasUnits = $this->acUnits()->exists();

        $this->forceFill([
            'setup_status' => $hasUnits ? 'completed' : 'pending_ac',
            'setup_completed_at' => $hasUnits ? ($this->setup_completed_at ?? now()) : null,
        ])->save();
    }

    public function getSetupStatusLabelAttribute(): string
    {
        return $this->setup_status === 'completed'
            ? 'Setup lengkap'
            : 'Pending setup AC';
    }

    public function getMaxDaysSinceServiceAttribute(): ?int
    {
        if ($this->acUnits->isEmpty()) {
            return null;
        }

        $dates = $this->acUnits
            ->pluck('last_service_date')
            ->filter();

        if ($dates->isEmpty()) {
            return null;
        }

        return $dates
            ->map(fn ($date) => Carbon::parse($date)->diffInDays(now()))
            ->max();
    }

    public function getUrgencyStatusAttribute(): string
    {
        $days = $this->max_days_since_service;

        if ($this->acUnits->isEmpty()) {
            return 'unknown';
        }

        if ($days === null) {
            return 'overdue';
        }

        if ($days < 90) {
            return 'aman';
        }

        if ($days < 120) {
            return 'harus_servis';
        }

        return 'overdue';
    }
}
