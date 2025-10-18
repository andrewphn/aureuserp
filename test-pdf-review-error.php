<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    echo "Testing ReviewPdfAndPrice page...\n\n";

    // Get project 1
    $project = \Webkul\Project\Models\Project::find(1);
    if (!$project) {
        die("Project 1 not found\n");
    }
    echo "✓ Project found: {$project->name}\n";

    // Get PDF 1
    $pdf = \App\Models\PdfDocument::find(1);
    if (!$pdf) {
        die("PDF 1 not found\n");
    }
    echo "✓ PDF found: {$pdf->file_name}\n";

    // Try to instantiate the page class
    echo "\nTrying to instantiate ReviewPdfAndPrice...\n";
    $pageClass = \Webkul\Project\Filament\Resources\ProjectResource\Pages\ReviewPdfAndPrice::class;
    echo "✓ Class exists: {$pageClass}\n";

    // Try to get the form schema
    echo "\nTrying to build form schema...\n";
    $page = new $pageClass();

    // Set record and pdf
    $reflection = new ReflectionClass($page);
    $recordProperty = $reflection->getProperty('record');
    $recordProperty->setAccessible(true);
    $recordProperty->setValue($page, $project);

    $pdfProperty = $reflection->getProperty('pdf');
    $pdfProperty->setAccessible(true);
    $pdfProperty->setValue($page, 1);

    $pdfDocProperty = $reflection->getProperty('pdfDocument');
    $pdfDocProperty->setAccessible(true);
    $pdfDocProperty->setValue($page, $pdf);

    echo "✓ Page properties set\n";

    // Try to build the form
    echo "\nBuilding form schema...\n";
    $form = \Filament\Forms\Form::make($page, 'form');
    $schema = $page->form($form);

    echo "✓ Form schema built successfully!\n";

} catch (\Throwable $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nFile: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}
