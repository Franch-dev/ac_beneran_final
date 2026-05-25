<?php

namespace Database\Factories;

use App\Models\Masjid;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Masjid>
 */
class MasjidFactory extends Factory
{
    protected $model = Masjid::class;

    public function definition(): array
    {
        return [
            'custom_id' => Masjid::generateCustomId('masjid'),
            'type' => 'masjid',
            'name' => 'Masjid '.$this->faker->unique()->words(2, true),
            'address' => $this->faker->address(),
            'dkm_name' => $this->faker->name(),
            'marbot_name' => $this->faker->name(),
            'phone_numbers' => ['081234567890'],
            'setup_status' => 'pending_ac',
        ];
    }
}
