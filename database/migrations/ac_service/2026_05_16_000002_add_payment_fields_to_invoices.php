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

        if (! Schema::connection('ac_service')->hasColumn('invoices', 'payment_verified_at')) {
            Schema::connection('ac_service')->table('invoices', function (Blueprint $table) {
                $table->timestamp('payment_verified_at')->nullable()->after('total_price');
            });
        }

        Schema::connection('ac_service')->table('invoices', function (Blueprint $table) {
            if (! Schema::connection('ac_service')->hasColumn('invoices', 'payment_method')) {
                $table->enum('payment_method', ['cash', 'transfer', 'qris'])->nullable()->after('payment_verified_at');
            }

            if (! Schema::connection('ac_service')->hasColumn('invoices', 'payment_verified_by')) {
                $table->unsignedBigInteger('payment_verified_by')->nullable()->after('payment_method');
            }

            if (! Schema::connection('ac_service')->hasColumn('invoices', 'payment_verified_by_name')) {
                $table->string('payment_verified_by_name')->nullable()->after('payment_verified_by');
            }

            if (! Schema::connection('ac_service')->hasColumn('invoices', 'payment_notes')) {
                $table->text('payment_notes')->nullable()->after('payment_verified_by_name');
            }

            if (! Schema::connection('ac_service')->hasColumn('invoices', 'payment_metadata')) {
                $table->json('payment_metadata')->nullable()->after('payment_notes');
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
                Schema::connection('ac_service')->hasColumn('invoices', 'payment_method') ? 'payment_method' : null,
                Schema::connection('ac_service')->hasColumn('invoices', 'payment_verified_by') ? 'payment_verified_by' : null,
                Schema::connection('ac_service')->hasColumn('invoices', 'payment_verified_by_name') ? 'payment_verified_by_name' : null,
                Schema::connection('ac_service')->hasColumn('invoices', 'payment_notes') ? 'payment_notes' : null,
                Schema::connection('ac_service')->hasColumn('invoices', 'payment_metadata') ? 'payment_metadata' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
