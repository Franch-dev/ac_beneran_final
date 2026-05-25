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
        'assigned_at',
        'completion_notes',
        'fee_reported',
        'fee_amount',
        'fee_description',
        'fee_tools_materials',
        'fee_reported_at',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'fee_reported' => 'boolean',
        'fee_amount' => 'decimal:2',
        'assigned_at' => 'datetime',
        'fee_reported_at' => 'datetime',
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function serviceOrder()
    {
        return $this->belongsTo(ServiceOrder::class);
    }
}
