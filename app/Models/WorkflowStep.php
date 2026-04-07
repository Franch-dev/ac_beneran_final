<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowStep extends Model
{
    protected $connection = 'ac_service';

    protected $fillable = [
        'service_order_id',
        'step',
        'actor_id',
        'actor_name',
        'actor_role',
        'notes',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function serviceOrder()
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    public static function stepLabel(string $step): string
    {
        return match($step) {
            'created'   => 'Order Dibuat',
            'approved'  => 'Disetujui Manager',
            'assigned'  => 'Teknisi Ditugaskan',
            'in_progress' => 'Sedang Dikerjakan',
            'completed' => 'Pekerjaan Selesai',
            'invoice_generated' => 'Invoice Dibuat',
            'closed'    => 'Ditutup / Invoice',
            'cancelled' => 'Dibatalkan',
            default     => ucfirst($step),
        };
    }

    public static function stepIcon(string $step): string
    {
        return match($step) {
            'created'     => 'fas fa-plus-circle',
            'approved'    => 'fas fa-check-circle',
            'assigned'    => 'fas fa-user-hard-hat',
            'in_progress' => 'fas fa-tools',
            'completed'   => 'fas fa-check-double',
            'invoice_generated' => 'fas fa-file-invoice',
            'closed'      => 'fas fa-file-invoice',
            'cancelled'   => 'fas fa-times-circle',
            default       => 'fas fa-circle',
        };
    }

    public static function stepColor(string $step): string
    {
        return match($step) {
            'created'     => '#1a73e8',
            'approved'    => '#1e8e3e',
            'assigned'    => '#9c27b0',
            'in_progress' => '#f9ab00',
            'completed'   => '#137333',
            'invoice_generated' => '#0288d1',
            'closed'      => '#0288d1',
            'cancelled'   => '#c5221f',
            default       => '#5f6368',
        };
    }
}
