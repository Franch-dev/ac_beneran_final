<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::connection('ac_service')->hasTable('migrations')
            && DB::connection('ac_service')->table('migrations')
                ->where('migration', '2026_05_15_000002_update_service_order_status_enum')
                ->exists()) {
            return;
        }

        if (! Schema::connection('ac_service')->hasTable('service_orders') || DB::connection('ac_service')->getDriverName() !== 'mysql') {
            return;
        }

        DB::connection('ac_service')->statement("
            ALTER TABLE service_orders
            MODIFY status ENUM(
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::connection('ac_service')->hasTable('migrations')
            && DB::connection('ac_service')->table('migrations')
                ->where('migration', '2026_05_15_000002_update_service_order_status_enum')
                ->exists()) {
            return;
        }

        if (! Schema::connection('ac_service')->hasTable('service_orders') || DB::connection('ac_service')->getDriverName() !== 'mysql') {
            return;
        }

        DB::connection('ac_service')->statement("
            ALTER TABLE service_orders
            MODIFY status ENUM(
                'spk_invoice_created',
                'approved',
                'in_progress',
                'waiting_invoice',
                'waiting_review',
                'completed'
            ) NOT NULL DEFAULT 'spk_invoice_created'
        ");
    }
};
