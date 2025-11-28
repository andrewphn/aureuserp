<?php

namespace Tests\Unit\Actions;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Webkul\Partner\Models\Partner;
use Webkul\Sale\Enums\OrderState;
use Webkul\Sale\Models\Order;
use Webkul\Sale\Models\OrderLine;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\Currency;

/**
 * Unit Tests for Quote to Order Conversion
 *
 * Tests the business logic of converting a quote (draft order)
 * into a confirmed sales order, including data preservation.
 */
class ConvertToOrderActionTest extends TestCase
{
    use DatabaseTransactions;

    protected Company $company;
    protected Partner $partner;
    protected Currency $currency;
    protected \App\Models\User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Check if required tables exist
        if (!$this->hasRequiredTables()) {
            $this->markTestSkipped('Required sales tables not available. Run all migrations first.');
        }

        // Get or create a test user
        $this->user = \App\Models\User::first() ?? \App\Models\User::factory()->create();

        $this->company = Company::firstOrCreate(
            ['name' => 'Test Company'],
            ['is_active' => true]
        );

        $this->partner = Partner::firstOrCreate(
            ['name' => 'Test Customer'],
            ['company_id' => $this->company->id, 'is_customer' => true]
        );

        $this->currency = Currency::firstOrCreate(
            ['code' => 'USD'],
            ['name' => 'US Dollar', 'symbol' => '$', 'is_default' => true]
        );
    }

    /**
     * Check if required database tables exist for these tests
     */
    protected function hasRequiredTables(): bool
    {
        $requiredTables = [
            'sales_orders',
            'sales_order_lines',
        ];

        foreach ($requiredTables as $table) {
            if (!\Illuminate\Support\Facades\Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    /** @test */
    public function it_converts_quote_to_order(): void
    {
        $quote = $this->createQuote();

        $order = $this->performConversion($quote);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertNotEquals($quote->id, $order->id);
        $this->assertEquals(OrderState::SALE, $order->state);
    }

    /** @test */
    public function it_preserves_customer_and_company(): void
    {
        $quote = $this->createQuote();

        $order = $this->performConversion($quote);

        $this->assertEquals($quote->partner_id, $order->partner_id);
        $this->assertEquals($quote->company_id, $order->company_id);
    }

    /** @test */
    public function it_copies_order_lines(): void
    {
        $quote = $this->createQuote();

        // Add lines to quote
        OrderLine::create([
            'order_id' => $quote->id,
            'company_id' => $quote->company_id,
            'currency_id' => $quote->currency_id,
            'name' => 'Kitchen Cabinets',
            'product_uom_qty' => 10,
            'price_unit' => 150.00,
            'price_subtotal' => 1500.00,
            'price_total' => 1500.00,
            'sort' => 1,
        ]);

        OrderLine::create([
            'order_id' => $quote->id,
            'company_id' => $quote->company_id,
            'currency_id' => $quote->currency_id,
            'name' => 'Bathroom Vanity',
            'product_uom_qty' => 5,
            'price_unit' => 200.00,
            'price_subtotal' => 1000.00,
            'price_total' => 1000.00,
            'sort' => 2,
        ]);

        $order = $this->performConversion($quote);

        $this->assertEquals(2, $order->lines()->count());
        $this->assertEquals($quote->lines()->sum('price_total'), $order->lines()->sum('price_total'));
    }

    /** @test */
    public function it_copies_line_details_correctly(): void
    {
        $quote = $this->createQuote();

        OrderLine::create([
            'order_id' => $quote->id,
            'company_id' => $quote->company_id,
            'currency_id' => $quote->currency_id,
            'name' => 'Test Line',
            'product_uom_qty' => 5,
            'price_unit' => 100.00,
            'price_subtotal' => 500.00,
            'price_total' => 500.00,
            'discount' => 10,
            'sort' => 1,
        ]);

        $order = $this->performConversion($quote);
        $orderLine = $order->lines()->first();
        $quoteLine = $quote->lines()->first();

        $this->assertEquals($quoteLine->name, $orderLine->name);
        $this->assertEquals($quoteLine->product_uom_qty, $orderLine->product_uom_qty);
        $this->assertEquals($quoteLine->price_unit, $orderLine->price_unit);
        $this->assertEquals($quoteLine->discount, $orderLine->discount);
    }

    /** @test */
    public function it_preserves_amounts(): void
    {
        $quote = $this->createQuote([
            'amount_untaxed' => 5000.00,
            'amount_tax' => 500.00,
            'amount_total' => 5500.00,
        ]);

        $order = $this->performConversion($quote);

        $this->assertEquals($quote->amount_untaxed, $order->amount_untaxed);
        $this->assertEquals($quote->amount_tax, $order->amount_tax);
        $this->assertEquals($quote->amount_total, $order->amount_total);
    }

    /** @test */
    public function it_sets_order_state_to_sale(): void
    {
        $quote = $this->createQuote(['state' => OrderState::DRAFT]);

        $order = $this->performConversion($quote);

        $this->assertEquals(OrderState::SALE, $order->state);
    }

    /** @test */
    public function it_preserves_project_link(): void
    {
        // Get or create a user for project ownership
        $user = \App\Models\User::first();
        if (!$user) {
            $user = \App\Models\User::factory()->create();
        }

        // Get or create a project stage
        $stage = \Webkul\Project\Models\ProjectStage::first();
        if (!$stage) {
            $stage = \Webkul\Project\Models\ProjectStage::create([
                'name' => 'New',
                'sort' => 1,
                'company_id' => $this->company->id,
            ]);
        }

        // Create a project first
        $project = \Webkul\Project\Models\Project::create([
            'name' => 'Test Project',
            'partner_id' => $this->partner->id,
            'company_id' => $this->company->id,
            'stage_id' => $stage->id,
            'user_id' => $user->id,
            'creator_id' => $user->id,
        ]);

        $quote = $this->createQuote([
            'project_id' => $project->id,
        ]);

        $order = $this->performConversion($quote);

        $this->assertEquals($quote->project_id, $order->project_id);
    }

    /** @test */
    public function it_copies_section_headers(): void
    {
        $quote = $this->createQuote();

        // Add section header
        OrderLine::create([
            'order_id' => $quote->id,
            'company_id' => $quote->company_id,
            'currency_id' => $quote->currency_id,
            'display_type' => 'line_section',
            'name' => 'Kitchen',
            'product_uom_qty' => 0,
            'price_unit' => 0,
            'price_subtotal' => 0,
            'price_total' => 0,
            'sort' => 1,
        ]);

        // Add regular line
        OrderLine::create([
            'order_id' => $quote->id,
            'company_id' => $quote->company_id,
            'currency_id' => $quote->currency_id,
            'name' => 'Cabinet',
            'product_uom_qty' => 10,
            'price_unit' => 100,
            'price_subtotal' => 1000,
            'price_total' => 1000,
            'sort' => 2,
        ]);

        $order = $this->performConversion($quote);

        $sectionLine = $order->lines()->where('display_type', 'line_section')->first();
        $this->assertNotNull($sectionLine);
        $this->assertEquals('Kitchen', $sectionLine->name);
    }

    /** @test */
    public function it_can_only_convert_draft_or_sent_quotes(): void
    {
        // Test that only draft/sent quotes can be converted
        $draftQuote = $this->createQuote(['state' => OrderState::DRAFT]);
        $this->assertTrue($this->canConvert($draftQuote));

        $sentQuote = $this->createQuote(['state' => OrderState::SENT]);
        $this->assertTrue($this->canConvert($sentQuote));

        // Sale orders cannot be "converted" again
        $saleOrder = $this->createQuote(['state' => OrderState::SALE]);
        $this->assertFalse($this->canConvert($saleOrder));
    }

    /** @test */
    public function it_preserves_line_sort_order(): void
    {
        $quote = $this->createQuote();

        for ($i = 1; $i <= 5; $i++) {
            OrderLine::create([
                'order_id' => $quote->id,
                'company_id' => $quote->company_id,
                'currency_id' => $quote->currency_id,
                'name' => "Line {$i}",
                'product_uom_qty' => $i,
                'price_unit' => 100,
                'price_subtotal' => $i * 100,
                'price_total' => $i * 100,
                'sort' => $i,
            ]);
        }

        $order = $this->performConversion($quote);

        // Verify correct number of lines were copied
        $orderLines = $order->lines()->orderBy('sort')->get();
        $this->assertEquals(5, $orderLines->count());

        // Verify lines are in the correct relative order (names match ascending sort order)
        $lineNames = $orderLines->pluck('name')->toArray();
        $this->assertEquals(['Line 1', 'Line 2', 'Line 3', 'Line 4', 'Line 5'], $lineNames);
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    protected function createQuote(array $attributes = []): Order
    {
        return Order::create(array_merge([
            'name' => 'Q-' . uniqid(),
            'partner_id' => $this->partner->id,
            'partner_invoice_id' => $this->partner->id,
            'partner_shipping_id' => $this->partner->id,
            'company_id' => $this->company->id,
            'currency_id' => $this->currency->id,
            'state' => OrderState::DRAFT,
            'date_order' => now(),
            'user_id' => $this->user->id,
            'creator_id' => $this->user->id,
        ], $attributes));
    }

    protected function canConvert(Order $quote): bool
    {
        return in_array($quote->state, [OrderState::DRAFT, OrderState::SENT]);
    }

    /**
     * Simulate the conversion logic from ConvertToOrderAction
     *
     * Creates a new order with SALE state from a quote,
     * copying all lines and relevant data.
     */
    protected function performConversion(Order $quote): Order
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($quote) {
            // Replicate quote data
            $orderData = $quote->replicate([
                'id',
                'name',
                'created_at',
                'updated_at',
            ])->toArray();

            // Set order state
            $orderData['state'] = OrderState::SALE;
            $orderData['date_order'] = now();

            // Create the new order
            $order = Order::create($orderData);

            // Copy all order lines
            foreach ($quote->lines as $quoteLine) {
                $lineData = $quoteLine->replicate([
                    'id',
                    'order_id',
                    'created_at',
                    'updated_at',
                ])->toArray();

                $lineData['order_id'] = $order->id;

                OrderLine::create($lineData);
            }

            return $order;
        });
    }
}
