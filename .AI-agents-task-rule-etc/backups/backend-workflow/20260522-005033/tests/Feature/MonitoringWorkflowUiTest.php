<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Masjid;
use App\Models\ServiceDetail;
use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringWorkflowUiTest extends TestCase
{
    use RefreshDatabase;

    protected User $frontdesk;
    protected User $manager;

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
    }

    /** @test */
    public function frontdesk_and_manager_see_the_correct_buttons_on_the_main_monitoring_page(): void
    {
        $approvedOrder = $this->makeOrder('approved');
        $spkCreatedOrder = $this->makeOrder('spk_invoice_created', withInvoice: true);
        $spkApprovedOrder = $this->makeOrder('spk_invoice_approved', withInvoice: true);
        $waitingPaymentOrder = $this->makeOrder('waiting_payment', withInvoice: true);

        $frontdeskResponse = $this->actingAs($this->frontdesk)->get(route('monitoring'));
        $frontdeskResponse->assertOk();
        $frontdeskResponse->assertSee('onclick="createSpkInvoice('.$approvedOrder->id, false);
        $frontdeskResponse->assertDontSee('onclick="createSpkInvoice('.$spkCreatedOrder->id, false);
        $frontdeskResponse->assertDontSee('onclick="approveOrder('.$approvedOrder->id, false);
        $frontdeskResponse->assertDontSee('onclick="approveSpkInvoice('.$spkCreatedOrder->id, false);
        $frontdeskResponse->assertDontSee('openAssignTech('.$spkApprovedOrder->id, false);
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
        $waitingReviewOrder = $this->makeOrder('waiting_review', fieldReport: true);
        $paymentVerifiedOrder = $this->makeOrder('payment_verified', withInvoice: true);

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
