<?php

namespace Tests\Feature;

use App\Models\AcUnit;
use App\Models\Masjid;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceOrderArchiveDashboardE2ETest extends TestCase
{
    use RefreshDatabase;

    protected User $frontdesk;
    protected User $manager;
    protected User $technician;
    protected User $viewer;

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
        $this->viewer = User::factory()->create(['role' => 'viewer']);
    }

    /** @test */
    public function it_runs_the_full_order_to_archive_flow_and_updates_dashboard_state(): void
    {
        $masjid = Masjid::create([
            'custom_id' => Masjid::generateCustomId('masjid'),
            'type' => 'masjid',
            'name' => 'Masjid E2E Dashboard',
            'address' => 'Jl. End To End 1',
            'dkm_name' => 'DKM E2E',
            'marbot_name' => 'Marbot E2E',
            'phone_numbers' => ['081234567890'],
            'setup_status' => 'completed',
            'setup_completed_at' => now(),
        ]);

        $unit = AcUnit::create([
            'masjid_id' => $masjid->id,
            'pk_type' => '1PK',
            'brand' => 'Daikin',
            'quantity' => 1,
            'last_service_date' => now()->subDays(150)->toDateString(),
        ]);

        $serviceDate = now()->toDateString();

        $this->actingAs($this->frontdesk)
            ->postJson(route('service-order.store'), [
                'masjid_id' => $masjid->id,
                'meeting_person' => 'dkm',
                'phone' => '081234567890',
                'service_date' => $serviceDate,
                'notes' => 'E2E archive test',
                'details' => [
                    ['pk_type' => '1PK', 'brand' => 'Daikin', 'quantity' => 1],
                ],
            ])
            ->assertOk();

        $order = ServiceOrder::query()->latest('id')->firstOrFail();
        $this->assertSame('pending_review', $order->status);

        $this->actingAs($this->manager)
            ->postJson(route('service-order.approve', $order))
            ->assertOk();

        $order->refresh();
        $this->assertSame('approved', $order->status);

        $this->actingAs($this->frontdesk)
            ->postJson(route('workflow.create-spk-invoice', $order))
            ->assertOk();

        $order->refresh();
        $this->assertSame('spk_invoice_created', $order->status);
        $this->assertNotNull($order->invoice);

        $this->actingAs($this->manager)
            ->postJson('/workflow/'.$order->id.'/approve-spk-invoice')
            ->assertOk();

        $order->refresh();
        $this->assertSame('spk_invoice_approved', $order->status);

        $this->actingAs($this->manager)
            ->postJson(route('workflow.assign', $order), [
                'technician_id' => $this->technician->id,
            ])
            ->assertOk();

        $order->refresh();
        $this->assertSame('technician_assigned', $order->status);

        $this->actingAs($this->technician)
            ->postJson(route('workflow.progress', $order), [
                'status' => 'in_progress',
            ])
            ->assertOk();

        $order->refresh();
        $this->assertSame('in_progress', $order->status);

        $this->actingAs($this->technician)
            ->postJson(route('service-order.field-report', $order), [
                'field_report_notes' => 'Semua unit selesai diservis.',
                'field_report_additional_fee' => 0,
            ])
            ->assertOk();

        $order->refresh();
        $this->assertSame('waiting_review', $order->status);

        $this->actingAs($this->manager)
            ->postJson(route('service-order.finalize-order', $order))
            ->assertOk();

        $order->refresh();
        $this->assertSame('waiting_payment', $order->status);

        $this->actingAs($this->manager)
            ->postJson(route('service-order.confirm-payment', $order))
            ->assertOk();

        $order->refresh();
        $this->assertSame('payment_verified', $order->status);

        $this->actingAs($this->manager)
            ->postJson(route('service-order.finalize-order', $order))
            ->assertOk();

        $order->refresh();
        $unit->refresh();

        $this->assertSame('completed', $order->status);
        $this->assertSame($serviceDate, $unit->last_service_date?->toDateString());
        $this->assertSame('aman', $masjid->fresh()->urgency_status);

        $this->actingAs($this->manager)
            ->postJson('/modules/ac-masjid-musholla/service-order/'.$order->id.'/archive')
            ->assertOk()
            ->assertJsonPath('archived', true);

        $order->refresh();
        $this->assertNotNull($order->archived_at);
        $this->assertFalse($order->isActive());

        $this->assertDatabaseHas('service_order_histories', [
            'service_order_id' => $order->id,
        ], 'ac_service');

        $this->assertSame(1, ServiceOrderHistory::query()->where('service_order_id', $order->id)->count());

        $this->actingAs($this->manager)
            ->get(route('monitoring'))
            ->assertOk()
            ->assertDontSee($order->order_number);

        $this->actingAs($this->frontdesk)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee($order->order_number)
            ->assertSee('Belum ada service order aktif')
            ->assertSee('Aman');

        $this->actingAs($this->viewer)
            ->get(route('viewer.dashboard'))
            ->assertOk()
            ->assertDontSee($order->order_number)
            ->assertDontSee('Masjid E2E Dashboard');

        $this->assertSame(0, ServiceOrder::query()->active()->whereKey($order->id)->count());
    }
}
