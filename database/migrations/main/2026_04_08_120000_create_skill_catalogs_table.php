<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('main')->create('skill_catalogs', function (Blueprint $table) {
            $table->id();
            $table->string('path')->unique();
            $table->string('checksum', 64);
            $table->unsignedBigInteger('size_bytes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('main')->dropIfExists('skill_catalogs');
    }
};

