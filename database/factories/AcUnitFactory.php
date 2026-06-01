<?php

namespace Database\Factories;

use App\Models\AcUnit;
use App\Models\Masjid;
use Illuminate\Database\Eloquent\Factories\Factory;

class AcUnitFactory extends Factory
{
    protected $model = AcUnit::class;

    public function definition(): array
    {
        return [
            'masjid_id' => Masjid::factory(),
            'pk_type' => $this->faker->randomElement(['1PK', '2PK', '5PK']),
            'brand' => $this->faker->randomElement(['Daikin', 'Panasonic', 'LG', 'Samsung', 'Sharp']),
            'quantity' => $this->faker->numberBetween(1, 5),
            'last_service_date' => $this->faker->optional()->date(),
        ];
    }
}
