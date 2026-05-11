<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $connection = 'ac_service';

    protected $fillable = ['service_order_id', 'invoice_number', 'total_price', 'payment_verified_at'];

    public function serviceOrder()
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    public static function generateInvoiceNumber(): string
    {
        $prefix = 'INV-' . date('Ymd') . '-';
        $last = self::where('invoice_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->first();
        $next = $last ? ((int) substr($last->invoice_number, -3)) + 1 : 1;
        return $prefix . str_pad($next, 3, '0', STR_PAD_LEFT);
    }
}
