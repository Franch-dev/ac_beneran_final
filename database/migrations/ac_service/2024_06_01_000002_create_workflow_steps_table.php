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
                'guest_created',
                'frontdesk_created',
                'approved',
                'spk_invoice_created',
                'spk_invoice_approved',
                'assigned',
                'in_progress',
                'technician_reported',
                'invoice_editing',
                'fee_review',
                'invoice_edited',
                'waiting_payment',
                'payment_verified',
                'printed',
                'waiting_review',
                'completed',
                'closed',
                'cancelled',
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
