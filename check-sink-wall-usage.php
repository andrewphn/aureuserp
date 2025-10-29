<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$locations = \Webkul\Project\Models\RoomLocation::where('name', 'Sink Wall')->get();

echo 'Sink Wall Location Analysis:' . PHP_EOL;
echo str_repeat('=', 80) . PHP_EOL;

foreach ($locations as $loc) {
    $annoCount = \App\Models\PdfPageAnnotation::where('room_location_id', $loc->id)->count();
    $room = $loc->room;

    echo 'Location ID: ' . $loc->id . PHP_EOL;
    echo '  Room ID: ' . ($loc->room_id ?? 'NULL') . PHP_EOL;
    echo '  Room Name: ' . ($room->name ?? 'ORPHANED') . PHP_EOL;
    echo '  Annotations: ' . $annoCount . PHP_EOL;
    echo '  Created: ' . $loc->created_at . PHP_EOL;
    echo PHP_EOL;
}

// Check annotations with label "Sink Wall" but no room_location_id
$orphanedAnnos = \App\Models\PdfPageAnnotation::where('label', 'Sink Wall')
    ->whereNull('room_location_id')
    ->count();

echo 'Orphaned "Sink Wall" annotations (no room_location_id): ' . $orphanedAnnos . PHP_EOL;
