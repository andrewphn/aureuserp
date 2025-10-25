<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PdfPageAnnotation;
use Webkul\Project\Models\RoomLocation;

echo "\n🔧 Backfilling Sink Wall Location Entity\n";
echo str_repeat('=', 80) . "\n\n";

// Find Sink Wall annotation
$sinkwall = PdfPageAnnotation::where('label', 'Sink Wall')
    ->where('annotation_type', 'location')
    ->first();

if (!$sinkwall) {
    echo "❌ Sink Wall annotation not found\n";
    exit(1);
}

echo "Found Sink Wall annotation:\n";
echo "  ID: {$sinkwall->id}\n";
echo "  Label: {$sinkwall->label}\n";
echo "  Room ID: {$sinkwall->room_id}\n";
echo "  Current room_location_id: " . ($sinkwall->room_location_id ?? 'NULL') . "\n\n";

// Check if RoomLocation already exists
if ($sinkwall->room_location_id) {
    $existing = RoomLocation::find($sinkwall->room_location_id);
    if ($existing) {
        echo "✓ RoomLocation already exists:\n";
        echo "  ID: {$existing->id}\n";
        echo "  Name: {$existing->name}\n";
        echo "  Room ID: {$existing->room_id}\n";
        exit(0);
    }
}

// Create RoomLocation entity
if ($sinkwall->room_id) {
    $roomLocation = RoomLocation::create([
        'room_id' => $sinkwall->room_id,
        'name' => $sinkwall->label,
        'notes' => $sinkwall->notes ?? '',
        'creator_id' => 1, // Default to first user
    ]);

    echo "✅ Created RoomLocation entity:\n";
    echo "  ID: {$roomLocation->id}\n";
    echo "  Name: {$roomLocation->name}\n";
    echo "  Room ID: {$roomLocation->room_id}\n\n";

    // Update annotation to reference the new RoomLocation
    $sinkwall->room_location_id = $roomLocation->id;
    $sinkwall->save();

    echo "✅ Updated Sink Wall annotation:\n";
    echo "  room_location_id: {$sinkwall->room_location_id}\n\n";

    echo "🎉 Backfill complete! Now when you go to the project page, K1 should show \"1 Location\"\n";
} else {
    echo "❌ Sink Wall annotation has no room_id, cannot create RoomLocation\n";
}
