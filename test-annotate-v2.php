<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    $project = \Webkul\Project\Models\Project::find(1);
    echo "Project found: {$project->name}\n";
    echo "PDF Document ID: {$project->pdf_document_id}\n";
    
    if ($project->pdf_document_id) {
        $pdfPage = \Webkul\Project\Models\PdfPage::where('pdf_document_id', $project->pdf_document_id)
            ->where('page_number', 1)
            ->first();
        
        if ($pdfPage) {
            echo "PDF Page found: ID {$pdfPage->id}\n";
        } else {
            echo "No PDF page found for page number 1\n";
        }
    } else {
        echo "Project has no PDF document attached\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
