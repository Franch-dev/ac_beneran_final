<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ac_service')->create('receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_order_id')->constrained('service_orders')->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('receipt_number', 30)->unique(); // REC-YYYYMMDD-NNN
            $table->enum('payment_method', ['cash', 'transfer', 'qris']);
            $table->decimal('payment_amount', 12, 2);
            $table->date('payment_date');
            $table->string('transfer_bank')->nullable();
            $table->string('transfer_reference')->nullable();
            $table->string('qris_reference')->nullable();
            $table->unsignedBigInteger('verified_by'); // user_id from main DB
            $table->string('verified_by_name');
            $table->string('digital_signature_path')->nullable();
            $table->string('printed_name');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('service_order_id');
            $table->index('invoice_id');
            $table->index('payment_date');
        });
    }

    public function down(): void
    {
        Schema::connection('ac_service')->dropIfExists('receipts');
    }
};
