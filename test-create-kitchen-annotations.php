<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PdfDocument;
use App\Models\PdfPage;
use App\Models\PdfPageAnnotation;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\Room;

echo "=== Creating Kitchen Annotations for E2E Testing ===\n\n";

// Get project and PDF
$project = Project::find(1);
if (!$project) {
    die("ERROR: Project not found\n");
}

$pdfDocument = $project->pdfDocuments()->first();
if (!$pdfDocument) {
    die("ERROR: PDF document not found\n");
}

$pdfPage = $pdfDocument->pages()->where('page_number', 1)->first();
if (!$pdfPage) {
    die("ERROR: PDF page 1 not found\n");
}

echo "Project: {$project->name} (ID: {$project->id})\n";
echo "PDF Document: {$pdfDocument->file_name} (ID: {$pdfDocument->id})\n";
echo "PDF Page: Page {$pdfPage->page_number} (ID: {$pdfPage->id})\n\n";

// Check existing annotations
$existingCount = PdfPageAnnotation::where('pdf_page_id', $pdfPage->id)->count();
echo "Existing annotations on page: $existingCount\n";

// Create 5 Kitchen room annotations with different positions
$kitchenAnnotations = [
    [
        'label' => 'Kitchen 1',
        'x' => 100,
        'y' => 100,
        'width' => 150,
        'height' => 120,
        'length_ft' => 12.5,
        'width_ft' => 10.0,
        'ceiling_height_ft' => 8.0,
    ],
    [
        'label' => 'Kitchen 2',
        'x' => 300,
        'y' => 100,
        'width' => 140,
        'height' => 110,
        'length_ft' => 11.7,
        'width_ft' => 9.2,
        'ceiling_height_ft' => 8.0,
    ],
    [
        'label' => 'Kitchen 3',
        'x' => 100,
        'y' => 300,
        'width' => 160,
        'height' => 130,
        'length_ft' => 13.3,
        'width_ft' => 10.8,
        'ceiling_height_ft' => 8.0,
    ],
    [
        'label' => 'Kitchen 4',
        'x' => 300,
        'y' => 300,
        'width' => 145,
        'height' => 115,
        'length_ft' => 12.1,
        'width_ft' => 9.6,
        'ceiling_height_ft' => 8.0,
    ],
    [
        'label' => 'Kitchen 5',
        'x' => 500,
        'y' => 200,
        'width' => 155,
        'height' => 125,
        'length_ft' => 12.9,
        'width_ft' => 10.4,
        'ceiling_height_ft' => 8.0,
    ],
];

echo "\nCreating 5 Kitchen room annotations...\n";

$createdAnnotations = [];
foreach ($kitchenAnnotations as $index => $data) {
    echo "\n--- Creating annotation: {$data['label']} ---\n";

    // Create Room entity first
    $room = Room::create([
        'project_id' => $project->id,
        'name' => $data['label'],
        'room_type' => 'Kitchen',
        'room_number' => $index + 1,
        'length_ft' => $data['length_ft'],
        'width_ft' => $data['width_ft'],
        'ceiling_height_ft' => $data['ceiling_height_ft'],
        'square_footage' => $data['length_ft'] * $data['width_ft'],
    ]);

    echo "✓ Room entity created (ID: {$room->id})\n";

    // Create PDF annotation using the CORRECT model structure (PdfPageAnnotation)
    $annotation = PdfPageAnnotation::create([
        'pdf_page_id' => $pdfPage->id,
        'annotation_type' => 'room',
        'label' => $data['label'],
        'x' => $data['x'],
        'y' => $data['y'],
        'width' => $data['width'],
        'height' => $data['height'],
        'room_type' => 'Kitchen',
        'color' => '#3b82f6', // Blue for Kitchen
        'room_id' => $room->id, // Direct foreign key to room
        'metadata' => [
            'measurements' => [
                'length_ft' => $data['length_ft'],
                'width_ft' => $data['width_ft'],
                'ceiling_height_ft' => $data['ceiling_height_ft'],
                'square_footage' => $data['length_ft'] * $data['width_ft'],
            ],
        ],
        'creator_id' => auth()->id() ?? 1,
    ]);

    echo "✓ Annotation created (ID: {$annotation->id})\n";
    echo "  Position: ({$data['x']}, {$data['y']})\n";
    echo "  Size: {$data['width']}x{$data['height']}\n";
    echo "  Measurements: {$data['length_ft']} ft × {$data['width_ft']} ft × {$data['ceiling_height_ft']} ft\n";
    echo "  Foreign key: room_id={$annotation->room_id}\n";

    $createdAnnotations[] = [
        'annotation' => $annotation,
        'room' => $room,
    ];
}

echo "\n=== Summary ===\n";
echo "✓ Created 5 Kitchen room annotations\n";
echo "✓ Created 5 Room database entities\n";
echo "✓ Foreign keys linking annotations to rooms\n";

// Verify foreign key relationships
echo "\n=== Verifying Foreign Key Relationships ===\n";
foreach ($createdAnnotations as $index => $data) {
    $annotation = $data['annotation'];
    $room = $data['room'];

    // Check if annotation has correct room_id
    if ($annotation->room_id == $room->id) {
        echo "✓ Annotation {$annotation->id} → Room {$room->id} ({$room->name}): OK\n";
    } else {
        echo "✗ Annotation {$annotation->id} → Room relationship: FAILED\n";
        echo "  Expected: room_id={$room->id}\n";
        echo "  Got: room_id={$annotation->room_id}\n";
    }
}

// Display total count
$finalCount = PdfPageAnnotation::where('pdf_page_id', $pdfPage->id)->count();
echo "\nTotal annotations on page: $finalCount\n";
echo "Kitchen rooms in project: " . $project->rooms()->where('room_type', 'Kitchen')->count() . "\n";

echo "\n=== Test Complete ===\n";
