<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    // Test if the view can be found
    $viewPath = 'webkul-project::filament.components.pdf-annotation-viewer-v3-overlay';

    echo "Testing view path: {$viewPath}\n";

    if (view()->exists($viewPath)) {
        echo "✅ View EXISTS\n";

        // Try to render it with test data
        $rendered = view($viewPath, [
            'pdfPageId' => 1,
            'pdfUrl' => 'test.pdf',
            'pageNumber' => 1,
            'projectId' => 1,
            'totalPages' => 1,
            'pageType' => null,
            'pageMap' => []
        ])->render();

        echo "✅ View RENDERED successfully\n";
        echo "Content length: " . strlen($rendered) . " bytes\n";
        echo "First 200 chars: " . substr($rendered, 0, 200) . "\n";
    } else {
        echo "❌ View NOT FOUND\n";

        // Check what views are registered
        echo "\nChecking view finder paths:\n";
        $finder = app('view')->getFinder();
        foreach ($finder->getPaths() as $path) {
            echo "  - {$path}\n";
        }

        echo "\nChecking view hints:\n";
        foreach ($finder->getHints() as $namespace => $paths) {
            echo "  {$namespace}:\n";
            foreach ($paths as $path) {
                echo "    - {$path}\n";
            }
        }
    }
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
