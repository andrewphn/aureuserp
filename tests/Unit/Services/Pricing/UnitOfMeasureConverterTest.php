<?php

namespace Tests\Unit\Services\Pricing;

use PHPUnit\Framework\TestCase;
use Webkul\Sale\Services\Pricing\UnitOfMeasureConverter;

/**
 * Unit tests for UnitOfMeasureConverter
 *
 * @note These tests document the current fallback behavior.
 *       Once the Uom model is properly implemented, these tests should be updated
 *       to test actual UOM conversion logic.
 */
class UnitOfMeasureConverterTest extends TestCase
{
    /**
     * Test conversion without UOM ID returns quantity as-is
     */
    public function test_convert_without_uom_returns_quantity()
    {
        $result = UnitOfMeasureConverter::convert(null, 100);
        $this->assertEquals(100.0, $result);

        $result = UnitOfMeasureConverter::convert(null, 50.5);
        $this->assertEquals(50.5, $result);
    }

    /**
     * Test conversion with null quantity returns 0
     */
    public function test_convert_with_null_quantity_returns_zero()
    {
        $result = UnitOfMeasureConverter::convert(null, null);
        $this->assertEquals(0.0, $result);
    }

    /**
     * Test current fallback behavior with UOM ID
     *
     * @note This test documents the current broken state where UOM conversion is not implemented.
     *       When Uom model is added, this test should be updated to test actual conversion.
     */
    public function test_convert_with_uom_id_returns_quantity_as_fallback()
    {
        // Current implementation returns quantity as-is (fallback behavior)
        // TODO: Update this test once Uom model is implemented to test actual conversion
        $result = UnitOfMeasureConverter::convert(1, 100);
        $this->assertEquals(100.0, $result);
    }

    /**
     * Test that converter always returns float type
     */
    public function test_convert_always_returns_float()
    {
        $result = UnitOfMeasureConverter::convert(null, 42);
        $this->assertIsFloat($result);

        $result = UnitOfMeasureConverter::convert(null, "42.5");
        $this->assertIsFloat($result);
    }
}
