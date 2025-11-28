<?php

namespace Tests\Unit\Services\Pricing;

use Tests\TestCase;
use Webkul\Account\Facades\Tax;
use Webkul\Sale\Services\Pricing\LineTotalsCalculator;

/**
 * Unit tests for LineTotalsCalculator service
 *
 * @covers \Webkul\Sale\Services\Pricing\LineTotalsCalculator
 */
class LineTotalsCalculatorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Mock Tax facade to return predictable values
        // Tax::collect returns [$subtotal, $taxAmount]
        Tax::shouldReceive('collect')
            ->andReturnUsing(function ($taxIds, $subtotal, $quantity) {
                // Simple 10% tax calculation for testing
                if (empty($taxIds)) {
                    return [$subtotal, 0];
                }
                $taxAmount = $subtotal * 0.10; // 10% tax
                return [$subtotal, $taxAmount];
            });
    }

    /**
     * Test basic calculation without discount or tax
     */
    public function test_calculates_basic_totals_without_discount_or_tax()
    {
        $result = LineTotalsCalculator::calculate(
            priceUnit: 100,
            quantity: 5,
            purchasePrice: 60,
            discount: 0,
            taxIds: []
        );

        // $100 * 5 = $500 subtotal
        $this->assertEquals(500, $result['price_subtotal']);
        $this->assertEquals(0, $result['price_tax']);
        $this->assertEquals(500, $result['price_total']);
        // Margin: ($100 - $60) * 5 = $200
        $this->assertEquals(200, $result['margin']);
        // Margin %: ($100 - $60) / $100 = 40%
        $this->assertEquals(40, $result['margin_percent']);
    }

    /**
     * Test calculation with discount applied
     */
    public function test_calculates_totals_with_discount()
    {
        $result = LineTotalsCalculator::calculate(
            priceUnit: 100,
            quantity: 10,
            purchasePrice: 50,
            discount: 10, // 10% discount
            taxIds: []
        );

        // $100 * 10 = $1000, minus 10% = $900 subtotal
        $this->assertEquals(900, $result['price_subtotal']);
        $this->assertEquals(0, $result['price_tax']);
        $this->assertEquals(900, $result['price_total']);
    }

    /**
     * Test calculation with tax applied
     */
    public function test_calculates_totals_with_tax()
    {
        $result = LineTotalsCalculator::calculate(
            priceUnit: 100,
            quantity: 1,
            purchasePrice: 60,
            discount: 0,
            taxIds: [1] // Will trigger 10% tax mock
        );

        // $100 * 1 = $100 subtotal
        $this->assertEquals(100, $result['price_subtotal']);
        // 10% tax = $10
        $this->assertEquals(10, $result['price_tax']);
        // Total = $100 + $10 = $110
        $this->assertEquals(110, $result['price_total']);
    }

    /**
     * Test calculation with both discount and tax
     */
    public function test_calculates_totals_with_discount_and_tax()
    {
        $result = LineTotalsCalculator::calculate(
            priceUnit: 200,
            quantity: 5,
            purchasePrice: 100,
            discount: 20, // 20% discount
            taxIds: [1]   // 10% tax
        );

        // $200 * 5 = $1000, minus 20% = $800 subtotal
        $this->assertEquals(800, $result['price_subtotal']);
        // 10% tax on $800 = $80
        $this->assertEquals(80, $result['price_tax']);
        // Total = $800 + $80 = $880
        $this->assertEquals(880, $result['price_total']);
    }

    /**
     * Test that margin is calculated correctly with discount
     */
    public function test_margin_accounts_for_discount()
    {
        $result = LineTotalsCalculator::calculate(
            priceUnit: 100,
            quantity: 1,
            purchasePrice: 60,
            discount: 20, // 20% discount
            taxIds: []
        );

        // Discounted price = $100 - $20 = $80
        // Margin = $80 - $60 = $20
        $this->assertEquals(20, $result['margin']);
        // Margin % = $20 / $80 = 25%
        $this->assertEquals(25, $result['margin_percent']);
    }

    /**
     * Test negative margin when selling below cost
     */
    public function test_calculates_negative_margin_when_below_cost()
    {
        $result = LineTotalsCalculator::calculate(
            priceUnit: 50,
            quantity: 2,
            purchasePrice: 70,
            discount: 0,
            taxIds: []
        );

        // Margin per unit = $50 - $70 = -$20
        // Total margin = -$20 * 2 = -$40
        $this->assertEquals(-40, $result['margin']);
        // Margin % = -$20 / $50 = -40%
        $this->assertEquals(-40, $result['margin_percent']);
    }

    /**
     * Test return structure contains all expected keys
     */
    public function test_returns_all_expected_keys()
    {
        $result = LineTotalsCalculator::calculate(100, 1);

        $this->assertArrayHasKey('price_subtotal', $result);
        $this->assertArrayHasKey('price_tax', $result);
        $this->assertArrayHasKey('price_total', $result);
        $this->assertArrayHasKey('margin', $result);
        $this->assertArrayHasKey('margin_percent', $result);
    }

    /**
     * Test getEmptyTotals returns all zero values
     */
    public function test_get_empty_totals_returns_zeros()
    {
        $result = LineTotalsCalculator::getEmptyTotals();

        $this->assertEquals(0, $result['price_unit']);
        $this->assertEquals(0, $result['discount']);
        $this->assertEquals(0, $result['price_tax']);
        $this->assertEquals(0, $result['price_subtotal']);
        $this->assertEquals(0, $result['price_total']);
        $this->assertEquals(0, $result['purchase_price']);
        $this->assertEquals(0, $result['margin']);
        $this->assertEquals(0, $result['margin_percent']);
    }

    /**
     * Test getEmptyTotals returns all expected keys
     */
    public function test_get_empty_totals_returns_all_keys()
    {
        $result = LineTotalsCalculator::getEmptyTotals();

        $expectedKeys = [
            'price_unit',
            'discount',
            'price_tax',
            'price_subtotal',
            'price_total',
            'purchase_price',
            'margin',
            'margin_percent',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $result);
        }
    }

    /**
     * Test real-world scenario: cabinet order line
     */
    public function test_real_world_cabinet_order_line()
    {
        $result = LineTotalsCalculator::calculate(
            priceUnit: 450.00,     // Cabinet unit price
            quantity: 8,           // 8 cabinets
            purchasePrice: 280.00, // Cost per cabinet
            discount: 5,           // 5% volume discount
            taxIds: [1]            // Sales tax
        );

        // $450 * 8 = $3600, minus 5% = $3420 subtotal
        $this->assertEquals(3420, $result['price_subtotal']);
        // 10% tax on $3420 = $342
        $this->assertEquals(342, $result['price_tax']);
        // Total = $3420 + $342 = $3762
        $this->assertEquals(3762, $result['price_total']);
        // Margin and percentage should be positive (selling above cost)
        $this->assertGreaterThan(0, $result['margin']);
        $this->assertGreaterThan(0, $result['margin_percent']);
    }

    /**
     * Test handles zero quantity gracefully
     */
    public function test_handles_zero_quantity()
    {
        $result = LineTotalsCalculator::calculate(
            priceUnit: 100,
            quantity: 0,
            purchasePrice: 60
        );

        $this->assertEquals(0, $result['price_subtotal']);
        $this->assertEquals(0, $result['price_total']);
        $this->assertEquals(0, $result['margin']);
    }

    /**
     * Test handles zero price gracefully
     */
    public function test_handles_zero_price()
    {
        $result = LineTotalsCalculator::calculate(
            priceUnit: 0,
            quantity: 10,
            purchasePrice: 50
        );

        $this->assertEquals(0, $result['price_subtotal']);
        $this->assertEquals(0, $result['price_total']);
        // Negative margin when giving away product
        $this->assertEquals(-500, $result['margin']);
    }

    /**
     * Test values are rounded to 4 decimal places
     */
    public function test_values_are_rounded_to_four_decimals()
    {
        $result = LineTotalsCalculator::calculate(
            priceUnit: 33.333333,
            quantity: 3,
            purchasePrice: 20.111111
        );

        // Check that values are rounded (not exact floats)
        $this->assertEquals(round(33.333333 * 3, 4), $result['price_subtotal']);
    }
}
