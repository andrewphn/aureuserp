<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Webkul\Project\Models\Project;
use Webkul\Project\Models\PdfDocument;
use Webkul\Project\Models\Room;
use Webkul\Project\Models\RoomLocation;
use Webkul\Project\Models\CabinetRun;
use Webkul\Project\Models\Cabinet;
use App\Models\PdfAnnotation;

echo "=== 25 Friendship Lane - Complete Workflow Test ===\n\n";

// Step 1: Get the project
echo "1. Loading Project...\n";
$project = Project::find(1);
if (!$project) {
    echo "ERROR: Project not found!\n";
    exit(1);
}
echo "   ✓ Project: {$project->name} (ID: {$project->id})\n\n";

// Step 2: Get the PDF
echo "2. Loading PDF Document...\n";
$pdf = $project->pdfDocuments()->first();
if (!$pdf) {
    echo "ERROR: No PDF document found!\n";
    exit(1);
}
echo "   ✓ PDF: {$pdf->file_path}\n";
echo "   ✓ Pages: {$pdf->page_count}\n";
echo "   ✓ Current Annotations: {$pdf->annotations()->count()}\n\n";

// Step 3: Check existing rooms
echo "3. Current Rooms:\n";
$rooms = $project->rooms()->get();
foreach ($rooms as $room) {
    $locations = $room->locations()->count();
    echo "   - {$room->name} ({$room->room_type}) - {$locations} locations\n";
}
echo "\n";

// Step 4: Check existing cabinet runs
echo "4. Current Cabinet Runs:\n";
$cabinetRuns = $project->cabinetRuns()->get();
if ($cabinetRuns->count() === 0) {
    echo "   ⚠ No cabinet runs exist yet\n\n";
} else {
    foreach ($cabinetRuns as $run) {
        echo "   - {$run->name} ({$run->type})\n";
    }
    echo "\n";
}

// Step 5: Simulate creating annotations for a sample room
echo "5. Simulating Annotation Creation...\n";
echo "   Note: This would normally be done through the UI by:\n";
echo "   - Drawing rectangles on the PDF floor plan\n";
echo "   - Each rectangle becomes an annotation with coordinates\n";
echo "   - Annotations link to pages and can reference entities\n\n";

// Sample annotation data (this would come from the UI)
$sampleAnnotation = [
    'pdf_document_id' => $pdf->id,
    'page_number' => 2, // Floor plan page
    'type' => 'rectangle',
    'coordinates' => json_encode([
        'x' => 100,
        'y' => 200,
        'width' => 150,
        'height' => 100,
    ]),
    'entity_type' => 'room',
    'entity_id' => $rooms->first()->id ?? null,
    'content' => 'Kitchen area on floor plan',
];

echo "   Sample annotation structure:\n";
echo "   " . json_encode($sampleAnnotation, JSON_PRETTY_PRINT) . "\n\n";

// Step 6: Simulate creating a room location
echo "6. Creating Sample Room Location...\n";
$room = $rooms->first();
if ($room) {
    try {
        $location = RoomLocation::create([
            'room_id' => $room->id,
            'name' => 'Main Wall',
            'location_type' => 'wall',
            'description' => 'Primary kitchen wall with upper and lower cabinets',
        ]);
        echo "   ✓ Created location: {$location->name} for {$room->name}\n\n";
    } catch (\Exception $e) {
        echo "   ℹ Location may already exist or error: {$e->getMessage()}\n\n";
    }
}

// Step 7: Simulate creating a cabinet run
echo "7. Creating Sample Cabinet Run...\n";
$location = $room?->locations()->first();
if ($location) {
    try {
        $cabinetRun = CabinetRun::create([
            'room_location_id' => $location->id,
            'name' => 'Run A - Main Wall',
            'type' => 'base',
            'linear_feet' => 12.5,
            'notes' => 'Base cabinets along main wall',
        ]);
        echo "   ✓ Created cabinet run: {$cabinetRun->name}\n";
        echo "   ✓ Type: {$cabinetRun->type}\n";
        echo "   ✓ Linear Feet: {$cabinetRun->linear_feet}\n\n";
    } catch (\Exception $e) {
        echo "   ℹ Cabinet run may already exist or error: {$e->getMessage()}\n\n";
    }
}

// Step 8: Summary
echo "8. Current Project Summary:\n";
echo "   - Rooms: " . $project->rooms()->count() . "\n";
echo "   - Room Locations: " . RoomLocation::whereHas('room', function($q) use ($project) {
    $q->where('project_id', $project->id);
})->count() . "\n";
echo "   - Cabinet Runs: " . $project->cabinetRuns()->count() . "\n";
echo "   - PDF Annotations: " . $pdf->annotations()->count() . "\n\n";

echo "=== Workflow Test Complete ===\n";
echo "\nTo complete the full workflow through the UI:\n";
echo "1. Visit: http://aureuserp.test/admin/project/projects/1/edit\n";
echo "2. Go to 'Documents' tab\n";
echo "3. Click 'Review PDF & Price'\n";
echo "4. Use annotation tools to mark rooms on floor plans\n";
echo "5. Create room locations in the 'Project Data' tab\n";
echo "6. Add cabinet runs to each location\n";
echo "7. Annotate individual cabinets on elevation drawings\n";
