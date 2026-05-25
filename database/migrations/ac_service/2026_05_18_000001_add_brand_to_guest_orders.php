<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ac_service')->table('guest_orders', function (Blueprint $table) {
            $table->string('brand')->default('-')->after('ac_type');
        });
    }

    public function down(): void
    {
        Schema::connection('ac_service')->table('guest_orders', function (Blueprint $table) {
            $table->dropColumn('brand');
        });
    }
};
