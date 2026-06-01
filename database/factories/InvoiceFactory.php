<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\ServiceOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'service_order_id' => ServiceOrder::factory(),
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'total_price' => $this->faker->numberBetween(100000, 500000),
        ];
    }
}
