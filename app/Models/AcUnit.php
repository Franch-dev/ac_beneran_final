<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'masjid_id',
        'pk_type',
        'brand',
        'quantity',
        'last_service_date',
    ];

    protected $casts = [
        'last_service_date' => 'date',
    ];

    public function masjid()
    {
        return $this->belongsTo(Masjid::class);
    }
}
