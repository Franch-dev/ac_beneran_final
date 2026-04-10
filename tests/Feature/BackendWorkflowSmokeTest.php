<?php

namespace Tests\Feature;

use App\Models\AcAnggota;
use App\Models\AcUnit;
use App\Models\Anggota;
use App\Models\Masjid;
use App\Models\ServiceOrder;
use App\Models\TechnicianAssignment;
use App\Models\User;
use App\Models\WorkflowStep;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BackendWorkflowSmokeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->configureSqliteConnections();
        $this->refreshSeededDatabases();
    }

    public function test_monitoring_status_counts_endpoint_returns_seeded_counts(): void
    {
        $manager = User::where('role', 'manager')->firstOrFail();

        $this->actingAs($manager)
            ->getJson('/monitoring/status-counts')
            ->assertOk()
            ->assertJson([
                'pending' => 1,
                'approved' => 1,
                'waiting_invoice' => 1,
                'waiting_review' => 1,
                'completed' => 1,
            ]);
    }

    public function test_service_order_detail_endpoint_returns_audit_history(): void
    {
        $manager = User::where('role', 'manager')->firstOrFail();
        $order = ServiceOrder::where('status', 'waiting_review')->firstOrFail();

        $response = $this->actingAs($manager)->getJson("/service-order/{$order->id}");

        $response->assertOk()
            ->assertJsonPath('order.status', 'waiting_review');

        $this->assertNotEmpty($response->json('history'));
    }

    public function test_technician_can_mark_assigned_work_as_done(): void
    {
        $order = ServiceOrder::where('status', 'approved')->firstOrFail();
        $technician = User::where('email', 'teknisi@example.com')->firstOrFail();

        $this->actingAs($technician)
            ->postJson("/workflow/{$order->id}/progress", [
                'status' => 'done',
                'notes' => 'Smoke test completion',
            ])
            ->assertOk();

        $this->assertSame(
            'waiting_invoice',
            ServiceOrder::findOrFail($order->id)->status
        );
    }

    public function test_frontdesk_and_manager_can_complete_invoice_flow(): void
    {
        $frontdesk = User::where('role', 'frontdesk')->firstOrFail();
        $manager = User::where('role', 'manager')->firstOrFail();
        $waitingInvoice = ServiceOrder::where('status', 'waiting_invoice')->firstOrFail();
        $waitingReview = ServiceOrder::where('status', 'waiting_review')->firstOrFail();

        $this->actingAs($frontdesk)
            ->postJson("/service-order/{$waitingInvoice->id}/invoice")
            ->assertOk();

        $this->assertSame(
            'waiting_review',
            ServiceOrder::findOrFail($waitingInvoice->id)->status
        );

        $this->actingAs($manager)
            ->postJson("/service-order/{$waitingReview->id}/approve-invoice")
            ->assertOk();

        $this->assertSame(
            'completed',
            ServiceOrder::findOrFail($waitingReview->id)->status
        );
    }

    public function test_manager_can_view_reports_and_export_only_completed_orders(): void
    {
        $manager = User::where('role', 'manager')->firstOrFail();

        $this->actingAs($manager)
            ->get('/reports')
            ->assertOk()
            ->assertSee('Reports');

        $exportResponse = $this->actingAs($manager)->getJson('/reports/export');

        $exportResponse->assertOk();

        foreach ($exportResponse->json() as $order) {
            $this->assertSame(
                'completed',
                ServiceOrder::where('order_number', $order['order_number'])->value('status')
            );
            $this->assertGreaterThan(0, $order['total']);
        }
    }

    public function test_role_dashboards_and_history_page_render_successfully(): void
    {
        $manager = User::where('role', 'manager')->firstOrFail();
        $technician = User::where('email', 'teknisi@example.com')->firstOrFail();
        $viewer = User::where('role', 'viewer')->firstOrFail();
        $masjidId = ServiceOrder::query()->value('masjid_id');
        $assignedOrder = ServiceOrder::where('status', 'approved')->firstOrFail();

        $this->actingAs($manager)
            ->get("/masjid/{$masjidId}/history-page")
            ->assertOk();

        $this->actingAs($technician)
            ->get('/technician')
            ->assertOk()
            ->assertSee('Technician Dashboard');

        $this->actingAs($technician)
            ->get("/technician/spk/{$assignedOrder->id}")
            ->assertOk()
            ->assertSee($assignedOrder->order_number);

        $this->actingAs($viewer)
            ->get('/viewer')
            ->assertOk()
            ->assertSee('Viewer Dashboard');
    }

    public function test_dashboard_and_monitoring_render_operations_shells(): void
    {
        $frontdesk = User::where('role', 'frontdesk')->firstOrFail();

        $this->actingAs($frontdesk)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('page-operations--dashboard');

        $this->actingAs($frontdesk)
            ->get('/monitoring')
            ->assertOk()
            ->assertSee('page-operations--monitoring')
            ->assertSee('monitoring-mobile-list');
    }

    public function test_ac_modules_render_isolated_domain_data(): void
    {
        $frontdesk = User::where('role', 'frontdesk')->firstOrFail();

        $masjid = Masjid::create([
            'custom_id' => '998-0001',
            'type' => 'masjid',
            'name' => 'Masjid Domain Isolasi',
            'address' => 'Jl. Masjid Isolasi',
            'dkm_name' => 'DKM Isolasi',
            'marbot_name' => 'Marbot Isolasi',
            'phone_numbers' => ['08111111111'],
            'setup_status' => 'completed',
        ]);

        AcUnit::create([
            'masjid_id' => $masjid->id,
            'pk_type' => '1PK',
            'brand' => 'MasjidBrand',
            'quantity' => 1,
            'last_service_date' => now()->toDateString(),
        ]);

        $anggota = Anggota::create([
            'custom_id' => 'AGT-0001',
            'type' => 'anggota',
            'name' => 'Anggota Domain Isolasi',
            'address' => 'Jl. Anggota Isolasi',
            'phone_numbers' => ['08222222222'],
        ]);

        AcAnggota::create([
            'anggota_id' => $anggota->id,
            'pk_type' => '2PK',
            'brand' => 'AnggotaBrand',
            'quantity' => 2,
            'last_service_date' => now()->toDateString(),
        ]);

        $this->actingAs($frontdesk)
            ->get('/modules/ac-masjid-musholla/monitoring')
            ->assertOk()
            ->assertSee('Masjid Domain Isolasi')
            ->assertDontSee('Anggota Domain Isolasi');

        $this->actingAs($frontdesk)
            ->get('/modules/ac-anggota/monitoring')
            ->assertOk()
            ->assertSee('Anggota Domain Isolasi')
            ->assertDontSee('Masjid Domain Isolasi');
    }

    public function test_outside_ac_anggota_pages_render_successfully(): void
    {
        $frontdesk = User::where('role', 'frontdesk')->firstOrFail();

        $this->get('/ac-anggota')
            ->assertOk()
            ->assertSee('AC Anggota');

        $this->actingAs($frontdesk)
            ->get('/ac-anggota/dashboard')
            ->assertOk()
            ->assertSee('Dashboard AC Anggota');

        $this->actingAs($frontdesk)
            ->get('/ac-anggota/monitoring')
            ->assertOk()
            ->assertSee('Monitoring unit AC');
    }

    public function test_deleting_service_order_cascades_workflow_and_assignment_rows(): void
    {
        $manager = User::where('role', 'manager')->firstOrFail();
        $order = ServiceOrder::where('status', 'approved')->firstOrFail();

        $this->assertGreaterThan(0, WorkflowStep::where('service_order_id', $order->id)->count());
        $this->assertGreaterThan(0, TechnicianAssignment::where('service_order_id', $order->id)->count());

        $this->actingAs($manager)
            ->deleteJson("/service-order/{$order->id}/manager")
            ->assertOk();

        $this->assertDatabaseMissing('service_orders', ['id' => $order->id], 'ac_service');
        $this->assertSame(0, WorkflowStep::where('service_order_id', $order->id)->count());
        $this->assertSame(0, TechnicianAssignment::where('service_order_id', $order->id)->count());
    }

    public function test_frontdesk_onboarding_keeps_masjid_pending_until_ac_units_are_saved(): void
    {
        $frontdesk = User::where('role', 'frontdesk')->firstOrFail();

        $createResponse = $this->actingAs($frontdesk)
            ->postJson('/masjid', [
                'type' => 'masjid',
                'name' => 'Masjid Sinkron Baru',
                'address' => 'Jl. Sinkronisasi No. 1',
                'dkm_name' => 'DKM Sinkron',
                'marbot_name' => 'Marbot Sinkron',
                'phone_numbers' => ['081234567890'],
            ]);

        $createResponse->assertOk()
            ->assertJsonPath('masjid.setup_status', 'pending_ac');

        $masjidId = $createResponse->json('masjid.id');

        $this->assertDatabaseHas('masjids', [
            'id' => $masjidId,
            'setup_status' => 'pending_ac',
        ], 'ac_service');

        $this->assertDatabaseHas('sync_events', [
            'type' => 'masjid.created',
            'masjid_id' => $masjidId,
        ], 'ac_service');

        $acResponse = $this->actingAs($frontdesk)
            ->postJson('/ac/bulk', [
                'masjid_id' => $masjidId,
                'units' => [[
                    'pk_type' => '1PK',
                    'brand' => 'LG',
                    'quantity' => 2,
                    'last_service_date' => now()->toDateString(),
                ]],
            ]);

        $acResponse->assertOk()
            ->assertJsonPath('masjid.setup_status', 'completed');

        $this->assertDatabaseHas('masjids', [
            'id' => $masjidId,
            'setup_status' => 'completed',
        ], 'ac_service');

        $this->assertDatabaseHas('sync_events', [
            'type' => 'ac.bulk_saved',
            'masjid_id' => $masjidId,
        ], 'ac_service');
    }

    public function test_snapshot_endpoints_render_sync_roots_for_operational_dashboards(): void
    {
        $frontdesk = User::where('role', 'frontdesk')->firstOrFail();
        $technician = User::where('email', 'teknisi@example.com')->firstOrFail();
        $viewer = User::where('role', 'viewer')->firstOrFail();

        $dashboardSnapshot = $this->actingAs($frontdesk)->getJson('/dashboard/snapshot');
        $dashboardSnapshot->assertOk();
        $this->assertStringContainsString('dashboardSyncRoot', (string) $dashboardSnapshot->json('html'));

        $monitoringSnapshot = $this->actingAs($frontdesk)->getJson('/monitoring/snapshot');
        $monitoringSnapshot->assertOk();
        $this->assertStringContainsString('monitoringSyncRoot', (string) $monitoringSnapshot->json('html'));

        $technicianSnapshot = $this->actingAs($technician)->getJson('/technician/snapshot');
        $technicianSnapshot->assertOk();
        $this->assertStringContainsString('technicianSyncRoot', (string) $technicianSnapshot->json('html'));

        $viewerSnapshot = $this->actingAs($viewer)->getJson('/viewer/snapshot');
        $viewerSnapshot->assertOk();
        $this->assertStringContainsString('viewerSyncRoot', (string) $viewerSnapshot->json('html'));
    }

    public function test_service_order_and_workflow_writes_emit_sync_events(): void
    {
        $manager = User::where('role', 'manager')->firstOrFail();
        $technician = User::where('email', 'teknisi@example.com')->firstOrFail();
        $pendingOrder = ServiceOrder::where('status', 'pending')->firstOrFail();
        $approvedOrder = ServiceOrder::where('status', 'approved')->firstOrFail();

        $this->actingAs($manager)
            ->postJson("/service-order/{$pendingOrder->id}/approve")
            ->assertOk();

        $this->assertDatabaseHas('sync_events', [
            'type' => 'service_order.approved',
            'service_order_id' => $pendingOrder->id,
        ], 'ac_service');

        $this->actingAs($manager)
            ->postJson("/workflow/{$approvedOrder->id}/assign", [
                'technician_id' => $technician->id,
                'notes' => 'Assign for sync test',
            ])
            ->assertOk();

        $this->assertDatabaseHas('sync_events', [
            'type' => 'workflow.assigned',
            'service_order_id' => $approvedOrder->id,
        ], 'ac_service');

        $this->actingAs($technician)
            ->postJson("/workflow/{$approvedOrder->id}/progress", [
                'status' => 'done',
                'notes' => 'Sync test done',
            ])
            ->assertOk();

        $this->assertDatabaseHas('sync_events', [
            'type' => 'workflow.progress_updated',
            'service_order_id' => $approvedOrder->id,
        ], 'ac_service');
    }

    private function configureSqliteConnections(): void
    {
        $basePath = database_path('testing');
        if (! is_dir($basePath)) {
            mkdir($basePath, 0777, true);
        }

        $mainDatabase = $basePath . DIRECTORY_SEPARATOR . 'main.sqlite';
        $acServiceDatabase = $basePath . DIRECTORY_SEPARATOR . 'ac_service.sqlite';

        if (! file_exists($mainDatabase)) {
            touch($mainDatabase);
        }

        if (! file_exists($acServiceDatabase)) {
            touch($acServiceDatabase);
        }

        config([
            'database.default' => 'main',
            'database.connections.main' => [
                'driver' => 'sqlite',
                'database' => $mainDatabase,
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
            'database.connections.ac_service' => [
                'driver' => 'sqlite',
                'database' => $acServiceDatabase,
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);

        DB::purge('main');
        DB::purge('ac_service');
    }

    private function refreshSeededDatabases(): void
    {
        Artisan::call('db:wipe', ['--database' => 'ac_service', '--force' => true]);
        Artisan::call('db:wipe', ['--database' => 'main', '--force' => true]);
        Artisan::call('migrate', ['--force' => true]);
        Artisan::call('db:seed', ['--force' => true]);
    }
}
