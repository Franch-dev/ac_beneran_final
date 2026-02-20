<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AcUnit extends Model
{
    protected $fillable = ['masjid_id', 'pk_type', 'brand', 'quantity', 'last_service_date'];

    protected $casts = ['last_service_date' => 'date'];

    public function masjid()
    {
        return $this->belongsTo(Masjid::class);
    }

    public function getDaysSinceServiceAttribute(): int
    {
        if (!$this->last_service_date) return 0;
        return Carbon::parse($this->last_service_date)->diffInDays(now(), false);
    }

    public function getUrgencyAttribute(): string
    {
        $days = $this->days_since_service;
        if ($days < 90) return 'aman';
        if ($days <= 120) return 'harus_servis';
        return 'overdue';
    }
}
