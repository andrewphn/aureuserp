<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PdfPageAnnotation;
use Webkul\Project\Models\Room;
use Webkul\Project\Models\RoomLocation;

echo "\n🔍 Comparing Location Systems for K1 Room\n";
echo str_repeat('=', 80) . "\n\n";

// Find K1 room entity
$k1Room = Room::where('name', 'K1')->first();

if (!$k1Room) {
    echo "❌ K1 room not found in projects_rooms table\n";
    exit(1);
}

echo "📊 K1 Room Entity (projects_rooms):\n";
echo "  ID: {$k1Room->id}\n";
echo "  Name: {$k1Room->name}\n";
echo "  Room Type: {$k1Room->room_type}\n\n";

// Check RoomLocation system (projects_room_locations)
echo "📍 Traditional Location System (projects_room_locations):\n";
$roomLocations = RoomLocation::where('room_id', $k1Room->id)->get();
echo "  Count: {$roomLocations->count()}\n";
if ($roomLocations->count() > 0) {
    foreach ($roomLocations as $loc) {
        echo "    - ID: {$loc->id} | Name: {$loc->name} | Type: {$loc->location_type}\n";
    }
} else {
    echo "    (No locations in traditional system)\n";
}
echo "\n";

// Check PDF Annotation system
echo "🎨 PDF Annotation System (pdf_page_annotations):\n";
$pdfAnnotations = PdfPageAnnotation::where('room_id', $k1Room->id)
    ->orWhere(function($q) use ($k1Room) {
        $k1Annotation = PdfPageAnnotation::where('room_id', $k1Room->id)
            ->where('annotation_type', 'room')
            ->first();
        if ($k1Annotation) {
            $q->where('parent_annotation_id', $k1Annotation->id);
        }
    })
    ->get();

echo "  Count: {$pdfAnnotations->count()}\n";
if ($pdfAnnotations->count() > 0) {
    foreach ($pdfAnnotations as $anno) {
        $parent = $anno->parent_annotation_id ? "parent: {$anno->parent_annotation_id}" : "top-level";
        echo "    - ID: {$anno->id} | Label: {$anno->label} | Type: {$anno->annotation_type} | {$parent}\n";
    }
}

echo "\n";
echo "💡 SUMMARY:\n";
echo "  - Rooms table 'Locations' count comes from projects_room_locations table\n";
echo "  - PDF annotations are in pdf_page_annotations table\n";
echo "  - These are TWO SEPARATE SYSTEMS that are not synchronized\n";
echo "  - K1 has " . $roomLocations->count() . " traditional locations\n";
echo "  - K1 has " . $pdfAnnotations->count() . " PDF annotations\n";
