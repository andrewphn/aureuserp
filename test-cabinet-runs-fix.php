<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing cabinetRuns() Fix ===\n\n";

try {
    // Get project
    $project = \Webkul\Project\Models\Project::find(1);

    if (!$project) {
        echo "❌ Project ID 1 not found\n";
        exit(1);
    }

    echo "✓ Project: {$project->name} (ID: {$project->id})\n\n";

    // Test 1: Call cabinetRuns() method
    echo "Test 1: Calling cabinetRuns() method...\n";
    $query = $project->cabinetRuns();
    echo "✓ Query builder returned: " . get_class($query) . "\n";

    // Test 2: Chain select() method
    echo "\nTest 2: Chaining ->select() method...\n";
    $selectQuery = $project->cabinetRuns()->select('id', 'name', 'run_type');
    echo "✓ Select query created successfully\n";

    // Test 3: Chain orderBy() method
    echo "\nTest 3: Chaining ->orderBy() method...\n";
    $orderQuery = $project->cabinetRuns()->orderBy('name');
    echo "✓ OrderBy query created successfully\n";

    // Test 4: Execute query with get()
    echo "\nTest 4: Executing query with ->get()...\n";
    $cabinetRuns = $project->cabinetRuns()->get();
    echo "✓ Query executed successfully\n";
    echo "✓ Found {$cabinetRuns->count()} cabinet runs\n";

    if ($cabinetRuns->count() > 0) {
        echo "\nCabinet Runs:\n";
        foreach ($cabinetRuns as $run) {
            echo "  - ID: {$run->id} | Name: {$run->name} | Type: {$run->run_type}\n";
        }
    }

    // Test 5: Test the API controller code pattern
    echo "\nTest 5: Testing API controller code pattern...\n";
    $apiPattern = $project->cabinetRuns()
        ->select('projects_cabinet_runs.id', 'projects_cabinet_runs.name', 'projects_cabinet_runs.run_type')
        ->orderBy('projects_cabinet_runs.name')
        ->get();
    echo "✓ API pattern works! Found {$apiPattern->count()} cabinet runs\n";

    echo "\n=== All Tests Passed! ✅ ===\n";

} catch (\Exception $e) {
    echo "\n❌ ERROR: {$e->getMessage()}\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString();
    exit(1);
}
