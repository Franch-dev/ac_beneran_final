<?php

namespace Tests\Feature;

use App\Models\Masjid;
use App\Models\ServiceDetail;
use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowIntegrationTest extends TestCase
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

    protected function setUp(): void
    {
        parent::setUp();

        // Create Admin, Manager, Frontdesk, and Technician
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->manager = User::factory()->create(['role' => 'manager']);
        $this->frontdesk = User::factory()->create(['role' => 'frontdesk']);
        $this->technician = User::factory()->create(['role' => 'technician']);

        // Create a Masjid
        $this->masjid = Masjid::factory()->create(['type' => 'Masjid']);
    }

    /** @test */
    public function it_can_complete_a_standard_workflow_happy_path()
    {
        // 1. Create Service Order (Frontdesk)
        $this->actingAs($this->frontdesk);
        $response = $this->postJson(route('service-order.store'), [
            'masjid_id' => $this->masjid->id,
            'meeting_person' => 'dkm',
            'phone' => '08123456789',
            'service_date' => now()->addDay()->toDateString(),
            'details' => [
                ['pk_type' => '1PK', 'brand' => 'Daikin', 'quantity' => 2]
            ]
        ]);

        $response->assertStatus(200);
        $order = ServiceOrder::latest('id')->first();
        $this->assertEquals('pending_review', $order->status);

        // 2. Approve Order (Manager)
        $this->actingAs($this->manager);
        $response = $this->postJson(route('service-order.approve', $order));
        $response->assertStatus(200);
        $order->refresh();
        $this->assertEquals('approved', $order->status);

        // 3. Frontdesk creates SPK & Invoice
        $this->actingAs($this->frontdesk);
        $response = $this->postJson(route('workflow.create-spk-invoice', $order));
        $response->assertStatus(200);
        $order->refresh();
        $this->assertEquals('spk_invoice_created', $order->status);
        $this->assertNotNull($order->invoice);

        // 4. Manager approves SPK & Invoice
        $this->actingAs($this->manager);
        $response = $this->postJson('/workflow/'.$order->id.'/approve-spk-invoice');
        $response->assertStatus(200);
        $order->refresh();
        $this->assertEquals('spk_invoice_approved', $order->status);

        // 5. Assign Technician (Manager)
        $this->actingAs($this->manager);
        $response = $this->postJson(route('workflow.assign', $order), [
            'technician_id' => $this->technician->id
        ]);
        $response->assertStatus(200);
        $order->refresh();
        $this->assertEquals('technician_assigned', $order->status);

        // 6. Technician starts work
        $this->actingAs($this->technician);
        $response = $this->postJson(route('workflow.progress', $order), [
            'status' => 'in_progress',
        ]);
        $response->assertStatus(200);
        $order->refresh();
        $this->assertEquals('in_progress', $order->status);

        // 7. Submit Field Report (Technician)
        $response = $this->postJson(route('service-order.field-report', $order), [
            'field_report_notes' => 'Service completed successfully.',
            'field_report_additional_fee' => 0
        ]);
        $response->assertStatus(200);
        $order->refresh();
        $this->assertEquals('waiting_review', $order->status);

        // 8. Finalize work into payment (Manager)
        $this->actingAs($this->manager);
        $response = $this->postJson(route('service-order.finalize-order', $order));
        $response->assertStatus(200);
        $order->refresh();
        $this->assertEquals('waiting_payment', $order->status);

        // 9. Confirm Payment (Manager)
        $this->actingAs($this->manager);
        $response = $this->postJson(route('service-order.confirm-payment', $order));
        $response->assertStatus(200);
        $order->refresh();
        $this->assertEquals('payment_verified', $order->status);

        // 10. Complete order after payment (Manager)
        $this->actingAs($this->manager);
        $response = $this->postJson(route('service-order.finalize-order', $order));
        $response->assertStatus(200);
        $order->refresh();
        $this->assertEquals('completed', $order->status);
    }

    /** @test */
    public function it_blocks_technician_assignment_before_spk_invoice_approval()
    {
        // Create Order and Approve
        $order = ServiceOrder::factory()->create([
            'masjid_id' => $this->masjid->id,
            'status' => 'approved'
        ]);

        // Try to assign technician before payment
        $this->actingAs($this->manager);
        $response = $this->postJson(route('workflow.assign', $order), [
            'technician_id' => $this->technician->id
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'Order harus diverifikasi pembayarannya sebelum bisa ditugaskan.']);
    }

    /** @test */
    public function manager_cannot_create_spk_invoice(): void
    {
        $order = ServiceOrder::factory()->create([
            'masjid_id' => $this->masjid->id,
            'status' => 'approved',
        ]);

        ServiceDetail::create([
            'service_order_id' => $order->id,
            'pk_type' => '1PK',
            'brand' => 'Daikin',
            'quantity' => 1,
            'price_per_unit' => 150000,
        ]);

        $this->actingAs($this->manager);
        $response = $this->postJson(route('workflow.create-spk-invoice', $order));

        $response->assertStatus(403);
    }

    /** @test */
    public function it_blocks_payment_before_spk_invoice_is_approved(): void
    {
        $order = ServiceOrder::factory()->create([
            'masjid_id' => $this->masjid->id,
            'status' => 'spk_invoice_created',
        ]);

        ServiceDetail::create([
            'service_order_id' => $order->id,
            'pk_type' => '1PK',
            'brand' => 'Daikin',
            'quantity' => 1,
            'price_per_unit' => 150000,
        ]);

        $order->invoice()->create([
            'invoice_number' => 'INV-TEST-001',
            'total_price' => 150000,
        ]);

        $this->actingAs($this->manager);
        $response = $this->postJson(route('service-order.confirm-payment', $order));

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'Order tidak menunggu pembayaran.']);
    }

    /** @test */
    public function it_blocks_finalize_before_field_report_exists(): void
    {
        $order = ServiceOrder::factory()->create([
            'masjid_id' => $this->masjid->id,
            'status' => 'technician_assigned',
            'field_report_notes' => null,
            'field_report_additional_fee' => null,
        ]);

        $this->actingAs($this->manager);
        $response = $this->postJson(route('service-order.finalize-order', $order));

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'Order belum siap untuk diproses ke tahap berikutnya.']);
    }

    /** @test */
    public function it_requires_additional_fee_approval_before_payment()
    {
        // Setup order in progress
        $order = ServiceOrder::factory()->create([
            'masjid_id' => $this->masjid->id,
            'status' => 'in_progress'
        ]);
        $order->technicianAssignment()->create([
            'technician_id' => $this->technician->id,
            'technician_name' => $this->technician->name,
            'assigned_by' => $this->manager->id,
            'assigned_by_name' => $this->manager->name,
            'status' => 'assigned'
        ]);

        // Technician submits report with additional fee
        $this->actingAs($this->technician);
        $this->postJson(route('service-order.field-report', $order), [
            'field_report_notes' => 'Found broken parts.',
            'field_report_additional_fee' => 50000
        ]);

        $order->refresh();
        $this->assertEquals('waiting_review', $order->status);

        // Manager approves additional fee
        $this->actingAs($this->manager);
        $response = $this->postJson(route('service-order.approve-additional-fee', $order));
        $response->assertStatus(200);
        $order->refresh();
        $this->assertEquals('waiting_payment', $order->status);
    }
}
