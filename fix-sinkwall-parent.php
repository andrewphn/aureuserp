<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PdfPageAnnotation;

echo "\n🔧 Fixing Sinkwall Parent Connection\n";
echo str_repeat('=', 80) . "\n\n";

// Find Sinkwall annotation
$sinkwall = PdfPageAnnotation::where('label', 'Sink Wall')
    ->where('annotation_type', 'location')
    ->first();

// Find K1 annotation
$k1 = PdfPageAnnotation::where('label', 'K1')
    ->where('annotation_type', 'room')
    ->first();

if (!$sinkwall) {
    echo "❌ Sinkwall annotation not found\n";
    exit(1);
}

if (!$k1) {
    echo "❌ K1 annotation not found\n";
    exit(1);
}

echo "Found Sinkwall:\n";
echo "  ID: {$sinkwall->id}\n";
echo "  Current parent_annotation_id: " . ($sinkwall->parent_annotation_id ?? 'NULL') . "\n\n";

echo "Found K1:\n";
echo "  ID: {$k1->id}\n";
echo "  Label: {$k1->label}\n\n";

// Update Sinkwall to have K1 as parent
$sinkwall->parent_annotation_id = $k1->id;
$sinkwall->save();

echo "✅ Updated Sinkwall parent_annotation_id to {$k1->id}\n\n";

// Verify the update
$sinkwall->refresh();
echo "Verification:\n";
echo "  Sinkwall ID: {$sinkwall->id}\n";
echo "  Sinkwall parent_annotation_id: {$sinkwall->parent_annotation_id}\n";
echo "  Sinkwall should now appear under K1 in the hierarchy\n";
