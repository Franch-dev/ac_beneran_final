<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceEdit extends Model
{
    protected $connection = 'ac_service';

    public $timestamps = false; // immutable - only created_at

    protected $fillable = [
        'invoice_id',
        'service_order_id',
        'edited_by',
        'edited_by_name',
        'edited_by_role',
        'edit_type',
        'old_value',
        'new_value',
        'notes',
        'created_at',
    ];

    protected $casts = [
        'old_value' => 'array',
        'new_value' => 'array',
        'created_at' => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function serviceOrder(): BelongsTo
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeByEditType(Builder $query, string $type): Builder
    {
        return $query->where('edit_type', $type);
    }

    public function scopeByActor(Builder $query, int $userId): Builder
    {
        return $query->where('edited_by', $userId);
    }
}
