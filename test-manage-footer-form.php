<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use App\Services\FooterFieldRegistry;
use Webkul\Security\Models\User;

echo "\n" . str_repeat("=", 80) . "\n";
echo "MANAGEFOOTER FORM TEST\n";
echo str_repeat("=", 80) . "\n\n";

// Test 1: Check Tabs import
echo "TEST 1: Check if Tabs class exists\n";
echo str_repeat("-", 80) . "\n";
try {
    $tabsClass = new ReflectionClass(\Filament\Schemas\Components\Tabs::class);
    echo "✓ Filament\Schemas\Components\Tabs exists\n";
    echo "  Location: " . $tabsClass->getFileName() . "\n";
} catch (\Exception $e) {
    echo "✗ Tabs class not found: " . $e->getMessage() . "\n";
    echo "  Trying Forms\\Components\\Tabs...\n";
    try {
        $tabsClass = new ReflectionClass(\Filament\Forms\Components\Tabs::class);
        echo "✓ Found at Filament\Forms\Components\Tabs\n";
        echo "  Location: " . $tabsClass->getFileName() . "\n";
    } catch (\Exception $e2) {
        echo "✗ Also not found: " . $e2->getMessage() . "\n";
    }
}
echo "\n";

// Test 2: Try to instantiate ManageFooter
echo "TEST 2: Instantiate ManageFooter Page\n";
echo str_repeat("-", 80) . "\n";
try {
    // Mock auth
    $user = User::first();
    auth()->login($user);

    $page = new \App\Filament\Pages\ManageFooter();
    echo "✓ ManageFooter instantiated\n";
} catch (\Exception $e) {
    echo "✗ Instantiation failed: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\n  Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
echo "\n";

// Test 3: Try to call form() method
echo "TEST 3: Call form() method\n";
echo str_repeat("-", 80) . "\n";
try {
    $schema = new \Filament\Schemas\Schema();
    $result = $page->form($schema);
    echo "✓ form() method called successfully\n";
    echo "  Returns: " . get_class($result) . "\n";
} catch (\Exception $e) {
    echo "✗ form() method failed: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\n  Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
echo "\n";

echo str_repeat("=", 80) . "\n";
echo "ALL TESTS PASSED\n";
echo str_repeat("=", 80) . "\n\n";
