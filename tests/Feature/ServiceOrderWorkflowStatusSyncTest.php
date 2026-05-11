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
            'status' => 'spk_invoice_created',
        ]);

        $this->actingAs($user);

        $controller = new ServiceOrderController();
        $response = $controller->approve($serviceOrder);

        $serviceOrder->refresh();

        $this->assertSame('approved', $serviceOrder->status);
        $this->assertDatabaseHas('workflow_steps', [
            'service_order_id' => $serviceOrder->id,
            'step' => 'spk_invoice_approved',
        ]);

        $this->assertSame('approved', $response->getData()->status);
    }
}
