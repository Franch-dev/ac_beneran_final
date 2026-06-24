<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::connection('ac_service')->hasTable('invoices')) {
            return;
        }

        Schema::connection('ac_service')->table('invoices', function (Blueprint $table) {
            if (! Schema::connection('ac_service')->hasColumn('invoices', 'cash_confirmed_at')) {
                $table->timestamp('cash_confirmed_at')->nullable()->after('payment_metadata');
            }

            if (! Schema::connection('ac_service')->hasColumn('invoices', 'cash_confirmed_by')) {
                $table->unsignedBigInteger('cash_confirmed_by')->nullable()->after('cash_confirmed_at');
            }

            if (! Schema::connection('ac_service')->hasColumn('invoices', 'cash_confirmed_by_name')) {
                $table->string('cash_confirmed_by_name')->nullable()->after('cash_confirmed_by');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::connection('ac_service')->hasTable('invoices')) {
            return;
        }

        Schema::connection('ac_service')->table('invoices', function (Blueprint $table) {
            $columns = array_values(array_filter([
                Schema::connection('ac_service')->hasColumn('invoices', 'cash_confirmed_at') ? 'cash_confirmed_at' : null,
                Schema::connection('ac_service')->hasColumn('invoices', 'cash_confirmed_by') ? 'cash_confirmed_by' : null,
                Schema::connection('ac_service')->hasColumn('invoices', 'cash_confirmed_by_name') ? 'cash_confirmed_by_name' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
