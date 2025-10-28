<?php

namespace Tests\Unit\Models;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Webkul\Sale\Models\SalesOrderLineItem;
use Webkul\Sale\Models\Order;
use Webkul\Product\Models\Product;

class SalesOrderLineItemTest extends TestCase
{
    use DatabaseTransactions;

    protected SalesOrderLineItem $lineItem;
    protected Order $order;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test order and product using factories
        $this->order = Order::factory()->create([
            'name' => 'SO-TEST-001',
        ]);
        $this->product = Product::factory()->create([
            'name' => 'Base Cabinet',
        ]);

        // Create basic line item
        $this->lineItem = SalesOrderLineItem::create([
            'sales_order_id' => $this->order->id,
            'product_id' => $this->product->id,
            'line_item_type' => 'cabinet',
            'description' => '24" Base Cabinet',
            'quantity' => 1.00,
            'linear_feet' => 2.00,
            'base_rate_per_lf' => 100.00,
            'material_rate_per_lf' => 50.00,
            'combined_rate_per_lf' => 150.00,
            'sequence' => 1,
        ]);
    }

    /** @test */
    public function it_can_be_created_with_minimal_attributes(): void
    {
        $lineItem = SalesOrderLineItem::create([
            'sales_order_id' => $this->order->id,
            'line_item_type' => 'additional',
            'description' => 'Installation',
            'quantity' => 1.00,
            'unit_price' => 500.00,
        ]);

        $this->assertInstanceOf(SalesOrderLineItem::class, $lineItem);
        $this->assertEquals('additional', $lineItem->line_item_type);
        $this->assertEquals(1.00, $lineItem->quantity);
    }

    /** @test */
    public function it_auto_calculates_cabinet_subtotal_with_linear_feet(): void
    {
        // Cabinet pricing: subtotal = linear_feet * combined_rate_per_lf * quantity
        // Expected: 2.00 * 150.00 * 1.00 = 300.00
        $this->assertEquals(300.00, $this->lineItem->subtotal);
    }

    /** @test */
    public function it_auto_calculates_standard_subtotal_with_quantity(): void
    {
        $lineItem = SalesOrderLineItem::create([
            'sales_order_id' => $this->order->id,
            'line_item_type' => 'additional',
            'description' => 'Installation',
            'quantity' => 2.00,
            'unit_price' => 250.00,
        ]);

        // Standard pricing: subtotal = quantity * unit_price
        // Expected: 2.00 * 250.00 = 500.00
        $this->assertEquals(500.00, $lineItem->subtotal);
    }

    /** @test */
    public function it_auto_calculates_cabinet_subtotal_with_multiple_quantity(): void
    {
        $lineItem = SalesOrderLineItem::create([
            'sales_order_id' => $this->order->id,
            'line_item_type' => 'cabinet',
            'description' => '24" Wall Cabinet',
            'quantity' => 3.00,
            'linear_feet' => 2.00,
            'base_rate_per_lf' => 60.00,
            'material_rate_per_lf' => 40.00,
            'combined_rate_per_lf' => 100.00,
        ]);

        // Cabinet with quantity: 2.00 LF * 100.00 * 3 qty = 600.00
        $this->assertEquals(600.00, $lineItem->subtotal);
    }

    /** @test */
    public function it_auto_calculates_discount_amount_from_percentage(): void
    {
        $lineItem = SalesOrderLineItem::create([
            'sales_order_id' => $this->order->id,
            'line_item_type' => 'additional',
            'description' => 'Test Item',
            'quantity' => 1.00,
            'unit_price' => 100.00,
            'discount_percentage' => 10.00,
        ]);

        // discount_amount = subtotal * (discount_percentage / 100)
        // Expected: 100.00 * 0.10 = 10.00
        $this->assertEquals(10.00, $lineItem->discount_amount);
    }

    /** @test */
    public function it_auto_calculates_line_total_without_discount(): void
    {
        $lineItem = SalesOrderLineItem::create([
            'sales_order_id' => $this->order->id,
            'line_item_type' => 'additional',
            'description' => 'Test Item',
            'quantity' => 1.00,
            'unit_price' => 100.00,
        ]);

        // line_total = subtotal - discount_amount (0 if no discount)
        // Expected: 100.00 - 0 = 100.00
        $this->assertEquals(100.00, $lineItem->line_total);
    }

    /** @test */
    public function it_auto_calculates_line_total_with_discount(): void
    {
        $lineItem = SalesOrderLineItem::create([
            'sales_order_id' => $this->order->id,
            'line_item_type' => 'additional',
            'description' => 'Test Item',
            'quantity' => 1.00,
            'unit_price' => 100.00,
            'discount_percentage' => 15.00,
        ]);

        // line_total = subtotal - discount_amount
        // Expected: 100.00 - 15.00 = 85.00
        $this->assertEquals(85.00, $lineItem->line_total);
    }

    /** @test */
    public function it_recalculates_on_update(): void
    {
        $this->lineItem->update([
            'linear_feet' => 4.00,
            'base_rate_per_lf' => 120.00,
            'material_rate_per_lf' => 80.00,
            'combined_rate_per_lf' => 200.00,
        ]);

        // New subtotal: 4.00 * 200.00 * 1.00 = 800.00
        $this->assertEquals(800.00, $this->lineItem->fresh()->subtotal);
        $this->assertEquals(800.00, $this->lineItem->fresh()->line_total);
    }

    /** @test */
    public function it_handles_zero_discount(): void
    {
        $lineItem = SalesOrderLineItem::create([
            'sales_order_id' => $this->order->id,
            'line_item_type' => 'additional',
            'description' => 'Test Item',
            'quantity' => 1.00,
            'unit_price' => 100.00,
            'discount_percentage' => 0.00,
        ]);

        $this->assertEquals(0.00, $lineItem->discount_amount);
        $this->assertEquals(100.00, $lineItem->line_total);
    }

    /** @test */
    public function it_handles_100_percent_discount(): void
    {
        $lineItem = SalesOrderLineItem::create([
            'sales_order_id' => $this->order->id,
            'line_item_type' => 'additional',
            'description' => 'Test Item',
            'quantity' => 1.00,
            'unit_price' => 100.00,
            'discount_percentage' => 100.00,
        ]);

        $this->assertEquals(100.00, $lineItem->discount_amount);
        $this->assertEquals(0.00, $lineItem->line_total);
    }

    /** @test */
    public function it_casts_decimal_fields_correctly(): void
    {
        // Laravel 11.5 decimal casting returns strings, not floats
        $this->assertIsString($this->lineItem->quantity);
        $this->assertIsString($this->lineItem->linear_feet);
        $this->assertIsString($this->lineItem->base_rate_per_lf);
        $this->assertIsString($this->lineItem->material_rate_per_lf);
        $this->assertIsString($this->lineItem->combined_rate_per_lf);
        $this->assertIsString($this->lineItem->subtotal);
        $this->assertIsString($this->lineItem->line_total);

        // Verify values are correct decimal strings
        $this->assertEquals('1.00', $this->lineItem->quantity);
        $this->assertEquals('2.00', $this->lineItem->linear_feet);
        $this->assertEquals('100.00', $this->lineItem->base_rate_per_lf);
        $this->assertEquals('50.00', $this->lineItem->material_rate_per_lf);
        $this->assertEquals('150.00', $this->lineItem->combined_rate_per_lf);
    }

    /** @test */
    public function it_casts_integer_fields_correctly(): void
    {
        $this->assertIsInt($this->lineItem->sequence);
        $this->assertEquals(1, $this->lineItem->sequence);
    }

    /** @test */
    public function it_belongs_to_sales_order(): void
    {
        $this->assertTrue(method_exists($this->lineItem, 'salesOrder'));
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $this->lineItem->salesOrder()
        );
        $this->assertEquals($this->order->id, $this->lineItem->salesOrder->id);
    }

    /** @test */
    public function it_belongs_to_product(): void
    {
        $this->assertTrue(method_exists($this->lineItem, 'product'));
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $this->lineItem->product()
        );
        $this->assertEquals($this->product->id, $this->lineItem->product->id);
    }

    /** @test */
    public function it_belongs_to_room(): void
    {
        $this->assertTrue(method_exists($this->lineItem, 'room'));
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $this->lineItem->room()
        );
    }

    /** @test */
    public function it_belongs_to_room_location(): void
    {
        $this->assertTrue(method_exists($this->lineItem, 'roomLocation'));
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $this->lineItem->roomLocation()
        );
    }

    /** @test */
    public function it_belongs_to_cabinet_run(): void
    {
        $this->assertTrue(method_exists($this->lineItem, 'cabinetRun'));
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $this->lineItem->cabinetRun()
        );
    }

    /** @test */
    public function it_belongs_to_cabinet_specification(): void
    {
        $this->assertTrue(method_exists($this->lineItem, 'cabinetSpecification'));
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $this->lineItem->cabinetSpecification()
        );
    }

    /** @test */
    public function scope_cabinets_filters_cabinet_line_items(): void
    {
        SalesOrderLineItem::create([
            'sales_order_id' => $this->order->id,
            'line_item_type' => 'cabinet',
            'description' => 'Base Cabinet',
            'quantity' => 1.00,
            'unit_price' => 100.00,
        ]);

        SalesOrderLineItem::create([
            'sales_order_id' => $this->order->id,
            'line_item_type' => 'countertop',
            'description' => 'Granite Countertop',
            'quantity' => 1.00,
            'unit_price' => 200.00,
        ]);

        $cabinets = SalesOrderLineItem::cabinets()->get();

        $this->assertGreaterThanOrEqual(2, $cabinets->count()); // Including setup line item
        $this->assertTrue($cabinets->every(fn ($item) => $item->line_item_type === 'cabinet'));
    }

    /** @test */
    public function scope_countertops_filters_countertop_line_items(): void
    {
        SalesOrderLineItem::create([
            'sales_order_id' => $this->order->id,
            'line_item_type' => 'countertop',
            'description' => 'Granite Countertop',
            'quantity' => 1.00,
            'unit_price' => 200.00,
        ]);

        $countertops = SalesOrderLineItem::countertops()->get();

        $this->assertEquals(1, $countertops->count());
        $this->assertTrue($countertops->every(fn ($item) => $item->line_item_type === 'countertop'));
    }

    /** @test */
    public function scope_additional_filters_additional_line_items(): void
    {
        SalesOrderLineItem::create([
            'sales_order_id' => $this->order->id,
            'line_item_type' => 'additional',
            'description' => 'Installation Fee',
            'quantity' => 1.00,
            'unit_price' => 500.00,
        ]);

        $additional = SalesOrderLineItem::additional()->get();

        $this->assertEquals(1, $additional->count());
        $this->assertTrue($additional->every(fn ($item) => $item->line_item_type === 'additional'));
    }

    /** @test */
    public function scope_discount_filters_discount_line_items(): void
    {
        SalesOrderLineItem::create([
            'sales_order_id' => $this->order->id,
            'line_item_type' => 'discount',
            'description' => 'Volume Discount',
            'quantity' => 1.00,
            'unit_price' => -100.00,
        ]);

        $discounts = SalesOrderLineItem::discount()->get();

        $this->assertEquals(1, $discounts->count());
        $this->assertTrue($discounts->every(fn ($item) => $item->line_item_type === 'discount'));
    }

    /** @test */
    public function scope_ordered_sorts_by_sort_order(): void
    {
        SalesOrderLineItem::create([
            'sales_order_id' => $this->order->id,
            'line_item_type' => 'cabinet',
            'description' => 'Item C',
            'quantity' => 1.00,
            'unit_price' => 100.00,
            'sequence' => 30,
        ]);

        SalesOrderLineItem::create([
            'sales_order_id' => $this->order->id,
            'line_item_type' => 'cabinet',
            'description' => 'Item A',
            'quantity' => 1.00,
            'unit_price' => 100.00,
            'sequence' => 10,
        ]);

        SalesOrderLineItem::create([
            'sales_order_id' => $this->order->id,
            'line_item_type' => 'cabinet',
            'description' => 'Item B',
            'quantity' => 1.00,
            'unit_price' => 100.00,
            'sequence' => 20,
        ]);

        $ordered = SalesOrderLineItem::ordered()->get();

        // First should have lowest sequence
        $this->assertEquals(1, $ordered->first()->sequence); // Setup item has sequence 1
        $this->assertEquals('Item A', $ordered->skip(1)->first()->description);
    }

    /** @test */
    public function it_handles_fractional_linear_feet(): void
    {
        $lineItem = SalesOrderLineItem::create([
            'sales_order_id' => $this->order->id,
            'line_item_type' => 'cabinet',
            'description' => '18" Cabinet',
            'quantity' => 1.00,
            'linear_feet' => 1.50,
            'base_rate_per_lf' => 120.00,
            'material_rate_per_lf' => 80.00,
            'combined_rate_per_lf' => 200.00,
        ]);

        // 1.50 LF * 200.00 = 300.00
        $this->assertEquals(300.00, $lineItem->subtotal);
    }

    /** @test */
    public function it_handles_high_quantity_cabinet_pricing(): void
    {
        $lineItem = SalesOrderLineItem::create([
            'sales_order_id' => $this->order->id,
            'line_item_type' => 'cabinet',
            'description' => 'Standard Cabinet',
            'quantity' => 10.00,
            'linear_feet' => 2.00,
            'base_rate_per_lf' => 90.00,
            'material_rate_per_lf' => 60.00,
            'combined_rate_per_lf' => 150.00,
        ]);

        // 2.00 LF * 150.00 * 10 qty = 3000.00
        $this->assertEquals(3000.00, $lineItem->subtotal);
    }

    /** @test */
    public function it_handles_partial_discount_percentage(): void
    {
        $lineItem = SalesOrderLineItem::create([
            'sales_order_id' => $this->order->id,
            'line_item_type' => 'additional',
            'description' => 'Test Item',
            'quantity' => 1.00,
            'unit_price' => 100.00,
            'discount_percentage' => 5.50,
        ]);

        // 100.00 * 0.055 = 5.50
        $this->assertEquals(5.50, $lineItem->discount_amount);
        $this->assertEquals(94.50, $lineItem->line_total);
    }

    /** @test */
    public function it_can_be_updated(): void
    {
        $this->lineItem->update([
            'description' => 'Updated Cabinet',
            'quantity' => 2.00,
        ]);

        $this->assertEquals('Updated Cabinet', $this->lineItem->fresh()->description);
        $this->assertEquals(2.00, $this->lineItem->fresh()->quantity);
    }

    /** @test */
    public function it_can_be_soft_deleted(): void
    {
        $id = $this->lineItem->id;
        $this->lineItem->delete();

        $this->assertSoftDeleted('sales_order_line_items', ['id' => $id]);
        $this->assertNotNull($this->lineItem->fresh()->deleted_at);
    }

    /** @test */
    public function it_can_be_restored_after_soft_delete(): void
    {
        $this->lineItem->delete();
        $this->lineItem->restore();

        $this->assertNull($this->lineItem->fresh()->deleted_at);
        $this->assertDatabaseHas('sales_order_line_items', [
            'id' => $this->lineItem->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function nullable_fields_can_be_null(): void
    {
        $lineItem = SalesOrderLineItem::create([
            'sales_order_id' => $this->order->id,
            'line_item_type' => 'additional',
            'description' => 'Minimal Item',
            'quantity' => 1.00,
            'unit_price' => 100.00,
        ]);

        $this->assertNull($lineItem->room_id);
        $this->assertNull($lineItem->room_location_id);
        $this->assertNull($lineItem->cabinet_run_id);
        $this->assertNull($lineItem->cabinet_specification_id);
        $this->assertNull($lineItem->product_id);
        $this->assertNull($lineItem->linear_feet);
        $this->assertNull($lineItem->base_rate_per_lf);
        $this->assertNull($lineItem->material_rate_per_lf);
        $this->assertNull($lineItem->combined_rate_per_lf);
        $this->assertNull($lineItem->discount_percentage);
    }

    /** @test */
    public function it_differentiates_between_pricing_models(): void
    {
        // Cabinet with linear feet pricing
        $cabinet = SalesOrderLineItem::create([
            'sales_order_id' => $this->order->id,
            'line_item_type' => 'cabinet',
            'description' => 'Cabinet',
            'quantity' => 1.00,
            'linear_feet' => 3.00,
            'base_rate_per_lf' => 60.00,
            'material_rate_per_lf' => 40.00,
            'combined_rate_per_lf' => 100.00,
        ]);

        // Standard item pricing
        $standard = SalesOrderLineItem::create([
            'sales_order_id' => $this->order->id,
            'line_item_type' => 'additional',
            'description' => 'Installation',
            'quantity' => 1.00,
            'unit_price' => 300.00,
        ]);

        // Both should calculate to 300.00 but via different methods
        $this->assertEquals(300.00, $cabinet->subtotal);
        $this->assertEquals(300.00, $standard->subtotal);
        $this->assertNotNull($cabinet->linear_feet);
        $this->assertNull($standard->linear_feet);
    }

    /** @test */
    public function it_properly_rounds_calculated_values(): void
    {
        $lineItem = SalesOrderLineItem::create([
            'sales_order_id' => $this->order->id,
            'line_item_type' => 'cabinet',
            'description' => 'Test Cabinet',
            'quantity' => 1.00,
            'linear_feet' => 2.33,
            'base_rate_per_lf' => 74.07,
            'material_rate_per_lf' => 49.38,
            'combined_rate_per_lf' => 123.45,
        ]);

        // 2.33 * 123.45 = 287.6385, should round to 287.64
        $this->assertEquals(287.64, $lineItem->subtotal);
    }
}
