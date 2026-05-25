<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ac_service')->create('guest_orders', function (Blueprint $table) {
            $table->id();
            $table->string('guest_name');
            $table->string('guest_phone');
            $table->foreignId('masjid_id')->nullable()->constrained('masjids')->nullOnDelete();
            $table->string('masjid_name')->nullable(); // freeform if masjid not in DB
            $table->text('address')->nullable();
            $table->enum('ac_type', ['1PK', '2PK', '5PK']);
            $table->unsignedInteger('ac_amount')->default(1);
            $table->text('problem_description');
            $table->enum('status', ['pending_review', 'approved', 'rejected', 'cancelled'])->default('pending_review');
            $table->text('rejection_reason')->nullable();
            $table->text('additional_phone_description')->nullable(); // new number or replacement note
            $table->string('ip_address')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('guest_phone');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::connection('ac_service')->dropIfExists('guest_orders');
    }
};
