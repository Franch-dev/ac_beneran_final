<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PhotoProof extends Model
{
    protected $connection = 'ac_service';

    protected $fillable = [
        'service_order_id',
        'technician_assignment_id',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'description',
        'taken_at',
        'created_by',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'taken_at' => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function serviceOrder(): BelongsTo
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    public function technicianAssignment(): BelongsTo
    {
        return $this->belongsTo(TechnicianAssignment::class);
    }

    // ── Methods ───────────────────────────────────────────────────

    public function getUrl(): string
    {
        return '';
    }

    public function deleteFile(): bool
    {
        if (Storage::disk('local')->exists($this->file_path)) {
            Storage::disk('local')->delete($this->file_path);
        }

        return $this->delete();
    }
}
