<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use App\Services\FooterFieldRegistry;
use App\Services\FooterPreferenceService;
use Webkul\Security\Models\User;

echo "\n" . str_repeat("=", 80) . "\n";
echo "MANAGEFOOTER PAGE DEBUG TEST\n";
echo str_repeat("=", 80) . "\n\n";

// Test 1: Check services
echo "TEST 1: Service Resolution\n";
echo str_repeat("-", 80) . "\n";
try {
    $registry = app(FooterFieldRegistry::class);
    $service = app(FooterPreferenceService::class);
    echo "✓ Services resolved\n";
} catch (\Exception $e) {
    echo "✗ Service resolution failed: " . $e->getMessage() . "\n";
    exit(1);
}
echo "\n";

// Test 2: Get user
echo "TEST 2: Get User\n";
echo str_repeat("-", 80) . "\n";
try {
    $user = User::first();
    if (!$user) {
        echo "✗ No user found\n";
        exit(1);
    }
    echo "✓ User found: {$user->name} (ID: {$user->id})\n";
} catch (\Exception $e) {
    echo "✗ User fetch failed: " . $e->getMessage() . "\n";
    exit(1);
}
echo "\n";

// Test 3: Get all user preferences
echo "TEST 3: Get User Preferences\n";
echo str_repeat("-", 80) . "\n";
try {
    $preferences = $service->getAllUserPreferences($user);
    echo "✓ Preferences loaded\n";
    foreach (['project', 'sale', 'inventory', 'production'] as $context) {
        $min = count($preferences[$context]['minimized_fields'] ?? []);
        $exp = count($preferences[$context]['expanded_fields'] ?? []);
        echo "  {$context}: {$min} minimized, {$exp} expanded\n";
    }
} catch (\Exception $e) {
    echo "✗ Preferences fetch failed: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}
echo "\n";

// Test 4: Get available fields
echo "TEST 4: Get Available Fields\n";
echo str_repeat("-", 80) . "\n";
try {
    foreach (['project', 'sale', 'inventory', 'production'] as $context) {
        $fields = $registry->getAvailableFields($context);
        echo "✓ {$context}: " . count($fields) . " fields\n";
    }
} catch (\Exception $e) {
    echo "✗ Get fields failed: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}
echo "\n";

// Test 5: Simulate page data structure
echo "TEST 5: Simulate Page Data Structure\n";
echo str_repeat("-", 80) . "\n";
try {
    $data = [
        'project_minimized' => $preferences['project']['minimized_fields'] ?? [],
        'project_expanded' => $preferences['project']['expanded_fields'] ?? [],
        'sale_minimized' => $preferences['sale']['minimized_fields'] ?? [],
        'sale_expanded' => $preferences['sale']['expanded_fields'] ?? [],
        'inventory_minimized' => $preferences['inventory']['minimized_fields'] ?? [],
        'inventory_expanded' => $preferences['inventory']['expanded_fields'] ?? [],
        'production_minimized' => $preferences['production']['minimized_fields'] ?? [],
        'production_expanded' => $preferences['production']['expanded_fields'] ?? [],
    ];
    echo "✓ Data structure created\n";
    echo "  Total fields: " . array_sum(array_map('count', $data)) . "\n";
} catch (\Exception $e) {
    echo "✗ Data structure failed: " . $e->getMessage() . "\n";
    exit(1);
}
echo "\n";

// Test 6: Try instantiating ManageFooter
echo "TEST 6: Instantiate ManageFooter Page\n";
echo str_repeat("-", 80) . "\n";
try {
    $page = new \App\Filament\Pages\ManageFooter();
    echo "✓ ManageFooter instantiated\n";
} catch (\Exception $e) {
    echo "✗ Instantiation failed: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}
echo "\n";

echo str_repeat("=", 80) . "\n";
echo "ALL TESTS PASSED\n";
echo "ManageFooter page should work. Issue might be in Livewire rendering.\n";
echo str_repeat("=", 80) . "\n\n";
