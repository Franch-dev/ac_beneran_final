<?php

namespace Database\Factories;

use App\Models\ServiceOrder;
use App\Models\ServiceOrderHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceOrderHistoryFactory extends Factory
{
    protected $model = ServiceOrderHistory::class;

    public function definition(): array
    {
        return [
            'service_order_id' => ServiceOrder::factory(),
            'archived_at' => now(),
            'summary' => 'Archived to service history',
            'order_snapshot' => ['status' => 'completed', 'masjid_name' => $this->faker->words(2, true)],
            'archived_by_id' => 1,
        ];
    }
}
