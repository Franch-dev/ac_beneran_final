<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceDetail extends Model
{
    protected $connection = 'ac_service';

    protected $fillable = ['service_order_id', 'pk_type', 'brand', 'quantity', 'price_per_unit'];

    public function serviceOrder()
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    public function getSubtotalAttribute(): float
    {
        return $this->quantity * $this->price_per_unit;
    }

    public function getDescriptionAttribute(): string
    {
        return trim("{$this->pk_type} {$this->brand}");
    }

    public function getPriceAttribute(): float
    {
        return (float) $this->price_per_unit;
    }
}
