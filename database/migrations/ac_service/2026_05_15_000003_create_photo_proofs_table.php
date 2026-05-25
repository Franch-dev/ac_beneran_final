<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ac_service')->create('photo_proofs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_order_id')->constrained('service_orders')->cascadeOnDelete();
            $table->foreignId('technician_assignment_id')->nullable()->constrained('technician_assignments')->nullOnDelete();
            $table->string('file_path');
            $table->string('file_name');
            $table->unsignedBigInteger('file_size'); // bytes
            $table->string('mime_type'); // image/jpeg, image/png, image/webp
            $table->text('description')->nullable();
            $table->timestamp('taken_at')->nullable();
            $table->unsignedBigInteger('created_by'); // user_id from main DB
            $table->timestamps();

            $table->index('service_order_id');
            $table->index('technician_assignment_id');
        });
    }

    public function down(): void
    {
        Schema::connection('ac_service')->dropIfExists('photo_proofs');
    }
};
