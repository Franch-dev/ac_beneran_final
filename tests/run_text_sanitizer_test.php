<?php
if (file_exists(__DIR__.'/../vendor/autoload.php')) {
    require __DIR__.'/../vendor/autoload.php';
} else {
    require __DIR__.'/../app/Utilities/TextSanitizer.php';
    
    // Define a tiny shim namespace if autoloader isn't available
    require_once __DIR__.'/../app/Utilities/TextSanitizer.php';
}

$tests = [
    "Hello <b>World</b>" => "Hello World",
    "Visit https://cdn.example.com/pic.jpg" => "Visit",
    "Data URI: data:image/png;base64,iVBORw0KGgo=" => "Data URI:",
    "Space  <span>child</span>" => "Space  child",
];

$allPassed = true;
foreach ($tests as $input => $expected) {
    $actual = \App\Utilities\TextSanitizer::sanitize($input);
    if ($actual !== $expected) {
        echo "FAIL: Input: $input\nExpected: $expected\nActual:   $actual\n\n";
        $allPassed = false;
    } else {
        echo "OK: $input -> $actual\n";
    }
}
if ($allPassed) {
    echo "ALL TESTS PASSED\n";
} else {
    echo "SOME TESTS FAILED\n";
}
