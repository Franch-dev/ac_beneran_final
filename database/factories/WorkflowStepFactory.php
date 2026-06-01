<?php

namespace Database\Factories;

use App\Models\ServiceOrder;
use App\Models\WorkflowStep;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkflowStepFactory extends Factory
{
    protected $model = WorkflowStep::class;

    public function definition(): array
    {
        return [
            'service_order_id' => ServiceOrder::factory(),
            'step' => 'frontdesk_created',
            'actor_id' => 1,
            'actor_name' => $this->faker->name(),
            'actor_role' => 'frontdesk',
            'notes' => $this->faker->sentence(),
        ];
    }
}
