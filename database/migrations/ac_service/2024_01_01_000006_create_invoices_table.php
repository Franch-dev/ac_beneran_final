<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ac_service')->create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_order_id')->unique()->constrained()->onDelete('cascade');
            $table->string('invoice_number')->unique();
            $table->decimal('total_price', 12, 2)->default(0);
            $table->timestamp('payment_verified_at')->nullable();
            $table->enum('payment_method', ['cash', 'transfer', 'qris'])->nullable();
            $table->unsignedBigInteger('payment_verified_by')->nullable();
            $table->string('payment_verified_by_name')->nullable();
            $table->text('payment_notes')->nullable();
            $table->json('payment_metadata')->nullable();
            $table->timestamp('cash_confirmed_at')->nullable();
            $table->unsignedBigInteger('cash_confirmed_by')->nullable();
            $table->string('cash_confirmed_by_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('ac_service')->dropIfExists('invoices');
    }
};
