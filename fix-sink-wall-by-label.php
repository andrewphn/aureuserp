<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$annotations = \App\Models\PdfPageAnnotation::where('label', 'Sink Wall')->get();
echo 'Found ' . $annotations->count() . ' annotations with label "Sink Wall"' . PHP_EOL;

foreach ($annotations as $anno) {
    echo PHP_EOL . 'Annotation ID: ' . $anno->id . PHP_EOL;
    echo '  Type: ' . $anno->type . PHP_EOL;
    echo '  Label: ' . $anno->label . PHP_EOL;
    echo '  room_id: ' . ($anno->room_id ?? 'NULL') . PHP_EOL;
    echo '  room_location_id: ' . ($anno->room_location_id ?? 'NULL') . PHP_EOL;
    echo '  cabinet_run_id: ' . ($anno->cabinet_run_id ?? 'NULL') . PHP_EOL;

    $updated = false;

    // Update room_id if wrong
    if ($anno->room_id != 10) {
        $anno->room_id = 10;
        $updated = true;
        echo '  → Set room_id to 10 (K1)' . PHP_EOL;
    }

    // Set room_location_id if it's a location type annotation
    if ($anno->type === 'location' && !$anno->room_location_id) {
        $anno->room_location_id = 1; // Sink Wall location entity ID
        $updated = true;
        echo '  → Set room_location_id to 1 (Sink Wall entity)' . PHP_EOL;
    }

    if ($updated) {
        $anno->save();
        echo '  ✅ Updated!' . PHP_EOL;
    } else {
        echo '  ✓ Already correct' . PHP_EOL;
    }
}
