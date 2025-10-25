<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PdfPageAnnotation;

echo "\n🔍 Checking Sinkwall Annotation\n";
echo str_repeat('=', 80) . "\n\n";

$annotations = PdfPageAnnotation::where('label', 'LIKE', '%sink%')
    ->orWhere('label', 'LIKE', '%Sink%')
    ->get();

if ($annotations->isEmpty()) {
    echo "❌ No Sinkwall annotations found\n";
} else {
    foreach ($annotations as $anno) {
        echo "Annotation ID: {$anno->id}\n";
        echo "Label: {$anno->label}\n";
        echo "Type: {$anno->annotation_type}\n";
        echo "Parent Annotation ID: " . ($anno->parent_annotation_id ?? 'NULL') . "\n";
        echo "Room ID (entity): " . ($anno->room_id ?? 'NULL') . "\n";
        echo "PDF Page ID: {$anno->pdf_page_id}\n";
        echo "\n";
    }
}

// Also check K1 annotation
echo "🔍 K1 Room Annotation\n";
echo str_repeat('=', 80) . "\n\n";

$k1 = PdfPageAnnotation::where('label', 'K1')
    ->where('annotation_type', 'room')
    ->first();

if ($k1) {
    echo "Annotation ID: {$k1->id}\n";
    echo "Label: {$k1->label}\n";
    echo "Type: {$k1->annotation_type}\n";
    echo "Room ID (entity): " . ($k1->room_id ?? 'NULL') . "\n";
    echo "PDF Page ID: {$k1->pdf_page_id}\n";
} else {
    echo "❌ K1 room annotation not found\n";
}
