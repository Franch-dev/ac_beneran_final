<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceOrderHistory extends Model
{
    use HasFactory;

    protected $connection = 'ac_service';

    protected $table = 'service_order_histories';

    protected $fillable = [
        'service_order_id',
        'archived_at',
        'summary',
        'order_snapshot',
        'archived_by_id',
    ];

    protected $casts = [
        'order_snapshot' => 'array',
        'archived_at' => 'datetime',
    ];

    public function serviceOrder()
    {
        return $this->belongsTo(ServiceOrder::class, 'service_order_id');
    }

    public function archivedBy()
    {
        return $this->belongsTo(User::class, 'archived_by_id');
    }
}
