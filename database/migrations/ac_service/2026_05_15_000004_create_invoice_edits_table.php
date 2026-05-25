<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ac_service')->create('invoice_edits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('service_order_id')->constrained('service_orders')->cascadeOnDelete();
            $table->unsignedBigInteger('edited_by'); // user_id from main DB
            $table->string('edited_by_name');
            $table->string('edited_by_role'); // frontdesk, manager
            $table->enum('edit_type', ['add_item', 'remove_item', 'update_price', 'update_quantity']);
            $table->json('old_value')->nullable(); // previous value (null for add_item)
            $table->json('new_value'); // new value
            $table->text('notes')->nullable();
            $table->timestamp('created_at'); // immutable - no updated_at

            $table->index('invoice_id');
            $table->index('service_order_id');
            $table->index('edited_by');
        });
    }

    public function down(): void
    {
        Schema::connection('ac_service')->dropIfExists('invoice_edits');
    }
};
