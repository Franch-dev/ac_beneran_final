<?php

namespace Tests\Feature;

use App\Models\Masjid;
use App\Models\ServiceDetail;
use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
        Storage::fake('local');

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

        // 7. Technician completes with photo proof
        $response = $this->postJson(route('technician.orders.complete', $order), [
            'photos' => [UploadedFile::fake()->image('proof.jpg')],
            'completion_notes' => 'Service completed successfully.',
            'has_fees' => false,
        ]);
        $response->assertStatus(200);
        $order->refresh();
        $this->assertEquals('waiting_review', $order->status);

        // 8. Finalize work (Manager)
        $this->actingAs($this->manager);
        $response = $this->postJson(route('service-order.finalize-order', $order));
        $response->assertStatus(200);
        $order->refresh();
        $this->assertEquals('waiting_payment', $order->status);

        // 9. Confirm payment
        $this->actingAs($this->manager);
        $response = $this->postJson(route('service-order.confirm-payment', $order));
        $response->assertStatus(200);
        $order->refresh();
        $this->assertEquals('payment_verified', $order->status);

        // 10. Complete and close with dual confirmation
        $response = $this->postJson(route('service-order.finalize-order', $order));
        $response->assertStatus(200);
        $order->refresh();
        $this->assertEquals('completed', $order->status);

        $this->actingAs($this->frontdesk)->postJson(route('service-order.frontdesk-confirm-complete', $order))->assertOk();
        $this->actingAs($this->manager)->postJson(route('service-order.manager-confirm-complete', $order))->assertOk();
        $order->refresh();
        $this->assertEquals('closed', $order->status);
        $this->assertNotNull($order->archived_at);
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
        $response->assertJsonFragment(['message' => 'Order hanya bisa ditugaskan setelah SPK & Invoice disetujui.']);
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
        $response->assertJsonFragment(['message' => 'Order tidak dalam status menunggu pembayaran.']);
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
        $response->assertJsonFragment(['message' => 'Order harus berstatus pembayaran terverifikasi.']);
    }

    /** @test */
    public function it_requires_additional_fee_approval_before_payment()
    {
        Storage::fake('local');

        // Setup order in progress
        $order = ServiceOrder::factory()->create([
            'masjid_id' => $this->masjid->id,
            'status' => 'in_progress'
        ]);
        $order->invoice()->create([
            'invoice_number' => 'INV-TEST-FEE-001',
            'total_price' => 150000,
        ]);
        $order->technicianAssignment()->create([
            'technician_id' => $this->technician->id,
            'technician_name' => $this->technician->name,
            'assigned_by' => $this->manager->id,
            'assigned_by_name' => $this->manager->name,
            'status' => 'assigned'
        ]);

        $this->actingAs($this->technician);
        $this->postJson(route('technician.orders.complete', $order), [
            'photos' => [UploadedFile::fake()->image('proof.jpg')],
            'completion_notes' => 'Found broken parts.',
            'has_fees' => true,
            'fee_description' => 'Broken part',
            'fee_amount' => 50000,
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

    /** @test */
    public function it_blocks_finalize_when_additional_fee_is_not_yet_approved(): void
    {
        $order = ServiceOrder::factory()->create([
            'masjid_id' => $this->masjid->id,
            'status' => 'waiting_review',
            'field_report_notes' => 'Butuh penggantian part.',
            'field_report_additional_fee' => 25000,
            'manager_approved_additional_fee' => false,
        ]);

        $this->actingAs($this->manager);
        $response = $this->postJson(route('service-order.finalize-order', $order));

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'Biaya tambahan harus disetujui atau diedit sebelum finalisasi pembayaran.']);
    }
}
