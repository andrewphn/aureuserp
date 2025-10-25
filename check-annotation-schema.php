<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n📋 Checking pdf_page_annotations table schema\n";
echo str_repeat('=', 80) . "\n\n";

$columns = DB::select("DESCRIBE pdf_page_annotations");

foreach ($columns as $column) {
    echo sprintf("%-30s %-20s %-10s\n",
        $column->Field,
        $column->Type,
        $column->Null === 'YES' ? 'Nullable' : 'Required'
    );
}
