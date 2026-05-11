<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ac_service')->table('service_orders', function (Blueprint $table): void {
            $table->index(['status', 'service_date'], 'service_orders_status_service_date_idx');
            $table->index(['masjid_id', 'status'], 'service_orders_masjid_status_idx');
            $table->index(['created_at'], 'service_orders_created_at_idx');
        });

        Schema::connection('ac_service')->table('ac_units', function (Blueprint $table): void {
            $table->index(['masjid_id', 'last_service_date'], 'ac_units_masjid_last_service_date_idx');
            $table->index(['pk_type', 'brand'], 'ac_units_pk_brand_idx');
        });
    }

    public function down(): void
    {
        Schema::connection('ac_service')->table('service_orders', function (Blueprint $table): void {
            $table->dropIndex('service_orders_status_service_date_idx');
            $table->dropIndex('service_orders_masjid_status_idx');
            $table->dropIndex('service_orders_created_at_idx');
        });

        Schema::connection('ac_service')->table('ac_units', function (Blueprint $table): void {
            $table->dropIndex('ac_units_masjid_last_service_date_idx');
            $table->dropIndex('ac_units_pk_brand_idx');
        });
    }
};

