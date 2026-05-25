<?php

namespace Database\Factories;

use App\Models\Masjid;
use App\Models\ServiceOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServiceOrder>
 */
class ServiceOrderFactory extends Factory
{
    protected $model = ServiceOrder::class;

    public function definition(): array
    {
        return [
            'masjid_id' => Masjid::factory(),
            'order_number' => ServiceOrder::generateOrderNumber(),
            'meeting_person' => 'dkm',
            'phone' => '081234567890',
            'service_date' => now()->addDay()->toDateString(),
            'notes' => $this->faker->sentence(),
            'status' => 'spk_invoice_created',
        ];
    }
}
