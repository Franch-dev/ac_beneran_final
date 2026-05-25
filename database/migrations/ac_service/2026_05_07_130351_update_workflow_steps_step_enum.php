<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::connection('ac_service')->getDriverName() !== 'mysql') {
            return;
        }

        // First expand the enum to include both old and new values
        DB::connection('ac_service')->statement("ALTER TABLE workflow_steps MODIFY COLUMN step ENUM(
            'created',
            'approved',
            'assigned',
            'in_progress',
            'completed',
            'invoice_generated',
            'closed',
            'cancelled',
            'guest_created',
            'frontdesk_created',
            'spk_invoice_created',
            'spk_invoice_approved',
            'technician_reported',
            'edited_invoice_created',
            'edited_invoice_approved',
            'invoice_edited',
            'payment_received',
            'printed'
        ) NOT NULL");

        // Update old step names to new ones
        DB::connection('ac_service')->table('workflow_steps')
            ->where('step', 'created')
            ->update(['step' => 'frontdesk_created']);

        DB::connection('ac_service')->table('workflow_steps')
            ->where('step', 'approved')
            ->update(['step' => 'spk_invoice_approved']);

        DB::connection('ac_service')->table('workflow_steps')
            ->where('step', 'invoice_generated')
            ->update(['step' => 'payment_received']);

        DB::connection('ac_service')->table('workflow_steps')
            ->where('step', 'closed')
            ->update(['step' => 'completed']);

        // Remove old values from enum
        DB::connection('ac_service')->statement("ALTER TABLE workflow_steps MODIFY COLUMN step ENUM(
            'guest_created',
            'frontdesk_created',
            'spk_invoice_created',
            'spk_invoice_approved',
            'assigned',
            'in_progress',
            'technician_reported',
            'edited_invoice_created',
            'edited_invoice_approved',
            'invoice_edited',
            'payment_received',
            'printed',
            'completed',
            'cancelled'
        ) NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection('ac_service')->getDriverName() !== 'mysql') {
            return;
        }

        // Revert to original enum
        DB::connection('ac_service')->statement("ALTER TABLE workflow_steps MODIFY COLUMN step ENUM(
            'created',
            'approved',
            'assigned',
            'in_progress',
            'completed',
            'invoice_generated',
            'closed',
            'cancelled'
        ) NOT NULL");
    }
};
