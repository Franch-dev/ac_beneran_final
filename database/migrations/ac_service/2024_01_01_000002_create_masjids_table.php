<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::connection('ac_service')->hasTable('masjids')) {
            Schema::connection('ac_service')->create('masjids', function (Blueprint $table) {
                $table->id();
                $table->string('custom_id')->unique();
                $table->enum('type', ['masjid', 'musholla']);
                $table->string('name');
                $table->text('address');
                $table->string('dkm_name');
                $table->string('marbot_name');
                $table->json('phone_numbers');
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('ac_service')->dropIfExists('masjids');
    }
};
