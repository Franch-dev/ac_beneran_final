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
        DB::statement("ALTER TABLE service_orders MODIFY status ENUM('spk_invoice_created','approved','in_progress','waiting_invoice','waiting_review','payment_verified','completed')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE service_orders MODIFY status ENUM('spk_invoice_created','approved','in_progress','waiting_invoice','waiting_review','completed')");
    }
};
