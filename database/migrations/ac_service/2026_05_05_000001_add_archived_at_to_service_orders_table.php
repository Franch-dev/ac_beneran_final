<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::connection('ac_service')->table('service_orders', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('created_at');
        });
    }

    public function down()
    {
        Schema::connection('ac_service')->table('service_orders', function (Blueprint $table) {
            $table->dropColumn('archived_at');
        });
    }
};
