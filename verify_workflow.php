<?php

use App\Models\Masjid;
use App\Models\ServiceOrder;
use App\Models\User;
use App\Models\ServiceDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\ServiceOrderController;
use App\Http\Controllers\WorkflowController;
use Illuminate\Http\Request;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

function log_status($msg) {
    echo "[VERIFY] $msg\n";
}

try {
    DB::beginTransaction();

    log_status("Starting Verification Script...");

    // Setup Users manually
    $suffix = uniqid();
    $admin = User::create(['name' => 'Admin', 'email' => "admin_$suffix@test.com", 'password' => Hash::make('password'), 'role' => 'admin']);
    $manager = User::create(['name' => 'Manager', 'email' => "manager_$suffix@test.com", 'password' => Hash::make('password'), 'role' => 'manager']);
    $frontdesk = User::create(['name' => 'Frontdesk', 'email' => "fd_$suffix@test.com", 'password' => Hash::make('password'), 'role' => 'frontdesk']);
    $technician = User::create(['name' => 'Technician', 'email' => "tech_$suffix@test.com", 'password' => Hash::make('password'), 'role' => 'technician']);

    $masjid = Masjid::create([
        'custom_id' => '001-'.uniqid(),
        'type' => 'Masjid',
        'name' => 'Masjid Test '.uniqid(),
        'address' => 'Jl. Test',
        'dkm_name' => 'DKM Test',
        'marbot_name' => 'Marbot Test',
        'phone_numbers' => ['08123456789'],
    ]);

    // 1. Create Order
    log_status("1. Creating Service Order...");
    auth()->login($frontdesk);
    $order = ServiceOrder::create([
        'masjid_id' => $masjid->id,
        'order_number' => ServiceOrder::generateOrderNumber(),
        'meeting_person' => 'dkm',
        'phone' => '08123456789',
        'service_date' => now()->addDay(),
        'status' => 'spk_invoice_created',
    ]);
    ServiceDetail::create([
        'service_order_id' => $order->id,
        'pk_type' => '1PK',
        'brand' => 'Daikin',
        'quantity' => 1,
        'price_per_unit' => 100000
    ]);
    log_status("Order created: {$order->order_number}, Status: {$order->status}");

    // 2. Approve (Manager)
    log_status("2. Approving Order...");
    auth()->login($manager);
    $controller = app(ServiceOrderController::class);
    $controller->approve($order);
    $order->refresh();
    log_status("Status after approve: {$order->status}");
    if ($order->status !== 'approved') throw new Exception("Expected status approved, got {$order->status}");

    // 3. Generate Invoice (Frontdesk)
    log_status("3. Generating Invoice...");
    auth()->login($frontdesk);
    $workflowController = app(WorkflowController::class);
    $workflowController->createSpkInvoice(new Request(), $order);
    $order->refresh();
    log_status("Status after invoice: {$order->status}");
    if ($order->status !== 'waiting_payment') throw new Exception("Expected status waiting_payment, got {$order->status}");

    // 4. Confirm Payment (Manager)
    log_status("4. Confirming Payment...");
    auth()->login($manager);
    $controller->confirmPayment($order);
    $order->refresh();
    log_status("Status after payment: {$order->status}");
    if ($order->status !== 'payment_verified') throw new Exception("Expected status payment_verified, got {$order->status}");

    // 5. Try to assign technician
    log_status("5. Assigning Technician...");
    $workflowController->assign(new Request(['technician_id' => $technician->id]), $order);
    $order->refresh();
    log_status("Status after assignment: {$order->status}");
    if ($order->status !== 'in_progress') throw new Exception("Expected status in_progress, got {$order->status}");

    // 6. Technician Submit Report
    log_status("6. Submitting Field Report...");
    auth()->login($technician);
    $controller->submitFieldReport(new Request([
        'field_report_notes' => 'Done',
        'field_report_additional_fee' => 0
    ]), $order);
    $order->refresh();
    log_status("Status after report: {$order->status}");
    if ($order->status !== 'waiting_review') throw new Exception("Expected status waiting_review, got {$order->status}");

    // 7. Finalize
    log_status("7. Finalizing Order...");
    auth()->login($manager);
    $controller->finalizeOrder($order);
    $order->refresh();
    log_status("Status after finalize: {$order->status}");
    if ($order->status !== 'completed') throw new Exception("Expected status completed, got {$order->status}");

    log_status("SUCCESS: All transitions verified!");
    
    DB::rollBack();
} catch (Exception $e) {
    DB::rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
    // echo $e->getTraceAsString() . "\n";
    exit(1);
}
