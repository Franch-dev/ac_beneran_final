<?php

namespace Tests\Feature;

use App\Http\Controllers\ServiceOrderController;
use App\Models\Invoice;
use App\Models\Masjid;
use App\Models\ServiceDetail;
use App\Models\ServiceOrder;
use App\Models\User;
use App\Models\WorkflowStep;
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

    public function test_approve_spk_invoice_moves_order_to_waiting_payment_and_records_steps(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $frontdesk = User::factory()->create(['role' => 'frontdesk']);
        $masjid = Masjid::factory()->create(['type' => 'Masjid']);

        $serviceOrder = ServiceOrder::create([
            'masjid_id' => $masjid->id,
            'order_number' => ServiceOrder::generateOrderNumber(),
            'meeting_person' => 'dkm',
            'phone' => '081234567890',
            'service_date' => now()->addDays(3)->toDateString(),
            'notes' => 'Test service order',
            'status' => 'approved',
        ]);

        ServiceDetail::create([
            'service_order_id' => $serviceOrder->id,
            'pk_type' => '1PK',
            'brand' => 'Daikin',
            'quantity' => 1,
            'price_per_unit' => 150000,
        ]);

        $this->actingAs($frontdesk)
            ->postJson(route('workflow.create-spk-invoice', $serviceOrder))
            ->assertOk();

        $serviceOrder->refresh();
        $this->assertSame('spk_invoice_created', $serviceOrder->status);
        $this->assertInstanceOf(Invoice::class, $serviceOrder->invoice);

        $this->actingAs($manager)
            ->postJson('/workflow/'.$serviceOrder->id.'/approve-spk-invoice')
            ->assertOk();

        $serviceOrder->refresh();
        $this->assertSame('waiting_payment', $serviceOrder->status);

        $this->assertDatabaseHas('workflow_steps', [
            'service_order_id' => $serviceOrder->id,
            'step' => 'spk_invoice_approved',
        ], 'ac_service');

        $this->assertDatabaseHas('workflow_steps', [
            'service_order_id' => $serviceOrder->id,
            'step' => 'waiting_payment',
        ], 'ac_service');
    }

    public function test_workflow_step_latest_matches_waiting_review_after_technician_done(): void
    {
        $manager = User::factory()->create(['role' => 'manager']);
        $technician = User::factory()->create(['role' => 'technician']);
        $masjid = Masjid::factory()->create(['type' => 'Masjid']);

        $serviceOrder = ServiceOrder::factory()->create([
            'masjid_id' => $masjid->id,
            'status' => 'payment_verified',
        ]);

        $this->actingAs($manager)
            ->postJson(route('workflow.assign', $serviceOrder), [
                'technician_id' => $technician->id,
            ])
            ->assertOk();

        $serviceOrder->refresh();
        $this->assertSame('technician_assigned', $serviceOrder->status);

        $this->actingAs($technician)
            ->postJson(route('workflow.progress', $serviceOrder), [
                'status' => 'in_progress',
                'notes' => 'Mulai pengerjaan',
            ])
            ->assertOk();

        $this->actingAs($technician)
            ->postJson(route('workflow.progress', $serviceOrder), [
                'status' => 'done',
                'notes' => 'Pekerjaan selesai',
            ])
            ->assertOk();

        $serviceOrder->refresh();
        $this->assertSame('waiting_review', $serviceOrder->status);

        $latestStep = WorkflowStep::query()
            ->where('service_order_id', $serviceOrder->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($latestStep);
        $this->assertSame('waiting_review', $latestStep->step);
    }
}
