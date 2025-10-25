<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PdfPageAnnotation;

echo "\n🔍 All Annotations on PDF Page 2\n";
echo str_repeat('=', 80) . "\n\n";

$annotations = PdfPageAnnotation::where('pdf_page_id', 2)
    ->orderBy('id')
    ->get();

if ($annotations->isEmpty()) {
    echo "❌ No annotations found on page 2\n";
} else {
    foreach ($annotations as $anno) {
        echo "ID: {$anno->id} | Label: {$anno->label} | Type: {$anno->annotation_type}\n";
        echo "  Parent Annotation ID: " . ($anno->parent_annotation_id ?? 'NULL') . "\n";
        echo "  Room ID (entity): " . ($anno->room_id ?? 'NULL') . "\n";
        echo "\n";
    }
}

echo "\nTotal annotations: " . $annotations->count() . "\n";
