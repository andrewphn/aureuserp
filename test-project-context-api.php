#!/usr/bin/env php
<?php

/**
 * Test script to verify project context API endpoint
 * Tests: /api/pdf/page/{pdfPageId}/project-context
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PdfPage;
use Webkul\Project\Models\Project;

echo "═══════════════════════════════════════════════════════════════\n";
echo "🧪 Testing Project Context API Endpoint\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Step 1: Find a PDF page with a project
echo "📄 Step 1: Finding PDF page with project...\n";
$pdfPage = PdfPage::whereHas('pdfDocument', function($query) {
    $query->where('module_type', 'Webkul\\Project\\Models\\Project');
})->first();

if (!$pdfPage) {
    echo "❌ No PDF pages found with project association\n";
    exit(1);
}

echo "✅ Found PDF page ID: {$pdfPage->id}\n";
echo "   Document ID: {$pdfPage->pdf_document_id}\n\n";

// Step 2: Load project through the document
echo "📦 Step 2: Loading project context...\n";
$pdfDocument = $pdfPage->pdfDocument;
$project = $pdfDocument->module;

if (!$project) {
    echo "❌ No project found for PDF document\n";
    exit(1);
}

echo "✅ Project ID: {$project->id}\n";
echo "   Project Number: {$project->project_number}\n";
echo "   Project Name: {$project->name}\n\n";

// Step 3: Load project relationships
echo "🔗 Step 3: Loading project relationships...\n";
$project->load(['partner', 'company', 'branch', 'addresses']);

echo "Partner: " . ($project->partner ? $project->partner->name : 'None') . "\n";
echo "Company: " . ($project->company ? $project->company->name : 'None') . "\n";
echo "Branch: " . ($project->branch ? $project->branch->name : 'None') . "\n";

$primaryAddress = $project->addresses()->where('is_primary', true)->first();
echo "Primary Address: " . ($primaryAddress ? "{$primaryAddress->street1}, {$primaryAddress->city}" : 'None') . "\n\n";

// Step 4: Simulate API response
echo "📡 Step 4: Simulating API response structure...\n";
$response = [
    'success' => true,
    'project_context' => [
        'project_id' => $project->id,
        'project_number' => $project->project_number,
        'project_name' => $project->name,
        'partner' => $project->partner ? [
            'id' => $project->partner->id,
            'name' => $project->partner->name,
            'email' => $project->partner->email,
            'phone' => $project->partner->phone,
        ] : null,
        'company' => $project->company ? [
            'id' => $project->company->id,
            'name' => $project->company->name,
            'email' => $project->company->email,
            'phone' => $project->company->phone,
        ] : null,
        'branch' => $project->branch ? [
            'id' => $project->branch->id,
            'name' => $project->branch->name,
            'email' => $project->branch->email,
            'phone' => $project->branch->phone,
        ] : null,
        'address' => $primaryAddress ? [
            'street1' => $primaryAddress->street1,
            'street2' => $primaryAddress->street2,
            'city' => $primaryAddress->city,
            'zip' => $primaryAddress->zip,
            'state_id' => $primaryAddress->state_id,
            'country_id' => $primaryAddress->country_id,
        ] : null,
    ],
];

echo json_encode($response, JSON_PRETTY_PRINT) . "\n\n";

// Step 5: Test actual API endpoint
echo "🌐 Step 5: Testing actual API endpoint...\n";
echo "   Endpoint: /api/pdf/page/{$pdfPage->id}/project-context\n";
echo "   (Note: Requires authentication, so using Laravel)\n\n";

// Use Laravel's internal route testing
$app = app();
$kernel = $app->make('Illuminate\Contracts\Http\Kernel');

// Create a request
$request = \Illuminate\Http\Request::create(
    "/api/pdf/page/{$pdfPage->id}/project-context",
    'GET'
);

// Authenticate as first user
$user = \App\Models\User::first();
if ($user) {
    auth()->login($user);
    echo "✅ Authenticated as: {$user->email}\n";
} else {
    echo "⚠️  No user found for authentication\n";
}

try {
    $response = $kernel->handle($request);
    $statusCode = $response->getStatusCode();
    $content = $response->getContent();

    echo "Status Code: {$statusCode}\n";

    if ($statusCode === 200) {
        echo "✅ API endpoint working!\n\n";
        echo "Response:\n";
        echo json_encode(json_decode($content), JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "❌ API endpoint returned error\n";
        echo "Response: {$content}\n";
    }
} catch (\Exception $e) {
    echo "❌ Error testing endpoint: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "✅ Test Complete\n";
echo "═══════════════════════════════════════════════════════════════\n";
