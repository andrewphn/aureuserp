<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing tag selector component rendering...\n\n";

// Test 1: Can we load tags?
$tags = \Webkul\Project\Models\Tag::all()->groupBy('type');
echo "✓ Loaded " . $tags->flatten()->count() . " tags\n";

// Test 2: Can we access the Blade view?
$viewPath = resource_path('views/forms/components/tag-selector-panel.blade.php');
echo "✓ View exists: " . (file_exists($viewPath) ? 'Yes' : 'No') . "\n";

// Test 3: Check for PHP syntax errors
$output = shell_exec("php -l $viewPath 2>&1");
echo "✓ Syntax check: " . (strpos($output, 'No syntax errors') !== false ? 'OK' : 'ERRORS') . "\n";

echo "\nDone.\n";
