<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Anggota extends Model
{
    use SoftDeletes;

    protected $connection = 'ac_service';

    protected $fillable = [
        'custom_id',
        'type',
        'member_code',
        'registered_at',
        'name',
        'gender',
        'family_card_number',
        'national_id_number',
        'birth_date',
        'family_role',
        'membership_status',
        'phone_number',
        'whatsapp_number',
        'email',
        'location',
        'street',
        'house_number',
        'rt',
        'rw',
        'subdistrict',
        'district',
        'city',
        'province',
        'address',
        'contact_name',
        'phone_numbers',
        'setup_status',
        'setup_completed_at',
    ];

    protected $casts = [
        'phone_numbers' => 'array',
        'registered_at' => 'date',
        'birth_date' => 'date',
        'setup_completed_at' => 'datetime',
    ];

    protected $appends = [
        'urgency_status',
        'max_days_since_service',
        'setup_status_label',
    ];

    public function acUnits()
    {
        return $this->hasMany(AnggotaAcUnit::class);
    }

    public function serviceOrders()
    {
        return $this->hasMany(AnggotaServiceOrder::class);
    }

    public static function generateCustomId(): string
    {
        $prefix = '003';

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
