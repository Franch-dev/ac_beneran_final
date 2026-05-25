<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ac_service')->create('technician_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_order_id')->unique()->constrained('service_orders')->cascadeOnDelete();
            $table->unsignedBigInteger('technician_id');    // user id from main DB
            $table->string('technician_name');              // denormalized
            $table->unsignedBigInteger('assigned_by');      // manager user id
            $table->string('assigned_by_name');             // denormalized
            $table->enum('status', ['assigned', 'in_progress', 'done'])->default('assigned');
            $table->text('technician_notes')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('completion_notes')->nullable();
            $table->boolean('fee_reported')->default(false);
            $table->decimal('fee_amount', 12, 2)->nullable();
            $table->text('fee_description')->nullable();
            $table->text('fee_tools_materials')->nullable();
            $table->timestamp('fee_reported_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('ac_service')->dropIfExists('technician_assignments');
    }
};
