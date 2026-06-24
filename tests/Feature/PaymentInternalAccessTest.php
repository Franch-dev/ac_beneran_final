<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Masjid;
use App\Models\PhotoProof;
use App\Models\Receipt;
use App\Models\ServiceDetail;
use App\Models\ServiceOrder;
use App\Models\SyncEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentInternalAccessTest extends TestCase
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
    public function direct_access_to_internal_payment_page_is_forbidden_without_signed_entry(): void
    {
        $order = $this->makePayableOrder();

        $this->actingAs($this->frontdesk)
            ->get(route('payments.internal.show', $order))
            ->assertForbidden();
    }

    /** @test */
    public function signed_entry_link_is_single_use_and_redirects_into_session_gated_page(): void
    {
        $order = $this->makePayableOrder();

        $entryUrl = $this->actingAs($this->frontdesk)
            ->postJson(route('payments.internal.access-link', $order))
            ->assertOk()
            ->json('url');

        $this->get($entryUrl)
            ->assertRedirect(route('payments.internal.show', $order));

        $this->get(route('payments.internal.show', $order))
            ->assertOk()
            ->assertSee('Pembayaran Internal');

        $this->get($entryUrl)->assertForbidden();
    }

    /** @test */
    public function technician_needs_proof_photo_before_internal_payment_access_is_granted(): void
    {
        $blockedOrder = $this->makePayableOrder($this->technician);
        $allowedOrder = $this->makePayableOrder($this->technician, withProof: true);

        $this->actingAs($this->technician)
            ->postJson(route('payments.internal.access-link', $blockedOrder))
            ->assertForbidden();

        $entryUrl = $this->postJson(route('payments.internal.access-link', $allowedOrder))
            ->assertOk()
            ->json('url');

        $this->get($entryUrl)
            ->assertRedirect(route('payments.internal.show', $allowedOrder));

        $this->get(route('payments.internal.show', $allowedOrder))
            ->assertOk();
    }

    /** @test */
    public function technician_with_payment_access_can_only_record_cash_payment(): void
    {
        $order = $this->makePayableOrder($this->technician, withProof: true);

        $entryUrl = $this->actingAs($this->technician)
            ->postJson(route('payments.internal.access-link', $order))
            ->assertOk()
            ->json('url');

        $this->get($entryUrl)
            ->assertRedirect(route('payments.internal.show', $order));

        $this->get(route('payments.internal.show', $order))
            ->assertOk()
            ->assertSee('verifyCash('.$order->id, false)
            ->assertDontSee('verifyTransfer('.$order->id, false)
            ->assertDontSee('verifyQris('.$order->id, false);

        $this->postJson(route('payments.verify-cash', $order))
            ->assertOk()
            ->assertJsonFragment(['success' => true]);

        $this->postJson(route('payments.verify-transfer', $order))
            ->assertForbidden();

        $order->refresh();
        $this->assertSame('payment_verified', $order->status);
        $this->assertSame('cash', $order->invoice->fresh()->payment_method);
    }

    /** @test */
    public function qris_verification_creates_receipt_updates_status_and_emits_sync_event(): void
    {
        config()->set('payments.qris.payload', '00020101021126580012ID.CO.QRIS.WWW01189360091400000000000208WARUNGAR5204599953033605802ID5911WARUNG ARIF6006BEKASI6105123456304A13A');
        config()->set('payments.qris.merchant_name', 'WARUNG ARIF');

        $order = $this->makePayableOrder();

        $entryUrl = $this->actingAs($this->manager)
            ->postJson(route('payments.internal.access-link', $order))
            ->assertOk()
            ->json('url');

        $this->get($entryUrl)
            ->assertRedirect(route('payments.internal.show', $order));

        $this
            ->postJson(route('payments.verify-qris', $order), [
                'qris_reference' => 'CLIENT-SHOULD-NOT-WIN',
            ])
            ->assertOk()
            ->assertJsonFragment(['success' => true]);

        $order->refresh();
        $order->load('invoice', 'receipt');

        $this->assertSame('payment_verified', $order->status);
        $this->assertNotNull($order->invoice->payment_verified_at);
        $this->assertSame('qris', $order->invoice->payment_method);
        $this->assertInstanceOf(Receipt::class, $order->receipt);
        $this->assertStringStartsWith('QRIS-', $order->receipt->qris_reference);
        $this->assertNotSame('CLIENT-SHOULD-NOT-WIN', $order->receipt->qris_reference);

        $this->assertDatabaseHas('sync_events', [
            'type' => 'service_order.payment_verified',
            'service_order_id' => $order->id,
        ], 'ac_service');
    }

    /** @test */
    public function transfer_verification_uses_server_configured_account_and_reference(): void
    {
        config()->set('payments.transfer.bank_name', 'BCA');
        config()->set('payments.transfer.account_number', '1234567890');
        config()->set('payments.transfer.account_name', 'Forkis Service');

        $order = $this->makePayableOrder();

        $entryUrl = $this->actingAs($this->manager)
            ->postJson(route('payments.internal.access-link', $order))
            ->assertOk()
            ->json('url');

        $this->get($entryUrl)
            ->assertRedirect(route('payments.internal.show', $order));

        $this
            ->postJson(route('payments.verify-transfer', $order), [
                'transfer_amount' => 1,
                'bank_name' => 'CLIENT-BANK',
                'reference_number' => 'CLIENT-REFERENCE',
            ])
            ->assertOk()
            ->assertJsonFragment(['success' => true]);

        $order->refresh();
        $order->load('invoice', 'receipt');

        $this->assertSame('payment_verified', $order->status);
        $this->assertSame('transfer', $order->invoice->payment_method);
        $this->assertInstanceOf(Receipt::class, $order->receipt);
        $this->assertSame('BCA', $order->receipt->transfer_bank);
        $this->assertStringStartsWith('TRF-', $order->receipt->transfer_reference);
        $this->assertNotSame('CLIENT-REFERENCE', $order->receipt->transfer_reference);
    }

    private function makePayableOrder(?User $technician = null, bool $withProof = false): ServiceOrder
    {
        $masjid = Masjid::factory()->create(['type' => 'Masjid']);

        $order = ServiceOrder::factory()->create([
            'masjid_id' => $masjid->id,
            'status' => 'waiting_payment',
        ]);

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

        if ($technician) {
            $assignment = $order->technicianAssignment()->create([
                'technician_id' => $technician->id,
                'technician_name' => $technician->name,
                'assigned_by' => $this->manager->id,
                'assigned_by_name' => $this->manager->name,
                'status' => 'done',
                'assigned_at' => now()->subHour(),
                'completed_at' => now()->subMinutes(10),
            ]);

            if ($withProof) {
                PhotoProof::create([
                    'service_order_id' => $order->id,
                    'technician_assignment_id' => $assignment->id,
                    'file_path' => 'proofs/test.webp',
                    'file_name' => 'test.webp',
                    'file_size' => 1024,
                    'mime_type' => 'image/webp',
                    'taken_at' => now(),
                    'created_by' => $technician->id,
                ]);
            }
        }

        return $order->fresh(['invoice', 'technicianAssignment']);
    }
}
