<?php

namespace Database\Factories;

use App\Models\GuestOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

class GuestOrderFactory extends Factory
{
    protected $model = GuestOrder::class;

    public function definition(): array
    {
        return [
            'guest_name' => $this->faker->name(),
            'guest_phone' => '08' . $this->faker->numerify('##########'),
            'masjid_name' => 'Masjid ' . $this->faker->words(2, true),
            'address' => $this->faker->address(),
            'ac_type' => $this->faker->randomElement(['1PK', '2PK', '5PK']),
            'ac_amount' => $this->faker->numberBetween(1, 3),
            'brand' => $this->faker->randomElement(['Daikin', 'Panasonic', 'LG']),
            'problem_description' => $this->faker->sentence(),
            'status' => 'pending_review',
        ];
    }
}
