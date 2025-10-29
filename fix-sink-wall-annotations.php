<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$annotations = \App\Models\PdfPageAnnotation::where('room_location_id', 1)->get();
echo 'Found ' . $annotations->count() . ' annotations linked to Sink Wall' . PHP_EOL;

foreach ($annotations as $anno) {
    if ($anno->room_id != 10) {
        $oldRoom = $anno->room_id;
        $anno->room_id = 10;
        $anno->save();
        echo '  ✅ Updated annotation ' . $anno->id . ': room_id ' . $oldRoom . ' → 10' . PHP_EOL;
    } else {
        echo '  ✓ Annotation ' . $anno->id . ' already has correct room_id (10)' . PHP_EOL;
    }
}

echo PHP_EOL . '✅ All Sink Wall annotations now belong to K1 (room_id = 10)' . PHP_EOL;
