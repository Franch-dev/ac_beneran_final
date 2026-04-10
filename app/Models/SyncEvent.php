<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncEvent extends Model
{
    protected $connection = 'ac_service';

    protected $fillable = [
        'type',
        'resource',
        'resource_id',
        'masjid_id',
        'service_order_id',
        'actor_id',
        'actor_name',
        'actor_role',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
