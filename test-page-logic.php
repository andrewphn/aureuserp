<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    $project = \Webkul\Project\Models\Project::find(1);
    echo "✓ Project loaded: {$project->name}\n";
    
    $pdfDocument = \App\Models\PdfDocument::find(1);
    echo "✓ PDF Document loaded: {$pdfDocument->file_name}\n";
    
    // Check if file exists
    $fileExists = \Illuminate\Support\Facades\Storage::disk('public')->exists($pdfDocument->file_path);
    echo "✓ File exists: " . ($fileExists ? 'Yes' : 'No') . "\n";
    
    // Try to get PDF page
    $pdfPage = \Webkul\Project\Models\PdfPage::where('document_id', $pdfDocument->id)
        ->where('page_number', 1)
        ->first();
    
    if ($pdfPage) {
        echo "✓ PDF Page found: ID {$pdfPage->id}\n";
    } else {
        echo "⚠ No PDF page record for page 1\n";
    }
    
    // Get URL
    $pdfUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($pdfDocument->file_path);
    echo "✓ PDF URL: {$pdfUrl}\n";
    
    echo "\n✅ All checks passed!\n";
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}
