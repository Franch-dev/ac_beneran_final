<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
                ->where('migration', '2026_05_16_000002_add_payment_fields_to_invoices')
                ->exists()) {
            return;
        }

        if (! Schema::connection('ac_service')->hasTable('invoices') || Schema::connection('ac_service')->hasColumn('invoices', 'payment_verified_at')) {
            return;
        }

        Schema::connection('ac_service')->table('invoices', function (Blueprint $table) {
            $table->timestamp('payment_verified_at')->nullable()->after('total_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::connection('ac_service')->hasTable('migrations')
            && DB::connection('ac_service')->table('migrations')
                ->where('migration', '2026_05_16_000002_add_payment_fields_to_invoices')
                ->exists()) {
            return;
        }

        if (! Schema::connection('ac_service')->hasTable('invoices') || ! Schema::connection('ac_service')->hasColumn('invoices', 'payment_verified_at')) {
            return;
        }

        Schema::connection('ac_service')->table('invoices', function (Blueprint $table) {
            $table->dropColumn('payment_verified_at');
        });
    }
};
