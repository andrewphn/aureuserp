<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PdfDocument;
use Illuminate\Support\Facades\Storage;

// Get all PDFs without page count
$pdfs = PdfDocument::whereNull('page_count')->get();

echo "Found " . $pdfs->count() . " PDFs without page count\n";

foreach ($pdfs as $pdf) {
    echo "Processing PDF ID {$pdf->id}: {$pdf->file_name}...";

    if (!Storage::disk('public')->exists($pdf->file_path)) {
        echo " FILE NOT FOUND\n";
        continue;
    }

    try {
        $fullPath = Storage::disk('public')->path($pdf->file_path);
        $parser = new \Smalot\PdfParser\Parser();
        $pdfDoc = $parser->parseFile($fullPath);
        $pages = $pdfDoc->getPages();
        $pageCount = count($pages);

        $pdf->page_count = $pageCount;
        $pdf->save();
        echo " Updated with {$pageCount} pages\n";
    } catch (\Exception $e) {
        echo " ERROR: {$e->getMessage()}\n";
    }
}

echo "\nDone!\n";
