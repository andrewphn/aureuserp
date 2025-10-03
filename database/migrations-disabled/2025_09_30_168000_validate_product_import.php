<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        echo "\n";
        echo "╔═══════════════════════════════════════════════════════════════════════════╗\n";
        echo "║                    PRODUCT IMPORT VALIDATION REPORT                       ║\n";
        echo "╚═══════════════════════════════════════════════════════════════════════════╝\n";
        echo "\n";

        $allValidationsPassed = true;

        // ========== SOURCE COUNTS ==========
        echo "┌─────────────────────────────────────────────────────────────────────────┐\n";
        echo "│ 1. IMPORT SOURCE COUNTS                                                 │\n";
        echo "└─────────────────────────────────────────────────────────────────────────┘\n";

        $amazonCount = DB::table('temp_amazon_mapping')->count();
        $migrationCount = DB::table('temp_migration_mapping')->count();
        $totalExpected = $amazonCount + $migrationCount;

        echo "  Amazon products:     {$amazonCount}\n";
        echo "  Migration products:  {$migrationCount}\n";
        echo "  Total expected:      {$totalExpected}\n";
        echo "\n";

        // ========== PRODUCT VALIDATION ==========
        echo "┌─────────────────────────────────────────────────────────────────────────┐\n";
        echo "│ 2. PRODUCT VALIDATION                                                   │\n";
        echo "└─────────────────────────────────────────────────────────────────────────┘\n";

        $amazonProductIds = DB::table('temp_amazon_mapping')->pluck('product_id');
        $migrationProductIds = DB::table('temp_migration_mapping')->pluck('product_id');
        $allProductIds = $amazonProductIds->merge($migrationProductIds);

        $productsExist = DB::table('products_products')->whereIn('id', $allProductIds)->count();

        echo "  Products created:    {$productsExist} / {$totalExpected} ";
        if ($productsExist === $totalExpected) {
            echo "✓\n";
        } else {
            echo "✗ MISMATCH!\n";
            $allValidationsPassed = false;
        }
        echo "\n";

        // ========== VENDOR PRICING VALIDATION ==========
        echo "┌─────────────────────────────────────────────────────────────────────────┐\n";
        echo "│ 3. VENDOR PRICING VALIDATION                                            │\n";
        echo "└─────────────────────────────────────────────────────────────────────────┘\n";

        $vendorPricingCount = DB::table('products_product_suppliers')
            ->whereIn('product_id', $allProductIds)
            ->count();

        echo "  Vendor pricing:      {$vendorPricingCount} / {$totalExpected} ";
        if ($vendorPricingCount === $totalExpected) {
            echo "✓\n";
        } else {
            echo "✗ MISSING!\n";
            $allValidationsPassed = false;

            // Find products without vendor pricing
            $missingPricing = DB::select("
                SELECT p.id, p.name
                FROM products_products p
                LEFT JOIN products_product_suppliers pps ON p.id = pps.product_id
                WHERE p.id IN (" . $allProductIds->implode(',') . ")
                AND pps.id IS NULL
                LIMIT 5
            ");

            if (!empty($missingPricing)) {
                echo "\n  Missing vendor pricing for:\n";
                foreach ($missingPricing as $product) {
                    echo "    - Product {$product->id}: {$product->name}\n";
                }
            }
        }
        echo "\n";

        // ========== CATEGORY VALIDATION ==========
        echo "┌─────────────────────────────────────────────────────────────────────────┐\n";
        echo "│ 4. CATEGORY VALIDATION                                                  │\n";
        echo "└─────────────────────────────────────────────────────────────────────────┘\n";

        $withCategories = DB::table('products_products')
            ->whereIn('id', $allProductIds)
            ->whereNotNull('category_id')
            ->count();

        echo "  With categories:     {$withCategories} / {$totalExpected} ";
        if ($withCategories === $totalExpected) {
            echo "✓\n";
        } else {
            echo "✗ MISSING!\n";
            $allValidationsPassed = false;
        }
        echo "\n";

        // ========== TAG VALIDATION ==========
        echo "┌─────────────────────────────────────────────────────────────────────────┐\n";
        echo "│ 5. TAG VALIDATION                                                       │\n";
        echo "└─────────────────────────────────────────────────────────────────────────┘\n";

        $withTags = DB::table('products_product_tag')
            ->whereIn('product_id', $allProductIds)
            ->distinct('product_id')
            ->count('product_id');

        $totalTagLinks = DB::table('products_product_tag')
            ->whereIn('product_id', $allProductIds)
            ->count();

        echo "  Products with tags:  {$withTags} / {$totalExpected} ";
        if ($withTags === $totalExpected) {
            echo "✓\n";
        } else {
            echo "✗ MISSING!\n";
            $allValidationsPassed = false;
        }

        echo "  Total tag links:     {$totalTagLinks}\n";
        echo "  Avg tags/product:    " . round($totalTagLinks / max($withTags, 1), 1) . "\n";
        echo "\n";

        // ========== VENDOR URL VALIDATION ==========
        echo "┌─────────────────────────────────────────────────────────────────────────┐\n";
        echo "│ 6. VENDOR URL VALIDATION                                                │\n";
        echo "└─────────────────────────────────────────────────────────────────────────┘\n";

        $withUrls = DB::table('products_product_suppliers')
            ->whereIn('product_id', $allProductIds)
            ->whereNotNull('vendor_url')
            ->count();

        echo "  With vendor URLs:    {$withUrls} / {$totalExpected} ";
        if ($withUrls >= ($totalExpected * 0.8)) { // 80% threshold (Richelieu + Amazon should have URLs)
            echo "✓\n";
        } else {
            echo "⚠ SOME MISSING\n";
        }
        echo "\n";

        // ========== BREAKDOWN BY VENDOR ==========
        echo "┌─────────────────────────────────────────────────────────────────────────┐\n";
        echo "│ 7. BREAKDOWN BY VENDOR                                                  │\n";
        echo "└─────────────────────────────────────────────────────────────────────────┘\n";

        $vendorBreakdown = DB::select("
            SELECT
                pp.name as vendor_name,
                COUNT(pps.id) as product_count,
                c.name as currency_name
            FROM products_product_suppliers pps
            JOIN partners_partners pp ON pps.partner_id = pp.id
            JOIN currencies c ON pps.currency_id = c.id
            WHERE pps.product_id IN (" . $allProductIds->implode(',') . ")
            GROUP BY pp.name, c.name
            ORDER BY product_count DESC
        ");

        foreach ($vendorBreakdown as $vendor) {
            echo sprintf("  %-25s %3d products (%s)\n", $vendor->vendor_name, $vendor->product_count, $vendor->currency_name);
        }
        echo "\n";

        // ========== BREAKDOWN BY CATEGORY ==========
        echo "┌─────────────────────────────────────────────────────────────────────────┐\n";
        echo "│ 8. BREAKDOWN BY CATEGORY                                                │\n";
        echo "└─────────────────────────────────────────────────────────────────────────┘\n";

        $categoryBreakdown = DB::select("
            SELECT
                pc.full_name as category_name,
                COUNT(p.id) as product_count
            FROM products_products p
            JOIN products_categories pc ON p.category_id = pc.id
            WHERE p.id IN (" . $allProductIds->implode(',') . ")
            GROUP BY pc.full_name
            ORDER BY product_count DESC
            LIMIT 15
        ");

        foreach ($categoryBreakdown as $category) {
            echo sprintf("  %-50s %3d products\n", $category->category_name, $category->product_count);
        }
        echo "\n";

        // ========== TOP TAGS ==========
        echo "┌─────────────────────────────────────────────────────────────────────────┐\n";
        echo "│ 9. TOP TAGS                                                             │\n";
        echo "└─────────────────────────────────────────────────────────────────────────┘\n";

        $tagBreakdown = DB::select("
            SELECT
                pt.name as tag_name,
                COUNT(ppt.product_id) as product_count
            FROM products_product_tag ppt
            JOIN products_tags pt ON ppt.tag_id = pt.id
            WHERE ppt.product_id IN (" . $allProductIds->implode(',') . ")
            GROUP BY pt.name
            ORDER BY product_count DESC
            LIMIT 15
        ");

        foreach ($tagBreakdown as $tag) {
            echo sprintf("  %-30s %3d products\n", $tag->tag_name, $tag->product_count);
        }
        echo "\n";

        // ========== ORPHANED RECORDS CHECK ==========
        echo "┌─────────────────────────────────────────────────────────────────────────┐\n";
        echo "│ 10. ORPHANED RECORDS CHECK                                              │\n";
        echo "└─────────────────────────────────────────────────────────────────────────┘\n";

        $orphanedPricing = DB::table('products_product_suppliers')
            ->whereNotIn('product_id', function($query) {
                $query->select('id')->from('products_products');
            })
            ->whereNotNull('product_id')
            ->count();

        $orphanedTags = DB::table('products_product_tag')
            ->whereNotIn('product_id', function($query) {
                $query->select('id')->from('products_products');
            })
            ->count();

        echo "  Orphaned pricing:    {$orphanedPricing} ";
        echo ($orphanedPricing === 0) ? "✓\n" : "✗\n";

        echo "  Orphaned tags:       {$orphanedTags} ";
        echo ($orphanedTags === 0) ? "✓\n" : "✗\n";

        if ($orphanedPricing > 0 || $orphanedTags > 0) {
            $allValidationsPassed = false;
        }
        echo "\n";

        // ========== FINAL SUMMARY ==========
        echo "╔═══════════════════════════════════════════════════════════════════════════╗\n";
        echo "║ VALIDATION SUMMARY                                                        ║\n";
        echo "╚═══════════════════════════════════════════════════════════════════════════╝\n";
        echo "\n";

        if ($allValidationsPassed) {
            echo "  ✓ ALL VALIDATIONS PASSED!\n\n";
            echo "  Cleaning up temporary mapping tables...\n";

            Schema::dropIfExists('temp_amazon_mapping');
            Schema::dropIfExists('temp_migration_mapping');

            echo "  ✓ Cleanup complete\n\n";
            echo "  Import successful! You can now review products in FilamentPHP admin.\n";
        } else {
            echo "  ✗ SOME VALIDATIONS FAILED\n\n";
            echo "  Please review the issues above.\n";
            echo "  Temporary mapping tables preserved for debugging.\n";
            echo "  Query temp_amazon_mapping and temp_migration_mapping for details.\n";
        }

        echo "\n";
        echo "╔═══════════════════════════════════════════════════════════════════════════╗\n";
        echo "║                           END OF REPORT                                   ║\n";
        echo "╚═══════════════════════════════════════════════════════════════════════════╝\n";
        echo "\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Validation migration doesn't need rollback
        // But we can recreate temp tables if they were dropped
        echo "Validation migration rollback - no action needed\n";
    }
};
