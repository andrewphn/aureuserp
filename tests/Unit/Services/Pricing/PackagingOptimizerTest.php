<?php

namespace Tests\Unit\Services\Pricing;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\Sale\Services\Pricing\PackagingOptimizer;
use Webkul\Product\Models\Packaging;
use Illuminate\Support\Facades\DB;

/**
 * Unit tests for PackagingOptimizer
 *
 * @note These tests verify the packaging optimization algorithm.
 *       Currently the Packaging table has no data, but the logic is tested
 *       using test data created via direct DB inserts.
 */
class PackagingOptimizerTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Helper to create a minimal product record for testing
     */
    private function createProduct(): int
    {
        // Query for existing UOM and Category (should exist from migrations)
        $uomId = DB::table('unit_of_measures')->first()?->id ?? 1;
        $categoryId = DB::table('products_categories')->first()?->id ?? 1;

        return DB::table('products_products')->insertGetId([
            'type' => 'storable',
            'name' => 'Test Product ' . uniqid(),
            'uom_id' => $uomId,
            'uom_po_id' => $uomId,
            'category_id' => $categoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Helper to create a packaging record
     */
    private function createPackaging(int $productId, int $qty): int
    {
        return DB::table('products_packagings')->insertGetId([
            'product_id' => $productId,
            'qty' => $qty,
            'name' => "Package of {$qty}",
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Test returns null when no packagings exist for product
     */
    public function test_returns_null_when_no_packagings_exist()
    {
        $productId = $this->createProduct();

        $result = PackagingOptimizer::findOptimal($productId, 100);
        $this->assertNull($result);
    }

    /**
     * Test returns null when quantity is zero
     */
    public function test_returns_null_when_quantity_is_zero()
    {
        $productId = $this->createProduct();
        $this->createPackaging($productId, 10);

        $result = PackagingOptimizer::findOptimal($productId, 0);
        $this->assertNull($result);
    }

    /**
     * Test returns null when no packaging evenly divides quantity
     */
    public function test_returns_null_when_no_packaging_evenly_divides()
    {
        $productId = $this->createProduct();

        // Create packagings of 10 and 25
        $this->createPackaging($productId, 10);
        $this->createPackaging($productId, 25);

        // Request 37 units - neither 10 nor 25 divides evenly
        $result = PackagingOptimizer::findOptimal($productId, 37);
        $this->assertNull($result);
    }

    /**
     * Test returns optimal packaging when single option exists
     */
    public function test_returns_optimal_packaging_single_option()
    {
        $productId = $this->createProduct();
        $packagingId = $this->createPackaging($productId, 10);

        $result = PackagingOptimizer::findOptimal($productId, 50);

        $this->assertIsArray($result);
        $this->assertEquals($packagingId, $result['packaging_id']);
        $this->assertEquals(5.0, $result['packaging_qty']); // 50 / 10 = 5
    }

    /**
     * Test prioritizes larger packaging when multiple options exist
     */
    public function test_prioritizes_larger_packaging()
    {
        $productId = $this->createProduct();

        // Create multiple packaging options
        $small = $this->createPackaging($productId, 10);
        $medium = $this->createPackaging($productId, 25);
        $large = $this->createPackaging($productId, 50);

        // Request 100 units - all three divide evenly (10x10, 4x25, 2x50)
        // Should return the largest (50-unit packaging)
        $result = PackagingOptimizer::findOptimal($productId, 100);

        $this->assertIsArray($result);
        $this->assertEquals($large, $result['packaging_id']);
        $this->assertEquals(2.0, $result['packaging_qty']); // 100 / 50 = 2
    }

    /**
     * Test handles fractional packaging quantities correctly
     */
    public function test_handles_fractional_quantities()
    {
        $productId = $this->createProduct();
        $this->createPackaging($productId, 3);

        $result = PackagingOptimizer::findOptimal($productId, 10);

        // 10 / 3 = 3.333... which doesn't divide evenly
        // Even though we have a packaging, it shouldn't return a result
        $this->assertNull($result);
    }

    /**
     * Test verifies return structure
     */
    public function test_return_structure()
    {
        $productId = $this->createProduct();
        $packagingId = $this->createPackaging($productId, 12);

        $result = PackagingOptimizer::findOptimal($productId, 60);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('packaging_id', $result);
        $this->assertArrayHasKey('packaging_qty', $result);
        $this->assertEquals($packagingId, $result['packaging_id']);
        $this->assertEquals(5.0, $result['packaging_qty']);
    }

    /**
     * Test real-world scenario: ordering cabinets in bulk
     */
    public function test_real_world_cabinet_ordering()
    {
        $productId = $this->createProduct();

        // Supplier offers packages of 6, 12, and 24 doors
        $sixPack = $this->createPackaging($productId, 6);
        $dozen = $this->createPackaging($productId, 12);
        $case = $this->createPackaging($productId, 24);

        // Customer orders 48 doors
        // Should recommend 2 cases of 24 (most efficient)
        $result = PackagingOptimizer::findOptimal($productId, 48);

        $this->assertEquals($case, $result['packaging_id']);
        $this->assertEquals(2.0, $result['packaging_qty']);

        // Customer orders 36 doors
        // Should recommend 3 dozens (24 doesn't divide evenly)
        $result = PackagingOptimizer::findOptimal($productId, 36);

        $this->assertEquals($dozen, $result['packaging_id']);
        $this->assertEquals(3.0, $result['packaging_qty']);

        // Customer orders 18 doors
        // Should recommend 3 six-packs (neither 12 nor 24 divide evenly)
        $result = PackagingOptimizer::findOptimal($productId, 18);

        $this->assertEquals($sixPack, $result['packaging_id']);
        $this->assertEquals(3.0, $result['packaging_qty']);

        // Customer orders 19 doors
        // No packaging divides evenly (6, 12, 24 don't divide 19) - returns null
        $result = PackagingOptimizer::findOptimal($productId, 19);

        $this->assertNull($result);
    }
}
