<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Receipt extends Model
{
    protected $connection = 'ac_service';

    protected $fillable = [
        'service_order_id',
        'invoice_id',
        'receipt_number',
        'payment_method',
        'payment_amount',
        'payment_date',
        'transfer_bank',
        'transfer_reference',
        'qris_reference',
        'verified_by',
        'verified_by_name',
        'digital_signature_path',
        'printed_name',
        'notes',
    ];

    protected $casts = [
        'payment_amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function serviceOrder(): BelongsTo
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public static function generateReceiptNumber(): string
    {
        $date = now()->format('Ymd');
        $last = static::where('receipt_number', 'like', "REC-{$date}-%")->count();
        $seq = str_pad($last + 1, 3, '0', STR_PAD_LEFT);

        return "REC-{$date}-{$seq}";
    }

    public function getFormattedAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->payment_amount, 0, ',', '.');
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'cash' => 'Tunai',
            'transfer' => 'Transfer Bank',
            'qris' => 'QRIS',
            default => ucfirst($this->payment_method),
        };
    }
}
