<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use App\Filament\Pages\ManageFooter;

echo "Testing ManageFooter page...\n";

try {
    // Try to instantiate the class
    $page = new ManageFooter();
    echo "✓ ManageFooter instantiated successfully\n";
} catch (\Exception $e) {
    echo "✗ Error instantiating ManageFooter: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "  Trace:\n";
    foreach ($e->getTrace() as $i => $trace) {
        echo "    #{$i} ";
        if (isset($trace['file'])) {
            echo $trace['file'] . "(" . $trace['line'] . "): ";
        }
        if (isset($trace['class'])) {
            echo $trace['class'] . $trace['type'];
        }
        if (isset($trace['function'])) {
            echo $trace['function'] . "()";
        }
        echo "\n";
        if ($i >= 5) break; // Limit trace output
    }
}
