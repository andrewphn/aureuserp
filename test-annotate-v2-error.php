<?php

// Test script to see why AnnotatePdfV2 page is throwing 500 error

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Http\Kernel');

// Simulate a request to the annotate-v2 page
$request = \Illuminate\Http\Request::create(
    '/admin/project/projects/1/annotate-v2?pdf=1',
    'GET'
);

try {
    $response = $kernel->handle($request);
    echo "Response Status: " . $response->getStatusCode() . "\n";

    if ($response->getStatusCode() >= 400) {
        echo "Response Content:\n";
        echo substr($response->getContent(), 0, 1000) . "\n";
    } else {
        echo "✓ Page loaded successfully!\n";
    }
} catch (\Exception $e) {
    echo "❌ Exception caught:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

$kernel->terminate($request, $response ?? null);
