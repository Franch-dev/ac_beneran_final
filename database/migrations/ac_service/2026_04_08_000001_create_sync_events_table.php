<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ac_service')->create('sync_events', function (Blueprint $table) {
            $table->id();
            $table->string('type', 120);
            $table->string('resource', 80)->nullable();
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->unsignedBigInteger('masjid_id')->nullable();
            $table->unsignedBigInteger('service_order_id')->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name')->nullable();
            $table->string('actor_role', 50)->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['type', 'created_at']);
            $table->index('masjid_id');
            $table->index('service_order_id');
        });
    }

    public function down(): void
    {
        Schema::connection('ac_service')->dropIfExists('sync_events');
    }
};
