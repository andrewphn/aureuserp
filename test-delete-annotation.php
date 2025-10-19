<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PdfPageAnnotation;
use Illuminate\Support\Facades\Auth;

echo "=== Testing Annotation Delete Endpoint ===\n\n";

// First, let's see what annotations exist
echo "1. Checking existing annotations:\n";
$annotations = PdfPageAnnotation::orderBy('created_at', 'desc')->take(5)->get();

if ($annotations->isEmpty()) {
    echo "   ❌ No annotations found in database\n";
    exit(1);
}

foreach ($annotations as $anno) {
    echo "   - ID: {$anno->id}, Label: {$anno->label}, Page: {$anno->pdf_page_id}\n";
}

$testAnnotation = $annotations->first();
echo "\n2. Using annotation ID {$testAnnotation->id} for testing\n";

// Test the API route
echo "\n3. Testing API DELETE endpoint:\n";

// Simulate authenticated request
$user = \App\Models\User::first();
if (!$user) {
    echo "   ❌ No users found in database\n";
    exit(1);
}

Auth::login($user);
echo "   Authenticated as: {$user->name} (ID: {$user->id})\n";

// Make API request using Laravel HTTP client
try {
    $response = \Illuminate\Support\Facades\Http::withHeaders([
        'Accept' => 'application/json',
        'X-CSRF-TOKEN' => csrf_token(),
    ])->delete(url("/api/pdf/page/annotations/{$testAnnotation->id}"));

    echo "   Response Status: {$response->status()}\n";
    echo "   Response Body:\n";
    echo "   " . json_encode($response->json(), JSON_PRETTY_PRINT) . "\n";

    if ($response->successful()) {
        echo "   ✅ DELETE request successful\n";

        // Verify annotation was deleted
        $deleted = PdfPageAnnotation::find($testAnnotation->id);
        if ($deleted === null) {
            echo "   ✅ Annotation successfully removed from database\n";
        } else {
            echo "   ❌ Annotation still exists in database\n";
        }
    } else {
        echo "   ❌ DELETE request failed\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
    echo "   Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";
