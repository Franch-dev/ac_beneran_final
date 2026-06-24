<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection('ac_service')->getDriverName() !== 'mysql') {
            return;
        }

        if (Schema::connection('ac_service')->hasTable('service_orders')) {
            DB::connection('ac_service')->table('service_orders')
                ->whereIn('status', ['work_completed', 'pending_fee_approval'])
                ->update(['status' => 'waiting_review']);

            DB::connection('ac_service')->table('service_orders')
                ->where('status', 'fee_approved')
                ->update(['status' => 'waiting_payment']);

            DB::connection('ac_service')->statement("
                ALTER TABLE service_orders
                MODIFY COLUMN status ENUM(
                    'pending_review',
                    'approved',
                    'spk_invoice_created',
                    'spk_invoice_approved',
                    'technician_assigned',
                    'in_progress',
                    'waiting_review',
                    'invoice_editing',
                    'fee_review',
                    'waiting_payment',
                    'payment_verified',
                    'completed',
                    'closed',
                    'cancelled'
                ) NOT NULL DEFAULT 'pending_review'
            ");
        }

        if (Schema::connection('ac_service')->hasTable('workflow_steps')) {
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
                    'invoice_editing',
                    'fee_review',
                    'invoice_edited',
                    'payment_received',
                    'printed',
                    'waiting_review',
                    'completed',
                    'closed',
                    'cancelled'
                ) NOT NULL
            ");

            DB::connection('ac_service')->table('workflow_steps')
                ->where('step', 'edited_invoice_created')
                ->update(['step' => 'invoice_editing']);

            DB::connection('ac_service')->table('workflow_steps')
                ->where('step', 'edited_invoice_approved')
                ->update(['step' => 'fee_review']);

            DB::connection('ac_service')->table('workflow_steps')
                ->where('step', 'invoice_generated')
                ->update(['step' => 'spk_invoice_created']);

            DB::connection('ac_service')->table('workflow_steps')
                ->where('step', 'payment_received')
                ->update(['step' => 'payment_verified']);

            DB::connection('ac_service')->statement("
                ALTER TABLE workflow_steps
                MODIFY step ENUM(
                    'guest_created',
                    'frontdesk_created',
                    'approved',
                    'spk_invoice_created',
                    'spk_invoice_approved',
                    'assigned',
                    'in_progress',
                    'technician_reported',
                    'invoice_editing',
                    'fee_review',
                    'invoice_edited',
                    'waiting_payment',
                    'payment_verified',
                    'printed',
                    'waiting_review',
                    'completed',
                    'closed',
                    'cancelled'
                ) NOT NULL
            ");
        }
    }

    public function down(): void
    {
        if (DB::connection('ac_service')->getDriverName() !== 'mysql') {
            return;
        }

        if (Schema::connection('ac_service')->hasTable('service_orders')) {
            DB::connection('ac_service')->statement("
                ALTER TABLE service_orders
                MODIFY COLUMN status ENUM(
                    'pending_review',
                    'approved',
                    'spk_invoice_created',
                    'spk_invoice_approved',
                    'technician_assigned',
                    'in_progress',
                    'waiting_review',
                    'invoice_editing',
                    'fee_review',
                    'waiting_payment',
                    'payment_verified',
                    'completed',
                    'closed',
                    'cancelled',
                    'work_completed',
                    'pending_fee_approval',
                    'fee_approved'
                ) NOT NULL DEFAULT 'pending_review'
            ");
        }
    }
};
