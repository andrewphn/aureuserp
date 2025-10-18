<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PdfPageAnnotation;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\Room;

echo "=== Testing Duplicate Room Prevention ===\n\n";

// Get project and PDF page
$project = Project::find(1);
$pdfPage = $project->pdfDocuments()->first()->pages()->where('page_number', 1)->first();

echo "Project: {$project->name} (ID: {$project->id})\n";
echo "PDF Page: Page {$pdfPage->page_number} (ID: {$pdfPage->id})\n\n";

// Count existing Kitchen rooms
$existingKitchens = Room::where('project_id', $project->id)
    ->where('room_type', 'Kitchen')
    ->count();

echo "Existing Kitchen rooms before test: $existingKitchens\n\n";

// Try to create annotation with existing room name "Kitchen 1"
echo "Creating annotation with existing room name 'Kitchen 1'...\n";

$annotation = PdfPageAnnotation::create([
    'pdf_page_id' => $pdfPage->id,
    'annotation_type' => 'room',
    'label' => 'Kitchen 1',  // This name already exists!
    'x' => 700,
    'y' => 100,
    'width' => 150,
    'height' => 120,
    'room_type' => 'Kitchen',
    'color' => '#3b82f6',
    'creator_id' => auth()->id() ?? 1,
]);

echo "✓ Annotation created (ID: {$annotation->id})\n";

// Now trigger entity creation using AnnotationEntityService
$entityService = new \App\Services\AnnotationEntityService();
$result = $entityService->createOrLinkEntityFromAnnotation($annotation, [
    'project_id' => $project->id,
    'page_number' => $pdfPage->page_number,
]);

if ($result['success']) {
    echo "✓ Entity service result: SUCCESS\n";
    echo "  Entity Type: {$result['entity_type']}\n";
    echo "  Entity ID: {$result['entity_id']}\n";
    echo "  Reused existing: " . ($result['reused'] ? 'YES' : 'NO') . "\n";
    echo "  Room Name: {$result['entity']->name}\n";
    
    // Refresh annotation to see linked room_id
    $annotation = $annotation->fresh();
    echo "  Annotation room_id: {$annotation->room_id}\n";
} else {
    echo "✗ Entity service result: FAILED\n";
    echo "  Error: {$result['error']}\n";
}

// Count Kitchen rooms after test
$newKitchenCount = Room::where('project_id', $project->id)
    ->where('room_type', 'Kitchen')
    ->count();

echo "\nKitchen rooms after test: $newKitchenCount\n";
echo "Difference: " . ($newKitchenCount - $existingKitchens) . "\n";

if ($newKitchenCount === $existingKitchens) {
    echo "✅ SUCCESS: No duplicate room created! Existing room was reused.\n";
} else {
    echo "❌ FAILED: Duplicate room was created.\n";
}

echo "\n=== Test Complete ===\n";
