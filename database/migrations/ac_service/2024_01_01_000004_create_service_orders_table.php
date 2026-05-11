<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ac_service')->create('service_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('masjid_id')->constrained()->onDelete('cascade');
            $table->string('order_number')->unique();
            $table->enum('meeting_person', ['dkm', 'marbot']);
            $table->string('phone');
            $table->date('service_date');
            $table->text('notes')->nullable();
            $table->enum('status', [
                'pending',
                'approved',
                'in_progress',
                'waiting_invoice',
                'waiting_review',
                'completed',
                'cancelled',
            ])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('ac_service')->dropIfExists('service_orders');
    }
};
