<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ac_service')->table('invoices', function (Blueprint $table) {
            $table->timestamp('cash_confirmed_at')->nullable()->after('payment_metadata');
            $table->unsignedBigInteger('cash_confirmed_by')->nullable()->after('cash_confirmed_at');
            $table->string('cash_confirmed_by_name')->nullable()->after('cash_confirmed_by');
        });
    }

    public function down(): void
    {
        Schema::connection('ac_service')->table('invoices', function (Blueprint $table) {
            $table->dropColumn(['cash_confirmed_at', 'cash_confirmed_by', 'cash_confirmed_by_name']);
        });
    }
};
