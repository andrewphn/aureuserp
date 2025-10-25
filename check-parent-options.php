<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n🔍 Checking Parent Annotation Options for Page 3 Location\n";
echo str_repeat('=', 80) . "\n\n";

// Simulate the scenario: Drawing location on page 3
$pdfPageId = 3; // Page 3

// Get PDF page
$pdfPage = \App\Models\PdfPage::find($pdfPageId);
if (!$pdfPage) {
    die("❌ PDF Page not found\n");
}

echo "📄 PDF Page: {$pdfPage->page_number} (ID: {$pdfPage->id})\n";
echo "📦 PDF Document ID: {$pdfPage->document_id}\n\n";

// Simulate getAvailableParents() for a location annotation
$annotationType = 'location';
$validParentTypes = ['room'];

echo "🔍 Looking for parent annotations...\n";
echo "   Annotation type: {$annotationType}\n";
echo "   Valid parent types: " . implode(', ', $validParentTypes) . "\n\n";

$parents = \App\Models\PdfPageAnnotation::whereHas('pdfPage', function ($query) use ($pdfPage) {
        $query->where('document_id', $pdfPage->document_id);
    })
    ->whereIn('annotation_type', $validParentTypes)
    ->orderBy('label')
    ->get();

echo "📊 Found {$parents->count()} potential parent annotations:\n\n";

foreach ($parents as $annotation) {
    $pageNum = $annotation->pdfPage->page_number ?? '?';
    echo "   ✓ [{$annotation->id}] {$annotation->label} (Page {$pageNum})\n";
    echo "      Type: {$annotation->annotation_type}\n";
    echo "      Room ID: {$annotation->room_id}\n";
    echo "      PDF Page: {$annotation->pdf_page_id}\n\n";
}

// Build the options array as it would be in the Select dropdown
echo "📋 Dropdown Options Array:\n\n";
$options = $parents->mapWithKeys(function ($annotation) {
    $pageNumber = $annotation->pdfPage->page_number ?? '?';
    return [$annotation->id => $annotation->label . ' (Page ' . $pageNumber . ')'];
})->toArray();

foreach ($options as $id => $label) {
    echo "   [{$id}] => \"{$label}\"\n";
}

echo "\n";

// Check if K1 specifically exists
$k1Annotation = $parents->first(function ($a) {
    return stripos($a->label, 'K1') !== false;
});

if ($k1Annotation) {
    echo "✅ K1 annotation found!\n";
    echo "   ID: {$k1Annotation->id}\n";
    echo "   Label: {$k1Annotation->label}\n";
    echo "   Dropdown display: {$k1Annotation->label} (Page {$k1Annotation->pdfPage->page_number})\n";
} else {
    echo "❌ K1 annotation NOT found\n";
    echo "   Available room annotations:\n";
    foreach ($parents as $p) {
        echo "   - {$p->label}\n";
    }
}

echo "\n";
