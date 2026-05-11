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
        // First expand the enum to include the new value
        DB::statement("ALTER TABLE service_orders MODIFY COLUMN status ENUM('pending', 'spk_invoice_created', 'approved', 'in_progress', 'waiting_invoice', 'waiting_review', 'completed') NOT NULL DEFAULT 'spk_invoice_created'");

        // Then update all 'pending' records to 'spk_invoice_created'
        DB::connection('ac_service')->table('service_orders')
            ->where('status', 'pending')
            ->update(['status' => 'spk_invoice_created']);

        // Finally remove 'pending' from the enum
        DB::statement("ALTER TABLE service_orders MODIFY COLUMN status ENUM('spk_invoice_created', 'approved', 'in_progress', 'waiting_invoice', 'waiting_review', 'completed') NOT NULL DEFAULT 'spk_invoice_created'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First update all 'spk_invoice_created' records back to 'pending'
        DB::connection('ac_service')->table('service_orders')
            ->where('status', 'spk_invoice_created')
            ->update(['status' => 'pending']);

        // Then revert the enum
        DB::statement("ALTER TABLE service_orders MODIFY COLUMN status ENUM('pending', 'approved', 'in_progress', 'waiting_invoice', 'waiting_review', 'completed') NOT NULL DEFAULT 'pending'");
    }
};
