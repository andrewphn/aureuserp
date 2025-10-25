<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PdfPageAnnotation;

echo "\n🔍 Checking parent_annotation_id in database\n";
echo str_repeat('=', 80) . "\n\n";

// Find K1 and Sink Wall annotations
$k1 = PdfPageAnnotation::where('label', 'K1')
    ->where('annotation_type', 'room')
    ->first();

$sinkwall = PdfPageAnnotation::where('label', 'Sink Wall')
    ->where('annotation_type', 'location')
    ->first();

if ($k1) {
    echo "K1 Annotation:\n";
    echo "  ID: {$k1->id}\n";
    echo "  Label: {$k1->label}\n";
    echo "  Type: {$k1->annotation_type}\n";
    echo "  parent_annotation_id: " . ($k1->parent_annotation_id ?? 'NULL') . "\n";
    echo "  room_id: " . ($k1->room_id ?? 'NULL') . "\n\n";
}

if ($sinkwall) {
    echo "Sink Wall Annotation:\n";
    echo "  ID: {$sinkwall->id}\n";
    echo "  Label: {$sinkwall->label}\n";
    echo "  Type: {$sinkwall->annotation_type}\n";
    echo "  parent_annotation_id: " . ($sinkwall->parent_annotation_id ?? 'NULL') . "\n";
    echo "  room_id: " . ($sinkwall->room_id ?? 'NULL') . "\n";
    echo "  room_location_id: " . ($sinkwall->room_location_id ?? 'NULL') . "\n\n";
}

// Check if Sink Wall's parent_annotation_id points to K1
if ($k1 && $sinkwall) {
    if ($sinkwall->parent_annotation_id == $k1->id) {
        echo "✅ Sink Wall's parent_annotation_id ({$sinkwall->parent_annotation_id}) correctly points to K1 ({$k1->id})\n";
    } else {
        echo "❌ Sink Wall's parent_annotation_id ({$sinkwall->parent_annotation_id}) does NOT point to K1 ({$k1->id})\n";
        echo "   Expected: {$k1->id}\n";
        echo "   Got: {$sinkwall->parent_annotation_id}\n";
    }
}

// List all annotations with their parent relationships
echo "\n📋 All annotations with parent relationships:\n";
echo str_repeat('-', 80) . "\n";

$allAnnotations = PdfPageAnnotation::whereNotNull('parent_annotation_id')
    ->orderBy('id')
    ->get();

foreach ($allAnnotations as $anno) {
    echo "ID: {$anno->id} | Label: {$anno->label} | Type: {$anno->annotation_type} | Parent ID: {$anno->parent_annotation_id}\n";
}
