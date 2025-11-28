<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Webkul\Project\Models\Project;

echo "=== Testing Project Dashboard ===\n\n";

$project = Project::with(['tags', 'cabinets', 'rooms.locations', 'orders', 'pdfDocuments'])
    ->find(9);

if (!$project) {
    echo "❌ Project #9 not found\n";
    exit(1);
}

echo "✅ Project loaded: {$project->name}\n\n";

// Test 1: Tags
echo "--- Testing Tags ---\n";
$tagCount = $project->tags->count();
echo "Tags count: {$tagCount}\n";
if ($tagCount > 0) {
    echo "Tags: " . $project->tags->pluck('name')->implode(', ') . "\n";
} else {
    echo "No tags assigned (this is OK)\n";
}
echo "\n";

// Test 2: Financial Data
echo "--- Testing Financial Calculations ---\n";
$quoted = $project->cabinets()
    ->selectRaw('SUM(unit_price_per_lf * linear_feet * quantity) as total')
    ->value('total') ?? 0;
$actual = $project->orders()->sum('amount_total') ?? 0;
$margin = $quoted > 0 ? (($quoted - $actual) / $quoted) * 100 : 0;

echo "Total Quoted: $" . number_format($quoted, 2) . "\n";
echo "Actual Costs: $" . number_format($actual, 2) . "\n";
echo "Profit Margin: " . number_format($margin, 1) . "%\n";
echo "\n";

// Test 3: Timeline Data
echo "--- Testing Timeline ---\n";
echo "Start Date: " . ($project->start_date ? $project->start_date->format('M d, Y') : 'Not set') . "\n";
echo "Target Completion: " . ($project->desired_completion_date ? $project->desired_completion_date->format('M d, Y') : 'Not set') . "\n";
if ($project->desired_completion_date) {
    $daysRemaining = now()->diffInDays($project->desired_completion_date, false);
    echo "Days Remaining: " . $daysRemaining . ($daysRemaining < 0 ? ' (OVERDUE)' : '') . "\n";
}
echo "\n";

// Test 4: Alerts
echo "--- Testing Alerts ---\n";
$roomsWithoutLocations = $project->rooms()->doesntHave('locations')->count();
$cabinetsWithoutPrice = $project->cabinets()
    ->where(function ($query) {
        $query->whereNull('unit_price_per_lf')
            ->orWhereNull('linear_feet')
            ->orWhereNull('quantity');
    })
    ->count();
$cabinetsWithoutDimensions = $project->cabinets()
    ->where(function ($query) {
        $query->whereNull('width_inches')
            ->orWhereNull('height_inches')
            ->orWhereNull('depth_inches');
    })
    ->count();

echo "Rooms without locations: {$roomsWithoutLocations}\n";
echo "Cabinets without pricing: {$cabinetsWithoutPrice}\n";
echo "Cabinets without dimensions: {$cabinetsWithoutDimensions}\n";

if ($roomsWithoutLocations == 0 && $cabinetsWithoutPrice == 0 && $cabinetsWithoutDimensions == 0) {
    echo "✅ No alerts - project is complete!\n";
}
echo "\n";

// Test 5: Room Hierarchy
echo "--- Testing Room Hierarchy ---\n";
$roomCount = $project->rooms->count();
$locationCount = $project->rooms->sum(fn($room) => $room->locations->count());
$cabinetCount = $project->cabinets->count();

echo "Rooms: {$roomCount}\n";
echo "Room Locations: {$locationCount}\n";
echo "Cabinets: {$cabinetCount}\n";
echo "\n";

// Test 6: Chatter
echo "--- Testing Chatter ---\n";
$hasChatter = method_exists($project, 'chatter');
echo "Chatter trait loaded: " . ($hasChatter ? "✅ Yes" : "❌ No") . "\n";
echo "\n";

echo "=== All Tests Complete ===\n";
echo "✅ Dashboard widgets should be working!\n";
echo "\nNow visit: http://aureuserp.test/admin/project/projects/9\n";
