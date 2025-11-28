<?php

namespace Tests\Unit\Services\Pricing;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Webkul\Sale\Services\Pricing\VendorPriceCalculator;

/**
 * Unit tests for VendorPriceCalculator service
 *
 * @covers \Webkul\Sale\Services\Pricing\VendorPriceCalculator
 */
class VendorPriceCalculatorTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Helper to create a minimal product record for testing
     */
    private function createProduct(float $price = 100, ?float $cost = 60): int
    {
        $uomId = DB::table('unit_of_measures')->first()?->id ?? 1;
        $categoryId = DB::table('products_categories')->first()?->id ?? 1;

        return DB::table('products_products')->insertGetId([
            'type' => 'storable',
            'name' => 'Test Product ' . uniqid(),
            'price' => $price,
            'cost' => $cost,
            'uom_id' => $uomId,
            'uom_po_id' => $uomId,
            'category_id' => $categoryId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Test returns product price when no vendor prices exist
     */
    public function test_returns_product_price_when_no_vendor_prices()
    {
        $productId = $this->createProduct(price: 150, cost: 80);

        $result = VendorPriceCalculator::calculate($productId);

        $this->assertEquals(150, $result);
    }

    /**
     * Test returns product cost when price is null
     */
    public function test_returns_product_cost_when_price_is_null()
    {
        $productId = $this->createProduct(price: 0, cost: 75);

        // Update to set price to null
        DB::table('products_products')
            ->where('id', $productId)
            ->update(['price' => null]);

        $result = VendorPriceCalculator::calculate($productId);

        $this->assertEquals(75, $result);
    }

    /**
     * Test returns zero for non-existent product
     */
    public function test_returns_zero_for_nonexistent_product()
    {
        $result = VendorPriceCalculator::calculate(999999);

        $this->assertEquals(0, $result);
    }

    /**
     * Test returns float value
     */
    public function test_returns_float_value()
    {
        $productId = $this->createProduct(price: 99.99);

        $result = VendorPriceCalculator::calculate($productId);

        $this->assertIsFloat($result);
    }

    /**
     * Test handles zero price and cost gracefully
     */
    public function test_handles_zero_price_and_cost()
    {
        $productId = $this->createProduct(price: 0, cost: 0);

        $result = VendorPriceCalculator::calculate($productId);

        $this->assertEquals(0, $result);
    }

    /**
     * Test quantity parameter doesn't affect result without vendor prices
     */
    public function test_quantity_doesnt_affect_product_price()
    {
        $productId = $this->createProduct(price: 100);

        $result1 = VendorPriceCalculator::calculate($productId, quantity: 1);
        $result2 = VendorPriceCalculator::calculate($productId, quantity: 100);

        $this->assertEquals($result1, $result2);
    }

    /**
     * Test accepts all optional parameters
     */
    public function test_accepts_all_optional_parameters()
    {
        $productId = $this->createProduct(price: 200);

        // Should not throw exception when all params provided
        $result = VendorPriceCalculator::calculate(
            productId: $productId,
            partnerId: 1,
            quantity: 10,
            currencyId: 1,
            uomId: null
        );

        $this->assertIsFloat($result);
    }

    /**
     * Test UOM factor adjustment with valid UOM
     */
    public function test_adjusts_price_by_uom_factor()
    {
        $productId = $this->createProduct(price: 100);

        // Get a UOM with known factor
        $uom = DB::table('unit_of_measures')->first();

        if ($uom && $uom->factor > 0 && $uom->factor != 1) {
            $result = VendorPriceCalculator::calculate(
                productId: $productId,
                uomId: $uom->id
            );

            $this->assertEquals(100 / $uom->factor, $result);
        } else {
            // If no UOM with factor != 1, just verify it doesn't crash
            $result = VendorPriceCalculator::calculate(
                productId: $productId,
                uomId: $uom?->id
            );
            $this->assertIsFloat($result);
        }
    }

    /**
     * Test handles deleted (soft-deleted) products
     */
    public function test_handles_soft_deleted_products()
    {
        $productId = $this->createProduct(price: 250);

        // Soft delete the product
        DB::table('products_products')
            ->where('id', $productId)
            ->update(['deleted_at' => now()]);

        // Should still find and return price (withTrashed)
        $result = VendorPriceCalculator::calculate($productId);

        $this->assertEquals(250, $result);
    }
}
