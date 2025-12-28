<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== Cabinet/Spec Related Tables ===\n\n";

$tables = DB::select('SHOW TABLES');
$cabinetTables = [];

foreach ($tables as $table) {
    $name = array_values((array)$table)[0];
    if (stripos($name, 'cabinet') !== false ||
        stripos($name, 'spec') !== false ||
        stripos($name, 'room') !== false ||
        stripos($name, 'run') !== false ||
        stripos($name, 'section') !== false ||
        stripos($name, 'component') !== false ||
        stripos($name, 'location') !== false) {
        $cabinetTables[] = $name;
    }
}

foreach ($cabinetTables as $table) {
    echo "TABLE: $table\n";
    $columns = Schema::getColumnListing($table);
    echo "Columns: " . implode(', ', $columns) . "\n";

    // Get sample data
    $count = DB::table($table)->count();
    echo "Row count: $count\n";

    if ($count > 0) {
        $sample = DB::table($table)->first();
        echo "Sample: " . json_encode($sample, JSON_PRETTY_PRINT) . "\n";
    }
    echo "\n---\n\n";
}

// Also check projects_project_drafts for form_data structure
echo "=== Project Drafts Form Data Structure ===\n";
$draft = DB::table('projects_project_drafts')->orderBy('id', 'desc')->first();
if ($draft && $draft->form_data) {
    $formData = json_decode($draft->form_data, true);
    echo "Form data keys: " . implode(', ', array_keys($formData ?? [])) . "\n";
    if (isset($formData['cabinet_specs'])) {
        echo "Cabinet specs structure:\n";
        print_r($formData['cabinet_specs']);
    }
}
