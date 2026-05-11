<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Check if columns already exist
        $columns = DB::connection('ac_service')->getSchemaBuilder()->getColumnListing('service_orders');
        
        // Field Report Fields
        if (!in_array('field_report_notes', $columns)) {
            Schema::connection('ac_service')->table('service_orders', function (Blueprint $table) {
                $table->text('field_report_notes')->nullable()->after('status');
            });
        }
        if (!in_array('field_report_additional_fee', $columns)) {
            Schema::connection('ac_service')->table('service_orders', function (Blueprint $table) {
                $table->decimal('field_report_additional_fee', 12, 2)->default(0)->nullable()->after('field_report_notes');
            });
        }
        if (!in_array('field_report_tools_materials', $columns)) {
            Schema::connection('ac_service')->table('service_orders', function (Blueprint $table) {
                $table->json('field_report_tools_materials')->nullable()->after('field_report_additional_fee');
            });
        }
        if (!in_array('field_report_submitted_at', $columns)) {
            Schema::connection('ac_service')->table('service_orders', function (Blueprint $table) {
                $table->timestamp('field_report_submitted_at')->nullable()->after('field_report_tools_materials');
            });
        }
        
        // Manager approval for additional fees
        if (!in_array('manager_approved_additional_fee', $columns)) {
            Schema::connection('ac_service')->table('service_orders', function (Blueprint $table) {
                $table->boolean('manager_approved_additional_fee')->default(false)->nullable()->after('field_report_submitted_at');
            });
        }
        if (!in_array('additional_fee_approved_by', $columns)) {
            Schema::connection('ac_service')->table('service_orders', function (Blueprint $table) {
                $table->unsignedBigInteger('additional_fee_approved_by')->nullable()->after('manager_approved_additional_fee');
            });
        }
        if (!in_array('additional_fee_approved_at', $columns)) {
            Schema::connection('ac_service')->table('service_orders', function (Blueprint $table) {
                $table->timestamp('additional_fee_approved_at')->nullable()->after('additional_fee_approved_by');
            });
        }
        
        // Dual confirmation for Order Selesai
        if (!in_array('frontdesk_confirmed_complete', $columns)) {
            Schema::connection('ac_service')->table('service_orders', function (Blueprint $table) {
                $table->boolean('frontdesk_confirmed_complete')->default(false)->nullable()->after('additional_fee_approved_at');
            });
        }
        if (!in_array('frontdesk_confirmed_by', $columns)) {
            Schema::connection('ac_service')->table('service_orders', function (Blueprint $table) {
                $table->unsignedBigInteger('frontdesk_confirmed_by')->nullable()->after('frontdesk_confirmed_complete');
            });
        }
        if (!in_array('frontdesk_confirmed_at', $columns)) {
            Schema::connection('ac_service')->table('service_orders', function (Blueprint $table) {
                $table->timestamp('frontdesk_confirmed_at')->nullable()->after('frontdesk_confirmed_by');
            });
        }
        if (!in_array('manager_confirmed_complete', $columns)) {
            Schema::connection('ac_service')->table('service_orders', function (Blueprint $table) {
                $table->boolean('manager_confirmed_complete')->default(false)->nullable()->after('frontdesk_confirmed_at');
            });
        }
        if (!in_array('manager_confirmed_by', $columns)) {
            Schema::connection('ac_service')->table('service_orders', function (Blueprint $table) {
                $table->unsignedBigInteger('manager_confirmed_by')->nullable()->after('manager_confirmed_complete');
            });
        }
        if (!in_array('manager_confirmed_at', $columns)) {
            Schema::connection('ac_service')->table('service_orders', function (Blueprint $table) {
                $table->timestamp('manager_confirmed_at')->nullable()->after('manager_confirmed_by');
            });
        }
    }

    public function down(): void
    {
        Schema::connection('ac_service')->table('service_orders', function (Blueprint $table) {
            $table->dropColumn([
                'field_report_notes',
                'field_report_additional_fee',
                'field_report_tools_materials',
                'field_report_submitted_at',
                'manager_approved_additional_fee',
                'additional_fee_approved_by',
                'additional_fee_approved_at',
                'frontdesk_confirmed_complete',
                'frontdesk_confirmed_by',
                'frontdesk_confirmed_at',
                'manager_confirmed_complete',
                'manager_confirmed_by',
                'manager_confirmed_at',
            ]);
        });
    }
};