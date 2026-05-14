<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fix the status enum to include all workflow statuses.
     *
     * The previous migration added 'payment_verified' but missed 'waiting_payment'
     * and 'cancelled'. The code uses 'waiting_payment' but the DB had 'waiting_invoice'.
     * This migration aligns the DB enum with the application's state machine.
     *
     * Full workflow: spk_invoice_created → approved → waiting_payment → payment_verified
     *                → in_progress → waiting_review → completed
     */
    public function up(): void
    {
        // First: migrate any rows stuck on 'waiting_invoice' to 'waiting_payment'
        // (waiting_invoice was the old name for the same concept)
        DB::connection('ac_service')->statement("
            ALTER TABLE service_orders
            MODIFY COLUMN status ENUM(
                'spk_invoice_created',
                'approved',
                'waiting_payment',
                'waiting_invoice',
                'payment_verified',
                'in_progress',
                'waiting_review',
                'completed',
                'cancelled'
            ) NOT NULL DEFAULT 'spk_invoice_created'
        ");

        // Migrate old 'waiting_invoice' values to 'waiting_payment'
        DB::connection('ac_service')->table('service_orders')
            ->where('status', 'waiting_invoice')
            ->update(['status' => 'waiting_payment']);

        // Now remove the obsolete 'waiting_invoice' value
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

    public function down(): void
    {
        // Revert: put 'waiting_invoice' back
        DB::connection('ac_service')->statement("
            ALTER TABLE service_orders
            MODIFY COLUMN status ENUM(
                'spk_invoice_created',
                'approved',
                'waiting_payment',
                'waiting_invoice',
                'payment_verified',
                'in_progress',
                'waiting_review',
                'completed'
            ) NOT NULL DEFAULT 'spk_invoice_created'
        ");

        DB::connection('ac_service')->table('service_orders')
            ->where('status', 'waiting_payment')
            ->update(['status' => 'waiting_invoice']);

        DB::connection('ac_service')->statement("
            ALTER TABLE service_orders
            MODIFY COLUMN status ENUM(
                'spk_invoice_created',
                'approved',
                'in_progress',
                'waiting_invoice',
                'waiting_review',
                'payment_verified',
                'completed'
            ) NOT NULL DEFAULT 'spk_invoice_created'
        ");
    }
};
