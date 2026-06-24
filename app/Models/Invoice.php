<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $connection = 'ac_service';

    protected $fillable = [
        'service_order_id',
        'invoice_number',
        'total_price',
        'payment_method',
        'payment_verified_at',
        'payment_verified_by',
        'payment_verified_by_name',
        'payment_notes',
        'payment_metadata',
        'cash_confirmed_at',
        'cash_confirmed_by',
        'cash_confirmed_by_name',
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
        'payment_verified_at' => 'datetime',
        'payment_metadata' => 'array',
        'cash_confirmed_at' => 'datetime',
    ];

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
