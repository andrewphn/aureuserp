<?php

namespace Tests\Unit\Services\Pricing;

use PHPUnit\Framework\TestCase;
use Webkul\Sale\Services\Pricing\MarginCalculator;

/**
 * Unit tests for MarginCalculator service
 *
 * @covers \Webkul\Sale\Services\Pricing\MarginCalculator
 */
class MarginCalculatorTest extends TestCase
{
    /**
     * Test basic margin calculation without discount
     */
    public function test_calculates_margin_without_discount()
    {
        // Selling at $100, cost $60, 1 unit, no discount
        // Margin = $100 - $60 = $40
        // Margin % = $40 / $100 = 40%
        [$totalMargin, $marginPercentage] = MarginCalculator::calculate(100, 60, 1, 0);

        $this->assertEquals(40, $totalMargin);
        $this->assertEquals(40, $marginPercentage);
    }

    /**
     * Test margin calculation with discount
     */
    public function test_calculates_margin_with_discount()
    {
        // Selling at $100, cost $60, 1 unit, 10% discount
        // Discounted price = $100 - $10 = $90
        // Margin = $90 - $60 = $30
        // Margin % = $30 / $90 = 33.33%
        [$totalMargin, $marginPercentage] = MarginCalculator::calculate(100, 60, 1, 10);

        $this->assertEquals(30, $totalMargin);
        $this->assertEqualsWithDelta(33.33, $marginPercentage, 0.01);
    }

    /**
     * Test margin calculation with multiple quantities
     */
    public function test_calculates_total_margin_for_multiple_quantities()
    {
        // Selling at $100, cost $60, 10 units, no discount
        // Margin per unit = $40
        // Total margin = $40 * 10 = $400
        [$totalMargin, $marginPercentage] = MarginCalculator::calculate(100, 60, 10, 0);

        $this->assertEquals(400, $totalMargin);
        $this->assertEquals(40, $marginPercentage); // Percentage doesn't change with quantity
    }

    /**
     * Test negative margin (loss) calculation
     */
    public function test_calculates_negative_margin_when_cost_exceeds_price()
    {
        // Selling at $50, cost $70, 1 unit, no discount
        // Margin = $50 - $70 = -$20 (loss)
        // Margin % = -$20 / $50 = -40%
        [$totalMargin, $marginPercentage] = MarginCalculator::calculate(50, 70, 1, 0);

        $this->assertEquals(-20, $totalMargin);
        $this->assertEquals(-40, $marginPercentage);
    }

    /**
     * Test zero margin calculation
     */
    public function test_calculates_zero_margin_when_price_equals_cost()
    {
        // Selling at $100, cost $100, 1 unit, no discount
        // Margin = $0
        // Margin % = 0%
        [$totalMargin, $marginPercentage] = MarginCalculator::calculate(100, 100, 1, 0);

        $this->assertEquals(0, $totalMargin);
        $this->assertEquals(0, $marginPercentage);
    }

    /**
     * Test handles zero selling price
     */
    public function test_handles_zero_selling_price()
    {
        // Selling at $0, cost $50, 1 unit - free item
        // Margin = -$50
        // Margin % = 0 (avoid division by zero)
        [$totalMargin, $marginPercentage] = MarginCalculator::calculate(0, 50, 1, 0);

        $this->assertEquals(-50, $totalMargin);
        $this->assertEquals(0, $marginPercentage); // Division by zero protection
    }

    /**
     * Test handles zero quantity
     */
    public function test_handles_zero_quantity()
    {
        // Selling at $100, cost $60, 0 units
        // Total margin = $0
        [$totalMargin, $marginPercentage] = MarginCalculator::calculate(100, 60, 0, 0);

        $this->assertEquals(0, $totalMargin);
        // Margin percentage is still calculated based on unit price
        $this->assertEquals(40, $marginPercentage);
    }

    /**
     * Test 100% discount eliminates margin
     */
    public function test_hundred_percent_discount_eliminates_margin()
    {
        // Selling at $100, cost $60, 1 unit, 100% discount
        // Discounted price = $0
        // Margin = -$60
        [$totalMargin, $marginPercentage] = MarginCalculator::calculate(100, 60, 1, 100);

        $this->assertEquals(-60, $totalMargin);
        $this->assertEquals(0, $marginPercentage); // Division by zero protection
    }

    /**
     * Test return structure is array with two elements
     */
    public function test_returns_array_with_two_elements()
    {
        $result = MarginCalculator::calculate(100, 60, 1, 0);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    /**
     * Test real-world scenario: cabinet door pricing
     */
    public function test_real_world_cabinet_door_pricing()
    {
        // Cabinet doors: sell at $45/door, cost $28/door, 24 doors, 5% volume discount
        // Discounted price = $45 - $2.25 = $42.75
        // Margin per door = $42.75 - $28 = $14.75
        // Total margin = $14.75 * 24 = $354
        // Margin % = $14.75 / $42.75 = 34.5%
        [$totalMargin, $marginPercentage] = MarginCalculator::calculate(45, 28, 24, 5);

        $this->assertEquals(354, $totalMargin);
        $this->assertEqualsWithDelta(34.5, $marginPercentage, 0.1);
    }

    /**
     * Test fractional values are handled correctly
     */
    public function test_handles_fractional_values()
    {
        // Price $99.99, cost $45.50, 2.5 units, 7.5% discount
        [$totalMargin, $marginPercentage] = MarginCalculator::calculate(99.99, 45.50, 2.5, 7.5);

        // Discounted price = $99.99 - $7.50 = $92.49
        // Margin per unit = $92.49 - $45.50 = $46.99
        // Total margin = $46.99 * 2.5 = $117.48
        $this->assertEqualsWithDelta(117.48, $totalMargin, 0.1);
        $this->assertGreaterThan(0, $marginPercentage);
    }

    /**
     * Test discount percentage is treated as percentage (0-100), not decimal
     */
    public function test_discount_is_percentage_not_decimal()
    {
        // 10% discount should be passed as 10, not 0.10
        [$totalMargin1, $marginPercentage1] = MarginCalculator::calculate(100, 60, 1, 10);
        [$totalMargin2, $marginPercentage2] = MarginCalculator::calculate(100, 60, 1, 0.10);

        // With 10% discount: margin = $30
        $this->assertEquals(30, $totalMargin1);

        // With 0.10% discount: margin = ~$39.90 (almost full price)
        // $100 * (0.10 / 100) = $0.10 discount, so $99.90 - $60 = $39.90
        $this->assertEqualsWithDelta(39.90, $totalMargin2, 0.01);
    }
}
