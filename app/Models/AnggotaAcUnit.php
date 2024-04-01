<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnggotaAcUnit extends Model
{
    use HasFactory;

    protected $connection = 'ac_service';

    protected $fillable = [
        'anggota_id',
        'anggota_custom_id',
        'pk_type',
        'brand',
        'quantity',
        'last_service_date',
    ];

    protected $casts = [
        'last_service_date' => 'date',
    ];

    public function anggota()
    {
        return $this->belongsTo(Anggota::class);
    }
}
