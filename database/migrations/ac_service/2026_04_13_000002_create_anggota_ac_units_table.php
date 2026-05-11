<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::connection('ac_service')->hasTable('anggota_ac_units')) {
            return;
        }

        Schema::connection('ac_service')->create('anggota_ac_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anggota_id')->constrained('anggotas')->onDelete('cascade');
            $table->string('anggota_custom_id')->nullable()->index()->comment('Member code from anggotas');
            $table->enum('pk_type', ['1PK', '2PK', '5PK']);
            $table->string('brand');
            $table->integer('quantity');
            $table->date('last_service_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('ac_service')->dropIfExists('anggota_ac_units');
    }
};
