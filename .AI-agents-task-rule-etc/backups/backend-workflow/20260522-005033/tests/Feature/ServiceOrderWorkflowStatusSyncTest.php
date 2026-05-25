<?php

namespace Tests\Feature;

use App\Http\Controllers\ServiceOrderController;
use App\Models\Masjid;
use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceOrderWorkflowStatusSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function migrateFreshUsing()
    {
        return array_merge(parent::migrateFreshUsing(), [
            '--path' => [
                'database/migrations',
                'database/migrations/main',
                'database/migrations/ac_service',
                'database/migrations/ac_anggota',
            ],
        ]);
    }

    public function test_approve_sets_order_status_to_approved_and_workflow_step_matches(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $masjid = Masjid::create([
            'name' => 'Masjid Test',
            'address' => 'Jl. Contoh',
            'dkm_name' => 'DKM Test',
            'marbot_name' => 'Marbot Test',
            'phone_numbers' => ['081234567890'],
        ]);

        $serviceOrder = ServiceOrder::create([
            'masjid_id' => $masjid->id,
            'order_number' => ServiceOrder::generateOrderNumber(),
            'meeting_person' => 'dkm',
            'phone' => '081234567890',
            'service_date' => now()->addDays(3)->toDateString(),
            'notes' => 'Test service order',
            'status' => 'pending_review',
        ]);

        $this->actingAs($user);

        $controller = new ServiceOrderController();
        $response = $controller->approve($serviceOrder);

        $serviceOrder->refresh();

        $this->assertSame('approved', $serviceOrder->status);
        $this->assertDatabaseHas('workflow_steps', [
            'service_order_id' => $serviceOrder->id,
            'step' => 'approved',
        ], 'ac_service');

        $this->assertSame('approved', $response->getData(true)['status']);
    }
}
