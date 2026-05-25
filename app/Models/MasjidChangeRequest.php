<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasjidChangeRequest extends Model
{
    protected $connection = 'ac_service';

    protected $fillable = [
        'masjid_id', 'guest_order_id', 'field', 'old_value', 'new_value',
        'requested_by', 'requested_by_name', 'status',
        'reviewed_by', 'reviewed_by_name', 'review_notes',
    ];

    public function masjid(): BelongsTo
    {
        return $this->belongsTo(Masjid::class);
    }

    public function guestOrder(): BelongsTo
    {
        return $this->belongsTo(GuestOrder::class);
    }

    public static function fieldLabel(string $field): string
    {
        return match ($field) {
            'name' => 'Nama Masjid',
            'address' => 'Alamat',
            'dkm_name' => 'Nama DKM',
            'marbot_name' => 'Nama Marbot',
            'phone_numbers' => 'Nomor Telepon',
            default => ucfirst($field),
        };
    }
}
