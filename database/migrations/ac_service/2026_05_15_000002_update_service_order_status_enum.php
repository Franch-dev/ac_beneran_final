<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Update service_orders.status enum to include new workflow statuses.
     *
     * New workflow:
     * pending_review → approved → spk_invoice_created → spk_invoice_approved →
     * technician_assigned → in_progress → work_completed → pending_fee_approval →
     * fee_approved → waiting_payment → payment_verified → completed
     */
    public function up(): void
    {
        if (DB::connection('ac_service')->getDriverName() !== 'mysql') {
            return;
        }

        DB::connection('ac_service')->statement("
            ALTER TABLE service_orders
            MODIFY COLUMN status ENUM(
                'pending_review',
                'approved',
                'spk_invoice_created',
                'spk_invoice_approved',
                'technician_assigned',
                'in_progress',
                'work_completed',
                'pending_fee_approval',
                'fee_approved',
                'waiting_payment',
                'payment_verified',
                'waiting_review',
                'completed',
                'cancelled'
            ) NOT NULL DEFAULT 'spk_invoice_created'
        ");
    }

    public function down(): void
    {
        if (DB::connection('ac_service')->getDriverName() !== 'mysql') {
            return;
        }

        DB::connection('ac_service')->statement("
            ALTER TABLE service_orders
            MODIFY COLUMN status ENUM(
                'spk_invoice_created',
                'approved',
                'waiting_payment',
                'payment_verified',
                'in_progress',
                'waiting_review',
                'completed',
                'cancelled'
            ) NOT NULL DEFAULT 'spk_invoice_created'
        ");
    }
};
