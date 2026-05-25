<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ac_service')->create('masjid_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('masjid_id')->constrained('masjids')->cascadeOnDelete();
            $table->unsignedBigInteger('guest_order_id')->nullable();
            $table->enum('field', ['name', 'address', 'dkm_name', 'marbot_name', 'phone_numbers']);
            $table->text('old_value')->nullable();
            $table->text('new_value');
            $table->unsignedBigInteger('requested_by'); // frontdesk user_id
            $table->string('requested_by_name');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->string('reviewed_by_name')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'masjid_id']);
        });
    }

    public function down(): void
    {
        Schema::connection('ac_service')->dropIfExists('masjid_change_requests');
    }
};
