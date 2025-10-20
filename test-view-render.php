<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $view = view('webkul-project::filament.resources.project-resource.pages.annotate-pdf-v2', [
        'pdfUrl' => 'test.pdf',
        'pdfPage' => null,
        'pageNumber' => 1,
        'projectId' => 1,
        'totalPages' => 1,
        'pageType' => null,
        'pageMap' => []
    ]);
    
    echo "View found and can be rendered!\n";
    echo "View path: " . $view->getPath() . "\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
