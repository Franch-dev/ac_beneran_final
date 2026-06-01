<?php

namespace Database\Factories;

use App\Models\ServiceOrder;
use App\Models\TechnicianAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TechnicianAssignmentFactory extends Factory
{
    protected $model = TechnicianAssignment::class;

    public function definition(): array
    {
        $technician = User::factory()->create(['role' => 'technician']);
        $assigner = User::factory()->create(['role' => 'manager']);

        return [
            'service_order_id' => ServiceOrder::factory(),
            'technician_id' => $technician->id,
            'technician_name' => $technician->name,
            'assigned_by' => $assigner->id,
            'assigned_by_name' => $assigner->name,
            'status' => 'assigned',
            'assigned_at' => now(),
        ];
    }
}
