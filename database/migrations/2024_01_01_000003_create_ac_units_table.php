<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ac_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('masjid_id')->constrained()->onDelete('cascade');
            $table->enum('pk_type', ['1PK', '2PK', '5PK']);
            $table->string('brand');
            $table->integer('quantity');
            $table->date('last_service_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ac_units');
    }
};
