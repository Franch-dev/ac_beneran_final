<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE workflow_steps MODIFY step ENUM('guest_created','frontdesk_created','spk_invoice_created','spk_invoice_approved','assigned','in_progress','technician_reported','edited_invoice_created','edited_invoice_approved','invoice_edited','payment_received','payment_verified','printed','completed','closed','cancelled')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE workflow_steps MODIFY step ENUM('guest_created','frontdesk_created','spk_invoice_created','spk_invoice_approved','assigned','in_progress','technician_reported','edited_invoice_created','edited_invoice_approved','invoice_edited','payment_received','payment_verified','printed','completed','cancelled')");
    }
};
