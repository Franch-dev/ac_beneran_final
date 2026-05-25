<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ac_service')->create('workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_order_id')->constrained('service_orders')->cascadeOnDelete();
            $table->enum('step', [
                'created',
                'guest_created',
                'frontdesk_created',
                'spk_invoice_created',
                'spk_invoice_approved',
                'approved',
                'assigned',
                'in_progress',
                'technician_reported',
                'waiting_payment',
                'payment_verified',
                'waiting_review',
                'completed',
                'invoice_generated',
                'edited_invoice_created',
                'edited_invoice_approved',
                'invoice_edited',
                'payment_received',
                'printed',
                'closed',
                'cancelled'
            ]);
            $table->unsignedBigInteger('actor_id')->nullable(); // user id from main DB
            $table->string('actor_name')->nullable();           // denormalized for display
            $table->string('actor_role')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('ac_service')->dropIfExists('workflow_steps');
    }
};
