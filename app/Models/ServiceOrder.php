<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceOrder extends Model
{
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
