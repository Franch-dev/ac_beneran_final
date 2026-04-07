<?php

namespace Tests\Feature;

use App\Models\ServiceOrder;
use App\Models\User;
use App\Models\WorkflowStep;
use App\Models\TechnicianAssignment;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BackendWorkflowSmokeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->configureMysqlConnections();
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
            ->assertSee('page-operations--dashboard')
            ->assertSee('pagination-shell');

        $this->actingAs($frontdesk)
            ->get('/monitoring')
            ->assertOk()
            ->assertSee('page-operations--monitoring')
            ->assertSee('monitoring-mobile-list')
            ->assertSee('pagination-shell');
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

    private function configureMysqlConnections(): void
    {
        config([
            'database.default' => 'main',
            'database.connections.main' => [
                'driver' => 'mysql',
                'host' => env('MAIN_DB_HOST', '127.0.0.1'),
                'port' => env('MAIN_DB_PORT', '3306'),
                'database' => env('MAIN_DB_DATABASE', 'main_platform'),
                'username' => env('MAIN_DB_USERNAME', 'root'),
                'password' => env('MAIN_DB_PASSWORD', ''),
                'unix_socket' => env('MAIN_DB_SOCKET', ''),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'prefix_indexes' => true,
                'strict' => true,
                'engine' => null,
            ],
            'database.connections.ac_service' => [
                'driver' => 'mysql',
                'host' => env('AC_SERVICE_DB_HOST', '127.0.0.1'),
                'port' => env('AC_SERVICE_DB_PORT', '3306'),
                'database' => env('AC_SERVICE_DB_DATABASE', 'ac_masjid_db'),
                'username' => env('AC_SERVICE_DB_USERNAME', 'root'),
                'password' => env('AC_SERVICE_DB_PASSWORD', ''),
                'unix_socket' => env('AC_SERVICE_DB_SOCKET', ''),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'prefix_indexes' => true,
                'strict' => true,
                'engine' => null,
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
