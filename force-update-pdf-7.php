<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PdfDocument;
use Illuminate\Support\Facades\Storage;

$pdf = PdfDocument::find(7);
if (!$pdf) {
    echo "PDF ID 7 not found\n";
    exit(1);
}

echo "Current page count: " . ($pdf->page_count ?? 'NULL') . "\n";

$fullPath = Storage::disk('public')->path($pdf->file_path);
echo "File path: $fullPath\n";

if (!file_exists($fullPath)) {
    echo "File does not exist!\n";
    exit(1);
}

echo "File exists, size: " . filesize($fullPath) . " bytes\n";

try {
    $parser = new \Smalot\PdfParser\Parser();
    $pdfDoc = $parser->parseFile($fullPath);
    $pages = $pdfDoc->getPages();
    $pageCount = count($pages);

    echo "Extracted page count: $pageCount\n";

    $pdf->page_count = $pageCount;
    $pdf->save();

    echo "Updated PDF with $pageCount pages\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
