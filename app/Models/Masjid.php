<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Masjid extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'custom_id', 'type', 'name', 'address',
        'dkm_name', 'marbot_name', 'phone_numbers'
    ];

    protected $casts = [
        'phone_numbers' => 'array',
    ];

    public function acUnits()
    {
        return $this->hasMany(AcUnit::class);
    }

    public function serviceOrders()
    {
        return $this->hasMany(ServiceOrder::class);
    }

    public function getUrgencyStatusAttribute(): string
    {
        $minDays = $this->acUnits->min(function ($unit) {
            if (!$unit->last_service_date) return 0;
            return Carbon::parse($unit->last_service_date)->diffInDays(now(), false);
        });

        if ($minDays === null) return 'unknown';
        if ($minDays < 90) return 'aman';
        if ($minDays <= 120) return 'harus_servis';
        return 'overdue';
    }

    public function getMaxDaysSinceServiceAttribute(): int
    {
        return $this->acUnits->max(function ($unit) {
            if (!$unit->last_service_date) return 0;
            return Carbon::parse($unit->last_service_date)->diffInDays(now(), false);
        }) ?? 0;
    }

    public static function generateCustomId(string $type): string
    {
        $prefix = $type === 'masjid' ? '001' : '002';
        $year = date('Y');

        // Find used numbers this year
        $existing = self::withTrashed()
            ->where('custom_id', 'like', $prefix . '%' . $year)
            ->pluck('custom_id')
            ->map(function ($id) use ($prefix, $year) {
                $middle = substr($id, strlen($prefix), -strlen($year));
                return (int) $middle;
            })
            ->sort()
            ->values();

        // Find smallest available number
        $next = 1;
        foreach ($existing as $num) {
            if ($num == $next) {
                $next++;
            } else {
                break;
            }
        }

        return $prefix . str_pad($next, 2, '0', STR_PAD_LEFT) . $year;
    }
}
