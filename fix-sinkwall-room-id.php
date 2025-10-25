<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PdfPageAnnotation;

echo "\n🔧 Fixing Sink Wall room_id\n";
echo str_repeat('=', 80) . "\n\n";

// Find Sink Wall annotation
$sinkwall = PdfPageAnnotation::where('label', 'Sink Wall')
    ->where('annotation_type', 'location')
    ->first();

// Find K1 annotation
$k1 = PdfPageAnnotation::where('label', 'K1')
    ->where('annotation_type', 'room')
    ->first();

if (!$sinkwall) {
    echo "❌ Sink Wall annotation not found\n";
    exit(1);
}

if (!$k1) {
    echo "❌ K1 annotation not found\n";
    exit(1);
}

echo "Found Sink Wall:\n";
echo "  ID: {$sinkwall->id}\n";
echo "  Current room_id: " . ($sinkwall->room_id ?? 'NULL') . "\n";
echo "  Parent annotation ID: {$sinkwall->parent_annotation_id}\n\n";

echo "Found K1:\n";
echo "  ID: {$k1->id}\n";
echo "  room_id: {$k1->room_id}\n\n";

// Update Sink Wall to have K1's room_id
$sinkwall->room_id = $k1->room_id;
$sinkwall->save();

echo "✅ Updated Sink Wall:\n";
echo "  room_id: {$sinkwall->room_id}\n\n";

echo "Now run backfill-sinkwall-location.php to create the RoomLocation entity\n";
