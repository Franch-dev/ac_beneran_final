<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Masjid;
use App\Models\PhotoProof;
use App\Models\ServiceDetail;
use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class MonitoringPaymentEntryWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $frontdesk;

    private User $manager;

    private User $technician;

    private User $otherTechnician;

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

        $this->frontdesk = User::factory()->create(['role' => 'frontdesk']);
        $this->manager = User::factory()->create(['role' => 'manager']);
        $this->technician = User::factory()->create(['role' => 'technician']);
        $this->otherTechnician = User::factory()->create(['role' => 'technician']);
    }

    public function test_frontdesk_and_manager_open_payment_from_signed_monitoring_entry(): void
    {
        $order = $this->makePayableOrder();

        foreach ([$this->frontdesk, $this->manager] as $user) {
            $entryUrl = $this->actingAs($user)
                ->withHeader('referer', route('monitoring'))
                ->postJson(route('payments.internal.access-link', $order))
                ->assertOk()
                ->json('url');

            $this->get($entryUrl)
                ->assertRedirect(route('payments.internal.show', $order));

            $this->get(route('payments.internal.show', $order))
                ->assertOk()
                ->assertSee('Pembayaran Internal');

            $this->get(route('payments.internal.show', $order))
                ->assertForbidden();
        }
    }

    public function test_direct_missing_nonce_and_expired_payment_entry_urls_are_blocked(): void
    {
        $order = $this->makePayableOrder();

        $this->actingAs($this->manager)
            ->get(route('payments.internal.show', $order))
            ->assertForbidden();

        $this->get(route('payments.internal.entry', ['order' => $order, 'nonce' => 'unsigned']))
            ->assertForbidden();

        $this->get(URL::signedRoute('payments.internal.entry', ['order' => $order->id, 'nonce' => 'missing']))
            ->assertForbidden();

        $entryUrl = $this->postJson(route('payments.internal.access-link', $order))
            ->assertOk()
            ->json('url');

        try {
            $this->travel(6)->minutes();
            $this->get($entryUrl)->assertForbidden();
        } finally {
            $this->travelBack();
        }
    }

    public function test_assigned_technician_needs_own_proof_photo_before_payment_access(): void
    {
        $noProofOrder = $this->makePayableOrder($this->technician);
        $wrongProofOrder = $this->makePayableOrder($this->technician, proofCreator: $this->otherTechnician);
        $validProofOrder = $this->makePayableOrder($this->technician, proofCreator: $this->technician);

        $this->actingAs($this->technician)
            ->postJson(route('payments.internal.access-link', $noProofOrder))
            ->assertForbidden();

        $this->postJson(route('payments.internal.access-link', $wrongProofOrder))
            ->assertForbidden();

        $entryUrl = $this->postJson(route('payments.internal.access-link', $validProofOrder))
            ->assertOk()
            ->json('url');

        $this->get($entryUrl)
            ->assertRedirect(route('payments.internal.show', $validProofOrder));

        $this->get(route('payments.internal.show', $validProofOrder))
            ->assertOk();
    }

    public function test_manager_can_continue_after_invoice_payment_verification(): void
    {
        $order = $this->makeWaitingReviewOrder($this->technician);

        $this->actingAs($this->manager)
            ->postJson(route('service-order.finalize-order', $order))
            ->assertOk();

        $this->assertSame('waiting_payment', $order->fresh()->status);

        $entryUrl = $this->postJson(route('payments.internal.access-link', $order))
            ->assertOk()
            ->json('url');

        $this->get($entryUrl)
            ->assertRedirect(route('payments.internal.show', $order));

        $this->postJson(route('payments.verify-cash', $order))
            ->assertOk()
            ->assertJsonPath('success', true);

        $order->refresh();
        $this->assertSame('payment_verified', $order->status);
        $this->assertNotNull($order->invoice->fresh()->payment_verified_at);

        $this->postJson(route('service-order.finalize-order', $order))
            ->assertOk();

        $this->assertSame('completed', $order->fresh()->status);
        $this->assertDatabaseHas('workflow_steps', [
            'service_order_id' => $order->id,
            'step' => 'completed',
        ], 'ac_service');
    }

    private function makePayableOrder(?User $technician = null, ?User $proofCreator = null): ServiceOrder
    {
        $order = $this->makeOrder('waiting_payment');

        if ($technician) {
            $assignment = $order->technicianAssignment()->create([
                'technician_id' => $technician->id,
                'technician_name' => $technician->name,
                'assigned_by' => $this->manager->id,
                'assigned_by_name' => $this->manager->name,
                'status' => 'done',
                'assigned_at' => now()->subHours(3),
                'started_at' => now()->subHours(2),
                'completed_at' => now()->subHour(),
            ]);

            if ($proofCreator) {
                $this->createProof($order, $assignment->id, $proofCreator);
            }
        }

        return $order->fresh(['invoice', 'technicianAssignment']);
    }

    private function makeWaitingReviewOrder(User $technician): ServiceOrder
    {
        $order = $this->makeOrder('waiting_review', [
            'field_report_notes' => 'Pekerjaan selesai tanpa biaya tambahan.',
            'field_report_additional_fee' => 0,
            'field_report_submitted_at' => now()->subMinutes(30),
            'manager_approved_additional_fee' => false,
        ]);

        $assignment = $order->technicianAssignment()->create([
            'technician_id' => $technician->id,
            'technician_name' => $technician->name,
            'assigned_by' => $this->manager->id,
            'assigned_by_name' => $this->manager->name,
            'status' => 'done',
            'assigned_at' => now()->subHours(3),
            'started_at' => now()->subHours(2),
            'completed_at' => now()->subHour(),
            'completion_notes' => 'Semua unit selesai diservis.',
        ]);

        $this->createProof($order, $assignment->id, $technician);

        return $order->fresh(['invoice', 'technicianAssignment']);
    }

    private function makeOrder(string $status, array $attributes = []): ServiceOrder
    {
        $masjid = Masjid::factory()->create(['type' => 'Masjid']);

        $order = ServiceOrder::factory()->create(array_merge([
            'masjid_id' => $masjid->id,
            'status' => $status,
        ], $attributes));

        ServiceDetail::create([
            'service_order_id' => $order->id,
            'pk_type' => '1PK',
            'brand' => 'Daikin',
            'description' => 'Servis cuci AC',
            'quantity' => 1,
            'price_per_unit' => 150000,
        ]);

        $order->invoice()->create([
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'total_price' => 150000,
        ]);

        return $order->fresh(['invoice']);
    }

    private function createProof(ServiceOrder $order, int $assignmentId, User $creator): void
    {
        PhotoProof::create([
            'service_order_id' => $order->id,
            'technician_assignment_id' => $assignmentId,
            'file_path' => 'proofs/test.webp',
            'file_name' => 'test.webp',
            'file_size' => 1024,
            'mime_type' => 'image/webp',
            'taken_at' => now(),
            'created_by' => $creator->id,
        ]);
    }
}
