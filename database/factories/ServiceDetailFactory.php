<?php

namespace Database\Factories;

use App\Models\ServiceDetail;
use App\Models\ServiceOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceDetailFactory extends Factory
{
    protected $model = ServiceDetail::class;

    public function definition(): array
    {
        return [
            'service_order_id' => ServiceOrder::factory(),
            'pk_type' => $this->faker->randomElement(['1PK', '2PK', '5PK']),
            'brand' => $this->faker->randomElement(['Daikin', 'Panasonic', 'LG', 'Samsung', 'Sharp']),
            'quantity' => $this->faker->numberBetween(1, 3),
            'price_per_unit' => $this->faker->randomElement([150000, 200000, 350000]),
        ];
    }
}
