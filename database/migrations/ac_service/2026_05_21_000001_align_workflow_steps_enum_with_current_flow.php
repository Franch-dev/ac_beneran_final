<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::connection('ac_service')->hasTable('workflow_steps') || DB::connection('ac_service')->getDriverName() !== 'mysql') {
            return;
        }

        DB::connection('ac_service')->statement("
            ALTER TABLE workflow_steps
            MODIFY step ENUM(
                'guest_created',
                'frontdesk_created',
                'approved',
                'spk_invoice_created',
                'spk_invoice_approved',
                'waiting_payment',
                'payment_verified',
                'invoice_generated',
                'assigned',
                'in_progress',
                'technician_reported',
                'edited_invoice_created',
                'edited_invoice_approved',
                'invoice_edited',
                'payment_received',
                'printed',
                'waiting_review',
                'completed',
                'closed',
                'cancelled'
            ) NOT NULL
        ");
    }

    public function down(): void
    {
        if (! Schema::connection('ac_service')->hasTable('workflow_steps') || DB::connection('ac_service')->getDriverName() !== 'mysql') {
            return;
        }

        DB::connection('ac_service')->statement("
            ALTER TABLE workflow_steps
            MODIFY step ENUM(
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
                'payment_verified',
                'printed',
                'completed',
                'closed',
                'cancelled'
            ) NOT NULL
        ");
    }
};
