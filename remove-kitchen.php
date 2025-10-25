<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PdfPageAnnotation;

echo "\n🗑️ Removing Kitchen Room Annotation\n";
echo str_repeat('=', 80) . "\n\n";

$kitchen = PdfPageAnnotation::where('label', 'Kitchen')
    ->where('annotation_type', 'room')
    ->first();

if ($kitchen) {
    echo "Found Kitchen annotation:\n";
    echo "  ID: {$kitchen->id}\n";
    echo "  Label: {$kitchen->label}\n";
    echo "  Type: {$kitchen->annotation_type}\n\n";

    $kitchen->delete();

    echo "✅ Kitchen annotation deleted successfully\n";
} else {
    echo "❌ Kitchen annotation not found\n";
}

// Verify deletion
echo "\nVerifying remaining annotations on PDF page 2:\n";
$remaining = PdfPageAnnotation::where('pdf_page_id', 2)->get();
foreach ($remaining as $anno) {
    echo "  - ID: {$anno->id} | Label: {$anno->label} | Type: {$anno->annotation_type}\n";
}
echo "\nTotal remaining: " . $remaining->count() . "\n";
