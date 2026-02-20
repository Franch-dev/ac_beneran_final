<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceDetail extends Model
{
    protected $fillable = ['service_order_id', 'pk_type', 'brand', 'quantity', 'price_per_unit'];

    public function serviceOrder()
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    public function getSubtotalAttribute(): float
    {
        return $this->quantity * $this->price_per_unit;
    }
}
