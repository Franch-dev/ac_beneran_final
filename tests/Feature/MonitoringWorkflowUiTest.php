<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Masjid;
use App\Models\PhotoProof;
use App\Models\ServiceDetail;
use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MonitoringWorkflowUiTest extends TestCase
{
    use RefreshDatabase;

    protected User $frontdesk;
    protected User $manager;
    protected User $technician;

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
    }

    /** @test */
    public function frontdesk_and_manager_see_the_correct_buttons_on_the_main_monitoring_page(): void
    {
        $approvedOrder = $this->makeOrder('approved');
        $spkCreatedOrder = $this->makeOrder('spk_invoice_created', withInvoice: true);
        $spkApprovedOrder = $this->makeOrder('spk_invoice_approved', withInvoice: true);
        $paymentVerifiedOrder = $this->makeOrder('payment_verified', withInvoice: true);
        $waitingPaymentOrder = $this->makeOrder('waiting_payment', withInvoice: true);

        $frontdeskResponse = $this->actingAs($this->frontdesk)->get(route('monitoring'));
        $frontdeskResponse->assertOk();
        $frontdeskResponse->assertSee('onclick="createSpkInvoice('.$approvedOrder->id, false);
        $frontdeskResponse->assertDontSee('onclick="createSpkInvoice('.$spkCreatedOrder->id, false);
        $frontdeskResponse->assertDontSee('onclick="approveOrder('.$approvedOrder->id, false);
        $frontdeskResponse->assertDontSee('onclick="approveSpkInvoice('.$spkCreatedOrder->id, false);
        $frontdeskResponse->assertSee('openAssignTech('.$spkApprovedOrder->id, false);
        $frontdeskResponse->assertDontSee('onclick="confirmPayment('.$waitingPaymentOrder->id, false);

        $managerResponse = $this->actingAs($this->manager)->get(route('monitoring'));
        $managerResponse->assertOk();
        $managerResponse->assertDontSee('onclick="createSpkInvoice('.$approvedOrder->id, false);
        $managerResponse->assertSee('onclick="approveSpkInvoice('.$spkCreatedOrder->id, false);
        $managerResponse->assertSee('openAssignTech('.$spkApprovedOrder->id, false);
        $managerResponse->assertSee('onclick="confirmPayment('.$waitingPaymentOrder->id, false);
    }

    /** @test */
    public function module_monitoring_page_respects_role_gates_and_finalize_rules(): void
    {
        $pendingReviewOrder = $this->makeOrder('pending_review');
        $approvedOrder = $this->makeOrder('approved');
        $spkCreatedOrder = $this->makeOrder('spk_invoice_created', withInvoice: true);
        $spkApprovedOrder = $this->makeOrder('spk_invoice_approved', withInvoice: true);
        $paymentVerifiedOrder = $this->makeOrder('payment_verified', withInvoice: true);
        $paymentVerifiedOrder->technicianAssignment()->create([
            'technician_id' => $this->manager->id,
            'technician_name' => $this->manager->name,
            'assigned_by' => $this->manager->id,
            'assigned_by_name' => $this->manager->name,
            'status' => 'done',
            'assigned_at' => now()->subHour(),
            'started_at' => now()->subMinutes(30),
            'completed_at' => now(),
        ]);
        $waitingReviewOrder = $this->makeOrder('waiting_review', fieldReport: true);

        $managerResponse = $this->actingAs($this->manager)->get(route('modules.ac-masjid-musholla.monitoring'));
        $managerResponse->assertOk();
        $managerResponse->assertSee('onclick="approveOrder('.$pendingReviewOrder->id.')"', false);
        $managerResponse->assertDontSee('onclick="createSpkInvoice('.$approvedOrder->id.')"', false);
        $managerResponse->assertSee('onclick="approveSpkInvoice('.$spkCreatedOrder->id.')"', false);
        $managerResponse->assertSee('openAssignTech('.$spkApprovedOrder->id, false);
        $managerResponse->assertSee('onclick="finalizeOrder('.$waitingReviewOrder->id, false);
        $managerResponse->assertSee('onclick="finalizeOrder('.$paymentVerifiedOrder->id, false);

        $frontdeskResponse = $this->actingAs($this->frontdesk)->get(route('modules.ac-masjid-musholla.monitoring'));
        $frontdeskResponse->assertOk();
        $frontdeskResponse->assertSee('onclick="createSpkInvoice('.$approvedOrder->id.')"', false);
        $frontdeskResponse->assertDontSee('onclick="approveOrder('.$pendingReviewOrder->id.')"', false);
        $frontdeskResponse->assertDontSee('onclick="approveSpkInvoice('.$spkCreatedOrder->id.')"', false);
    }

    /** @test */
    public function payment_chip_only_appears_for_roles_and_orders_that_pass_internal_access_rules(): void
    {
        $waitingPaymentOrder = $this->makeOrder('waiting_payment', withInvoice: true);
        $techReadyOrder = $this->makeOrder('waiting_payment', withInvoice: true);
        $techReadyOrder->technicianAssignment()->create([
            'technician_id' => $this->technician->id,
            'technician_name' => $this->technician->name,
            'assigned_by' => $this->manager->id,
            'assigned_by_name' => $this->manager->name,
            'status' => 'done',
            'assigned_at' => now()->subHour(),
            'completed_at' => now()->subMinutes(10),
        ]);
        PhotoProof::create([
            'service_order_id' => $techReadyOrder->id,
            'technician_assignment_id' => $techReadyOrder->technicianAssignment->id,
            'file_path' => 'proofs/test.webp',
            'file_name' => 'test.webp',
            'file_size' => 1000,
            'mime_type' => 'image/webp',
            'taken_at' => now(),
            'created_by' => $this->technician->id,
        ]);

        $techBlockedOrder = $this->makeOrder('waiting_payment', withInvoice: true);
        $techBlockedOrder->technicianAssignment()->create([
            'technician_id' => $this->technician->id,
            'technician_name' => $this->technician->name,
            'assigned_by' => $this->manager->id,
            'assigned_by_name' => $this->manager->name,
            'status' => 'done',
            'assigned_at' => now()->subHour(),
            'completed_at' => now()->subMinutes(10),
        ]);

        $frontdeskResponse = $this->actingAs($this->frontdesk)->get(route('monitoring'));
        $frontdeskResponse->assertOk();
        $frontdeskResponse->assertSee('data-payment-order-id="'.$waitingPaymentOrder->id.'"', false);

        $techResponse = $this->actingAs($this->technician)->get(route('monitoring'));
        $techResponse->assertOk();
        $techResponse->assertSee('data-payment-order-id="'.$techReadyOrder->id.'"', false);
        $techResponse->assertDontSee('data-payment-order-id="'.$techBlockedOrder->id.'"', false);
        $techResponse->assertDontSee('data-payment-order-id="'.$waitingPaymentOrder->id.'"', false);
    }

    /** @test */
    public function monitoring_status_counts_include_canonical_statuses_and_sidebar_aliases(): void
    {
        Cache::flush();
        foreach ([
            'photo_proofs',
            'workflow_steps',
            'technician_assignments',
            'receipts',
            'invoices',
            'service_details',
            'service_orders',
        ] as $table) {
            DB::connection('ac_service')->table($table)->delete();
        }

        $this->makeOrder('pending_review');
        $this->makeOrder('approved');
        $this->makeOrder('spk_invoice_created');
        $this->makeOrder('spk_invoice_approved');
        $this->makeOrder('invoice_editing');
        $this->makeOrder('fee_review');
        $this->makeOrder('waiting_review');
        $this->makeOrder('waiting_payment');

        $closed = $this->makeOrder('closed');
        $closed->update(['archived_at' => now()]);

        $response = $this->actingAs($this->manager)->getJson(route('monitoring.status-counts'));

        $response->assertOk()
            ->assertJsonPath('pending_review', 1)
            ->assertJsonPath('approved', 1)
            ->assertJsonPath('spk_invoice_created', 1)
            ->assertJsonPath('spk_invoice_approved', 1)
            ->assertJsonPath('invoice_editing', 1)
            ->assertJsonPath('fee_review', 1)
            ->assertJsonPath('waiting_review', 1)
            ->assertJsonPath('waiting_payment', 1)
            ->assertJsonPath('pending', 4)
            ->assertJsonPath('waiting_invoice', 3)
            ->assertJsonPath('invoice_queue', 3)
            ->assertJsonPath('review_queue', 1)
            ->assertJsonMissingPath('closed');
    }

    /** @test */
    public function logout_button_target_route_logs_the_user_out(): void
    {
        $response = $this->actingAs($this->manager)->post(route('logout'));

        $response->assertRedirect(route('home'));
        $this->assertGuest();
    }

    /** @test */
    public function manager_can_approve_pending_review_order_from_module_route(): void
    {
        $order = $this->makeOrder('pending_review');

        $response = $this->actingAs($this->manager)
            ->postJson('/modules/ac-masjid-musholla/service-order/'.$order->id.'/approve');

        $response->assertOk();

        $order->refresh();
        $this->assertSame('approved', $order->status);
    }

    /** @test */
    public function frontdesk_module_order_can_be_approved_by_manager_from_module_route(): void
    {
        $masjid = Masjid::factory()->create(['type' => 'Masjid']);

        $this->actingAs($this->frontdesk)
            ->postJson('/modules/ac-masjid-musholla/service-order', [
                'masjid_id' => $masjid->id,
                'meeting_person' => 'dkm',
                'phone' => '08123456789',
                'service_date' => now()->addDay()->toDateString(),
                'details' => [
                    ['pk_type' => '1PK', 'brand' => 'Daikin', 'quantity' => 1],
                ],
            ])
            ->assertOk();

        $order = ServiceOrder::query()->latest('id')->firstOrFail();
        $this->assertSame('pending_review', $order->status);

        $this->actingAs($this->manager)
            ->postJson('/modules/ac-masjid-musholla/service-order/'.$order->id.'/approve')
            ->assertOk();

        $order->refresh();
        $this->assertSame('approved', $order->status);
    }

    private function makeOrder(string $status, bool $withInvoice = false, bool $fieldReport = false): ServiceOrder
    {
        $masjid = Masjid::factory()->create(['type' => 'Masjid']);

        $order = ServiceOrder::factory()->create([
            'masjid_id' => $masjid->id,
            'status' => $status,
            'field_report_notes' => $fieldReport ? 'Laporan teknisi tersedia.' : null,
            'field_report_additional_fee' => $fieldReport ? 0 : null,
        ]);

        ServiceDetail::create([
            'service_order_id' => $order->id,
            'pk_type' => '1PK',
            'brand' => 'Daikin',
            'quantity' => 1,
            'price_per_unit' => 150000,
        ]);

        if ($withInvoice) {
            Invoice::create([
                'service_order_id' => $order->id,
                'invoice_number' => Invoice::generateInvoiceNumber(),
                'total_price' => 150000,
            ]);
        }

        return $order;
    }
}
