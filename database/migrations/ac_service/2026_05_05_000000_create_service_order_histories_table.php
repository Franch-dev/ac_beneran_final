<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::connection('ac_service')->create('service_order_histories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('service_order_id');
            $table->timestamp('archived_at')->nullable();
            $table->string('summary')->nullable();
            $table->json('order_snapshot');
            $table->unsignedBigInteger('archived_by_id')->nullable();
            $table->timestamps();

            $table->foreign('service_order_id')->references('id')->on('service_orders')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::connection('ac_service')->dropIfExists('service_order_histories');
    }
};
