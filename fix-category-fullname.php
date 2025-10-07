#!/usr/bin/env php
<?php

/**
 * Fix: Category with null full_name causing product create page to fail
 *
 * Issue: The migration 2025_10_04_123502_create_tcs_cabinet_products_with_attributes.php
 * created "Woodwork Services" category but didn't set the full_name field.
 * The ProductResource Select component requires full_name and throws error when it's null.
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Fixing category with null full_name...\n\n";

// Find categories with null full_name
$categories = DB::table('products_categories')
    ->whereNull('full_name')
    ->get();

if ($categories->isEmpty()) {
    echo "✅ No categories with null full_name found.\n";
    exit(0);
}

echo "Found {$categories->count()} categor(ies) with null full_name:\n\n";

foreach ($categories as $category) {
    echo "  ID: {$category->id}\n";
    echo "  Name: {$category->name}\n";

    // Set full_name to match name
    DB::table('products_categories')
        ->where('id', $category->id)
        ->update(['full_name' => $category->name]);

    echo "  ✅ Updated full_name to: {$category->name}\n\n";
}

echo "✅ All categories fixed!\n";
echo "\nYou can now access the product create page.\n";
