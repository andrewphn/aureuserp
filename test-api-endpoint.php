<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Authenticate as a user
$user = App\Models\User::first();
Auth::login($user);

// Test the controller method directly
$controller = new App\Http\Controllers\Api\PdfAnnotationController(
    new App\Services\AnnotationService()
);

try {
    $response = $controller->getProjectNumber(1);
    echo "Response status: " . $response->getStatusCode() . PHP_EOL;
    echo "Response body: " . $response->getContent() . PHP_EOL;
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
    echo "Trace: " . $e->getTraceAsString() . PHP_EOL;
}
