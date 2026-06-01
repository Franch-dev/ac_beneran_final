<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ac_service')->table('service_details', function (Blueprint $table) {
            $table->string('source', 20)->default('masjid')->after('service_order_id');
            $table->index('source');
        });

        Schema::connection('ac_service')->table('invoices', function (Blueprint $table) {
            $table->string('source', 20)->default('masjid')->after('service_order_id');
            $table->index('source');
        });

        Schema::connection('ac_service')->table('workflow_steps', function (Blueprint $table) {
            $table->string('source', 20)->default('masjid')->after('service_order_id');
            $table->index('source');
        });

        Schema::connection('ac_service')->table('technician_assignments', function (Blueprint $table) {
            $table->string('source', 20)->default('masjid')->after('service_order_id');
            $table->index('source');
        });

        Schema::connection('ac_service')->table('photo_proofs', function (Blueprint $table) {
            $table->string('source', 20)->default('masjid')->after('service_order_id');
            $table->index('source');
        });

        Schema::connection('ac_service')->table('receipts', function (Blueprint $table) {
            $table->string('source', 20)->default('masjid')->after('service_order_id');
            $table->index('source');
        });

        Schema::connection('ac_service')->table('service_order_histories', function (Blueprint $table) {
            $table->string('source', 20)->default('masjid')->after('service_order_id');
            $table->index('source');
        });

        Schema::connection('ac_service')->table('invoice_edits', function (Blueprint $table) {
            $table->string('source', 20)->default('masjid')->after('service_order_id');
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::connection('ac_service')->table('service_details', fn (Blueprint $t) => $t->dropColumn('source'));
        Schema::connection('ac_service')->table('invoices', fn (Blueprint $t) => $t->dropColumn('source'));
        Schema::connection('ac_service')->table('workflow_steps', fn (Blueprint $t) => $t->dropColumn('source'));
        Schema::connection('ac_service')->table('technician_assignments', fn (Blueprint $t) => $t->dropColumn('source'));
        Schema::connection('ac_service')->table('photo_proofs', fn (Blueprint $t) => $t->dropColumn('source'));
        Schema::connection('ac_service')->table('receipts', fn (Blueprint $t) => $t->dropColumn('source'));
        Schema::connection('ac_service')->table('service_order_histories', fn (Blueprint $t) => $t->dropColumn('source'));
        Schema::connection('ac_service')->table('invoice_edits', fn (Blueprint $t) => $t->dropColumn('source'));
    }
};
