<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::connection('ac_service')->hasTable('anggota_service_orders')) {
            return;
        }

        Schema::connection('ac_service')->create('anggota_service_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anggota_id')->constrained('anggotas')->onDelete('cascade');
            $table->string('anggota_custom_id')->nullable()->index()->comment('Member code from anggotas');
            $table->string('order_number')->unique();
            $table->string('meeting_person');
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
        Schema::connection('ac_service')->dropIfExists('anggota_service_orders');
    }
};
