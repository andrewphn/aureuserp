<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo 'Step 1: Update all Sink Wall annotations to use location ID 11' . PHP_EOL;
echo str_repeat('=', 80) . PHP_EOL;

$annotations = \App\Models\PdfPageAnnotation::where('label', 'Sink Wall')->get();

foreach ($annotations as $anno) {
    $updated = false;

    // Ensure room_id is 10 (K1)
    if ($anno->room_id != 10) {
        $anno->room_id = 10;
        $updated = true;
        echo 'Annotation ' . $anno->id . ': Set room_id to 10' . PHP_EOL;
    }

    // Ensure room_location_id is 11 (active Sink Wall location)
    if ($anno->room_location_id != 11) {
        $anno->room_location_id = 11;
        $updated = true;
        echo 'Annotation ' . $anno->id . ': Set room_location_id to 11' . PHP_EOL;
    }

    if ($updated) {
        $anno->save();
        echo '  ✅ Updated annotation ' . $anno->id . PHP_EOL;
    } else {
        echo '  ✓ Annotation ' . $anno->id . ' already correct' . PHP_EOL;
    }
}

echo PHP_EOL . 'Step 2: Delete duplicate/orphaned Sink Wall locations' . PHP_EOL;
echo str_repeat('=', 80) . PHP_EOL;

// Delete duplicate locations (keep only ID 11)
$duplicates = \Webkul\Project\Models\RoomLocation::where('name', 'Sink Wall')
    ->where('id', '!=', 11)
    ->get();

foreach ($duplicates as $dup) {
    echo 'Deleting duplicate location ID ' . $dup->id . ' (room_id: ' . ($dup->room_id ?? 'NULL') . ')' . PHP_EOL;
    $dup->delete();
}

echo PHP_EOL . '✅ Sink Wall consolidated! All annotations now use location ID 11 in room K1' . PHP_EOL;
