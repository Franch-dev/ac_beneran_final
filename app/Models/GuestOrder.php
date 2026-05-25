<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuestOrder extends Model
{
    protected $connection = 'ac_service';

    protected $fillable = [
        'guest_name',
        'guest_phone',
        'masjid_id',
        'masjid_name',
        'address',
        'ac_type',
        'ac_amount',
        'brand',
        'problem_description',
        'status',
        'rejection_reason',
        'additional_phone_description',
        'ip_address',
    ];

    protected $casts = [
        'ac_amount' => 'integer',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function masjid(): BelongsTo
    {
        return $this->belongsTo(Masjid::class);
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending_review');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', 'rejected');
    }

    // ── Methods ───────────────────────────────────────────────────

    public function approve(): ServiceOrder
    {
        // Determine masjid name
        $masjidName = $this->masjid?->name ?? $this->masjid_name;

        return \Illuminate\Support\Facades\DB::connection('ac_service')->transaction(function () use ($masjidName) {
            // Create service order
            $order = ServiceOrder::create([
                'masjid_id' => $this->masjid_id,
                'order_number' => ServiceOrder::generateOrderNumber(),
                'meeting_person' => 'dkm', // default
                'phone' => $this->guest_phone,
                'service_date' => now()->addDays(3), // default
                'notes' => "Order dari guest: {$this->guest_name}\n" .
                           "Deskripsi: {$this->problem_description}\n" .
                           "AC: {$this->ac_amount} unit {$this->ac_type}" .
                           ($this->additional_phone_description ? "\nCatatan telepon: {$this->additional_phone_description}" : ''),
                'status' => 'pending_review',
            ]);

            // Create ServiceDetail from guest order AC data
            ServiceDetail::create([
                'service_order_id' => $order->id,
                'pk_type' => $this->ac_type,
                'brand' => $this->brand ?? '-',
                'quantity' => $this->ac_amount,
                'price_per_unit' => 0,
            ]);

            // Create workflow steps
            WorkflowStep::create([
                'service_order_id' => $order->id,
                'step' => 'guest_created',
                'actor_id' => null,
                'actor_name' => $this->guest_name,
                'actor_role' => 'guest',
                'notes' => "Order dari guest: {$this->guest_name} ({$this->guest_phone})",
            ]);

            WorkflowStep::create([
                'service_order_id' => $order->id,
                'step' => 'frontdesk_created',
                'actor_id' => auth()->id(),
                'actor_name' => auth()->user()?->name ?? 'System',
                'actor_role' => auth()->user()?->role ?? 'system',
                'notes' => "Order guest disetujui dan diverifikasi",
            ]);

            // Update guest order status
            $this->update(['status' => 'approved']);

            return $order;
        });
    }

    public function reject(string $reason): self
    {
        $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);

        return $this;
    }

    public function isPending(): bool
    {
        return $this->status === 'pending_review';
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->masjid?->name ?? $this->masjid_name ?? 'Unknown';
    }

    public function getAcDetailsAttribute(): string
    {
        return "{$this->ac_amount} unit {$this->ac_type}";
    }
}
