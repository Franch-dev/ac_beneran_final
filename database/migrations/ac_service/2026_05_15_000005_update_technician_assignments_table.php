<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::connection('ac_service')->hasTable('technician_assignments')) {
            return;
        }

        Schema::connection('ac_service')->table('technician_assignments', function (Blueprint $table) {
            // photo_proof_required, completion_notes, completed_at already exist
            if (! Schema::connection('ac_service')->hasColumn('technician_assignments', 'fee_reported')) {
                $table->boolean('fee_reported')->default(false)->after('completed_at');
            }
            if (! Schema::connection('ac_service')->hasColumn('technician_assignments', 'fee_amount')) {
                $table->decimal('fee_amount', 12, 2)->nullable()->after('fee_reported');
            }
            if (! Schema::connection('ac_service')->hasColumn('technician_assignments', 'fee_description')) {
                $table->text('fee_description')->nullable()->after('fee_amount');
            }
            if (! Schema::connection('ac_service')->hasColumn('technician_assignments', 'fee_tools_materials')) {
                $table->text('fee_tools_materials')->nullable()->after('fee_description');
            }
            if (! Schema::connection('ac_service')->hasColumn('technician_assignments', 'fee_reported_at')) {
                $table->timestamp('fee_reported_at')->nullable()->after('fee_tools_materials');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('ac_service')->table('technician_assignments', function (Blueprint $table) {
            $table->dropColumn([
                'fee_reported',
                'fee_amount',
                'fee_description',
                'fee_tools_materials',
                'fee_reported_at',
            ]);
        });
    }
};
