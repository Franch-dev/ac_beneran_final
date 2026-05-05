<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

echo "==============================================\n";
echo "   SERVICE ORDER CREATION TEST\n";
echo "==============================================\n\n";

// Find an admin user for testing
$testUser = null;
try {
    $testUser = \App\Models\User::first();
    echo "Using test user: " . $testUser->email . " (ID: " . $testUser->id . ")\n";
} catch (\Exception $e) {
    echo "ERROR: Could not find test user: " . $e->getMessage() . "\n";
    exit(1);
}

// Find a masjid for the order
$masjid = null;
try {
    $masjid = \App\Models\Masjid::first();
    if ($masjid) {
        echo "Using masjid: " . $masjid->name . " (ID: " . $masjid->id . ")\n";
    }
} catch (\Exception $e) {
    echo "WARNING: Could not find masjid: " . $e->getMessage() . "\n";
}

echo "\n----------------------------------------------\n";
echo "Testing POST /service-order\n";
echo "----------------------------------------------\n\n";

// Prepare test payload - matching exact validation rules
$payload = [
    'masjid_id' => $masjid ? $masjid->id : 1,
    'meeting_person' => 'dkm',
    'phone' => '081234567890',
    'service_date' => date('Y-m-d', strtotime('+7 days')),
    'notes' => 'Test service order from automated testing',
    'details' => [
        [
            'pk_type' => '1PK',
            'brand' => 'Panasonic',
            'quantity' => 1,
        ]
    ],
];

// For web routes with CSRF, we need to use without CSRF middleware
// Using Laravel's internal test method
echo "\n[INFO] Route is a WEB route (requires CSRF)\n";
echo "[INFO] Testing with full authentication context...\n\n";

// Directly call the controller method to bypass CSRF
try {
    echo "=== TESTING SERVICE ORDER CREATION (Internal) ===\n\n";
    
    $request = \Illuminate\Http\Request::create('/service-order', 'POST', $payload);
    auth()->login($testUser);
    
    $controller = new \App\Http\Controllers\ServiceOrderController();
    
    echo "Calling ServiceOrderController::store() directly...\n";
    
    $response = $controller->store($request);
    
    echo "Response Status: " . $response->getStatusCode() . "\n";
    echo "Response Content:\n";
    echo json_encode(json_decode($response->getContent()), JSON_PRETTY_PRINT) . "\n";
    
    // If conflict, try with force_replace
    if ($response->getStatusCode() == 409) {
        echo "\n[INFO] Existing order found, retrying with force_replace=true...\n";
        $payload['force_replace'] = true;
        $request2 = \Illuminate\Http\Request::create('/service-order', 'POST', $payload);
        $response = $controller->store($request2);
        echo "Response Status: " . $response->getStatusCode() . "\n";
        echo "Response Content:\n";
        $content = json_decode($response->getContent(), true);
        if ($content) {
            echo json_encode($content, JSON_PRETTY_PRINT) . "\n";
        } else {
            echo $response->getContent() . "\n";
        }
    }
    
    if ($response->getStatusCode() >= 400) {
        echo "\n[ERROR] Service order creation failed!\n";
    } else {
        echo "\n[SUCCESS] Service order created successfully!\n";
    }
    
} catch (\Exception $e) {
    echo "\n[EXCEPTION CAUGHT]\n";
    echo "Type: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    
    // Get trace
    $trace = $e->getTrace();
    if (count($trace) > 3) {
        echo "\nKey trace:\n";
        for ($i = 1; $i <= min(5, count($trace)); $i++) {
            $f = $trace[$i];
            echo "  #$i: " . ($f['file'] ?? '?') . ":" . ($f['line'] ?? '?') . " " . ($f['class'] ?? '') . ($f['type'] ?? '') . ($f['function'] ?? '') . "\n";
        }
    }
}

// Logout
if (auth()->check()) {
    auth()->logout();
}

echo "\n==============================================\n";
echo "   TEST COMPLETE\n";
echo "==============================================\n";