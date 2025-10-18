<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$project = \Webkul\Project\Models\Project::find(1);
echo "Project: {$project->name}\n\n";

// Check for PDFs using the morphMany relationship
$pdfs = $project->pdfDocuments;
echo "PDF Documents attached: {$pdfs->count()}\n\n";

foreach ($pdfs as $pdf) {
    echo "PDF ID: {$pdf->id}\n";
    echo "File Name: {$pdf->file_name}\n";
    echo "File Path: {$pdf->file_path}\n";
    echo "Page Count: {$pdf->page_count}\n";
    echo "---\n";
}
