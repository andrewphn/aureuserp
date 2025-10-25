<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PdfPageAnnotation;

echo "\n🔧 Fixing K1 Room Hierarchy\n";
echo str_repeat('=', 80) . "\n\n";

// Step 1: Find or create K1 room annotation
echo "📍 Step 1: Creating K1 room annotation...\n";

$k1Room = PdfPageAnnotation::firstOrCreate(
    [
        'pdf_page_id' => 2,
        'annotation_type' => 'room',
        'label' => 'K1',
    ],
    [
        'room_id' => 6, // Links to projects_rooms.id = 6 (K1)
        'notes' => 'K1 room area',
        'coordinates' => json_encode([
            'x' => 100,
            'y' => 100,
            'width' => 200,
            'height' => 200,
        ]),
    ]
);

if ($k1Room->wasRecentlyCreated) {
    echo "✅ Created K1 room annotation (ID: {$k1Room->id})\n";
} else {
    echo "✓ K1 room annotation already exists (ID: {$k1Room->id})\n";
}

// Step 2: Update Sinkwall to be child of K1 room
echo "\n📍 Step 2: Updating Sinkwall location...\n";

$sinkwall = PdfPageAnnotation::where('pdf_page_id', 2)
    ->where('label', 'K1SinkWall')
    ->first();

if ($sinkwall) {
    $sinkwall->update([
        'annotation_type' => 'location',
        'parent_annotation_id' => $k1Room->id,
        'room_id' => 6, // Inherited from K1 room
    ]);
    echo "✅ Updated Sinkwall (ID: {$sinkwall->id}) - now child of K1 room\n";
} else {
    echo "❌ Sinkwall annotation not found\n";
}

// Step 3: Verify hierarchy
echo "\n📊 Verification:\n";
echo str_repeat('-', 80) . "\n";

$annotations = PdfPageAnnotation::where('pdf_page_id', 2)
    ->orderBy('id')
    ->get();

foreach ($annotations as $a) {
    $indent = $a->parent_annotation_id ? '  └─ ' : '';
    echo sprintf("%s%-4d | %-15s | %-25s | Room: %-2s | Parent: %-4s\n",
        $indent,
        $a->id,
        $a->annotation_type,
        $a->label,
        $a->room_id ?? 'null',
        $a->parent_annotation_id ?? 'null'
    );
}

echo "\n✅ Hierarchy fixed!\n\n";
