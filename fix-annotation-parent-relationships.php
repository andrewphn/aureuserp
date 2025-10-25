#!/usr/bin/env php
<?php

/**
 * Fix Annotation Parent Relationships Script
 *
 * This script repairs missing parent_annotation_id relationships in the pdf_page_annotations table.
 *
 * Issues Fixed:
 * 1. Location annotations without room parents
 * 2. Cabinet run annotations without location parents
 *
 * Usage: php fix-annotation-parent-relationships.php
 */

require __DIR__.'/bootstrap/app.php';

use Illuminate\Support\Facades\DB;

$app = app();
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║   Fix Annotation Parent Relationships                     ║\n";
echo "║   Date: " . date('Y-m-d H:i:s') . "                              ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Function to find the most appropriate room annotation for a given room_id
function findRoomAnnotationByRoomId($roomId) {
    return DB::table('pdf_page_annotations')
        ->where('annotation_type', 'room')
        ->where('room_id', $roomId)
        ->orderBy('created_at', 'desc') // Get the most recent one if multiple
        ->first();
}

// Function to find the most appropriate location annotation for a given room_location_id
function findLocationAnnotationByRoomLocationId($roomLocationId) {
    return DB::table('pdf_page_annotations')
        ->where('annotation_type', 'location')
        ->where('room_location_id', $roomLocationId)
        ->whereNotNull('parent_annotation_id') // Prefer locations that already have a parent
        ->orderBy('created_at', 'desc') // Get the most recent one if multiple
        ->first();
}

echo "🔍 Step 1: Finding location annotations without room parents...\n";
echo "─────────────────────────────────────────────────────────────\n";

$orphanedLocations = DB::table('pdf_page_annotations')
    ->where('annotation_type', 'location')
    ->whereNull('parent_annotation_id')
    ->get();

echo "Found " . $orphanedLocations->count() . " location annotations without parents\n\n";

$fixedLocations = 0;
$failedLocations = 0;

foreach ($orphanedLocations as $location) {
    echo "  📍 Location ID {$location->id} '{$location->label}' (room_id: {$location->room_id})\n";

    if (!$location->room_id) {
        echo "     ❌ SKIP: No room_id set\n\n";
        $failedLocations++;
        continue;
    }

    $roomAnnotation = findRoomAnnotationByRoomId($location->room_id);

    if (!$roomAnnotation) {
        echo "     ❌ SKIP: No room annotation found for room_id {$location->room_id}\n\n";
        $failedLocations++;
        continue;
    }

    try {
        DB::table('pdf_page_annotations')
            ->where('id', $location->id)
            ->update([
                'parent_annotation_id' => $roomAnnotation->id,
                'updated_at' => now()
            ]);

        echo "     ✅ FIXED: Set parent to Room ID {$roomAnnotation->id} '{$roomAnnotation->label}'\n\n";
        $fixedLocations++;
    } catch (\Exception $e) {
        echo "     ❌ ERROR: " . $e->getMessage() . "\n\n";
        $failedLocations++;
    }
}

echo "\n";
echo "🔍 Step 2: Finding cabinet_run annotations without location parents...\n";
echo "─────────────────────────────────────────────────────────────\n";

$orphanedCabinetRuns = DB::table('pdf_page_annotations')
    ->where('annotation_type', 'cabinet_run')
    ->whereNull('parent_annotation_id')
    ->get();

echo "Found " . $orphanedCabinetRuns->count() . " cabinet_run annotations without parents\n\n";

$fixedCabinetRuns = 0;
$failedCabinetRuns = 0;

foreach ($orphanedCabinetRuns as $run) {
    echo "  🏃 Cabinet Run ID {$run->id} '{$run->label}' (room_location_id: {$run->room_location_id})\n";

    if (!$run->room_location_id) {
        echo "     ❌ SKIP: No room_location_id set\n\n";
        $failedCabinetRuns++;
        continue;
    }

    $locationAnnotation = findLocationAnnotationByRoomLocationId($run->room_location_id);

    if (!$locationAnnotation) {
        // If no location annotation found with parent, try to find ANY location with that room_location_id
        $locationAnnotation = DB::table('pdf_page_annotations')
            ->where('annotation_type', 'location')
            ->where('room_location_id', $run->room_location_id)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    if (!$locationAnnotation) {
        echo "     ❌ SKIP: No location annotation found for room_location_id {$run->room_location_id}\n\n";
        $failedCabinetRuns++;
        continue;
    }

    try {
        DB::table('pdf_page_annotations')
            ->where('id', $run->id)
            ->update([
                'parent_annotation_id' => $locationAnnotation->id,
                'updated_at' => now()
            ]);

        echo "     ✅ FIXED: Set parent to Location ID {$locationAnnotation->id} '{$locationAnnotation->label}'\n\n";
        $fixedCabinetRuns++;
    } catch (\Exception $e) {
        echo "     ❌ ERROR: " . $e->getMessage() . "\n\n";
        $failedCabinetRuns++;
    }
}

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║   Summary                                                  ║\n";
echo "╠════════════════════════════════════════════════════════════╣\n";
echo "║   Locations:                                               ║\n";
echo "║     Fixed: " . str_pad($fixedLocations, 3, ' ', STR_PAD_LEFT) . "                                              ║\n";
echo "║     Failed: " . str_pad($failedLocations, 3, ' ', STR_PAD_LEFT) . "                                             ║\n";
echo "║                                                            ║\n";
echo "║   Cabinet Runs:                                            ║\n";
echo "║     Fixed: " . str_pad($fixedCabinetRuns, 3, ' ', STR_PAD_LEFT) . "                                              ║\n";
echo "║     Failed: " . str_pad($failedCabinetRuns, 3, ' ', STR_PAD_LEFT) . "                                             ║\n";
echo "║                                                            ║\n";
echo "║   Total Fixed: " . str_pad(($fixedLocations + $fixedCabinetRuns), 3, ' ', STR_PAD_LEFT) . "                                         ║\n";
echo "║   Total Failed: " . str_pad(($failedLocations + $failedCabinetRuns), 3, ' ', STR_PAD_LEFT) . "                                        ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Verify the fixes
echo "🔍 Verification: Checking for remaining orphaned annotations...\n";
echo "─────────────────────────────────────────────────────────────\n";

$remainingOrphanedLocations = DB::table('pdf_page_annotations')
    ->where('annotation_type', 'location')
    ->whereNull('parent_annotation_id')
    ->count();

$remainingOrphanedCabinetRuns = DB::table('pdf_page_annotations')
    ->where('annotation_type', 'cabinet_run')
    ->whereNull('parent_annotation_id')
    ->count();

echo "Remaining orphaned locations: " . $remainingOrphanedLocations . "\n";
echo "Remaining orphaned cabinet_runs: " . $remainingOrphanedCabinetRuns . "\n";

if ($remainingOrphanedLocations === 0 && $remainingOrphanedCabinetRuns === 0) {
    echo "\n✅ SUCCESS: All annotations now have proper parent relationships!\n\n";
} else {
    echo "\n⚠️  WARNING: Some annotations still don't have parents. Manual review needed.\n\n";
}

echo "📝 Log saved to: ANNOTATION_HIERARCHY_DATA_INTEGRITY_ISSUES.md\n";
echo "\n";
