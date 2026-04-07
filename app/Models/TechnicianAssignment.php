<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TechnicianAssignment extends Model
{
    protected $connection = 'ac_service';

    protected $fillable = [
        'service_order_id',
        'technician_id',
        'technician_name',
        'assigned_by',
        'assigned_by_name',
        'status',
        'technician_notes',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function serviceOrder()
    {
        return $this->belongsTo(ServiceOrder::class);
    }
}

