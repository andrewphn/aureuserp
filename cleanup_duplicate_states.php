<?php

/**
 * Cleanup Duplicate States Script
 * 
 * This script removes duplicate states from the database, keeping only the record
 * with the lowest ID for each unique name+country_id combination.
 * 
 * Before deleting, it updates all foreign key references to point to the kept record.
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\Support\Models\State;

echo "Starting duplicate states cleanup...\n\n";

// Find all duplicate states
$duplicates = DB::select("
    SELECT name, country_id, COUNT(*) as count, GROUP_CONCAT(id ORDER BY id) as ids
    FROM states
    GROUP BY name, country_id
    HAVING count > 1
    ORDER BY count DESC
");

echo "Found " . count($duplicates) . " groups of duplicate states\n\n";

$totalDeleted = 0;
$totalUpdated = 0;

// Tables that reference state_id
$tablesWithStateId = [
    'companies' => 'state_id',
    'partners' => 'state_id',
    'addresses' => 'state_id',
    // Add more tables as needed
];

foreach ($duplicates as $dup) {
    $ids = explode(',', $dup->ids);
    $keepId = (int) $ids[0]; // Keep the lowest ID
    $deleteIds = array_map('intval', array_slice($ids, 1)); // Delete the rest
    
    echo "Processing: {$dup->name} (Country ID: {$dup->country_id})\n";
    echo "  Keeping ID: {$keepId}\n";
    echo "  Deleting IDs: " . implode(', ', $deleteIds) . "\n";
    
    // Update all foreign key references to point to the kept record
    foreach ($tablesWithStateId as $table => $column) {
        if (!Schema::hasTable($table)) {
            continue;
        }
        
        foreach ($deleteIds as $deleteId) {
            $updated = DB::table($table)
                ->where($column, $deleteId)
                ->update([$column => $keepId]);
            
            if ($updated > 0) {
                echo "  Updated {$updated} records in {$table} (from state_id {$deleteId} to {$keepId})\n";
                $totalUpdated += $updated;
            }
        }
    }
    
    // Delete duplicate states
    $deleted = DB::table('states')
        ->whereIn('id', $deleteIds)
        ->delete();
    
    echo "  Deleted {$deleted} duplicate state records\n\n";
    $totalDeleted += $deleted;
}

echo "\n=== Cleanup Summary ===\n";
echo "Total duplicate groups processed: " . count($duplicates) . "\n";
echo "Total records updated: {$totalUpdated}\n";
echo "Total duplicate states deleted: {$totalDeleted}\n";
echo "\nCleanup complete!\n";
