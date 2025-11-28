<?php

namespace Tests\Integration;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Webkul\Inventory\Models\Location;
use Webkul\Inventory\Models\Warehouse;
use Webkul\Partner\Models\Partner;
use Webkul\Project\Models\CabinetSpecification;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\ProjectStage;
use Webkul\Project\Models\Room;
use Webkul\Project\Models\RoomLocation;
use Webkul\Project\Models\CabinetRun;
use Webkul\Project\Services\ProjectToOrderService;
use Webkul\Sale\Enums\OrderState;
use Webkul\Sale\Models\Order;
use Webkul\Sale\Models\OrderLine;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\Currency;

/**
 * Integration Tests for Project <-> Sales Module Integration
 *
 * Tests the complete workflow from:
 * - Creating a quote and linking to a project
 * - Converting a quote to an order
 * - Generating order lines from project specifications
 * - Tracking quote lineage
 */
class ProjectSalesIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    protected ProjectToOrderService $projectToOrderService;
    protected Company $company;
    protected Partner $partner;
    protected Currency $currency;
    protected ProjectStage $stage;
    protected Warehouse $warehouse;
    protected \App\Models\User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Check if required tables exist
        if (!$this->hasRequiredTables()) {
            $this->markTestSkipped('Required project/sales tables not available. Run project and sales migrations first.');
        }

        // Get or create a test user
        $this->user = \App\Models\User::first() ?? \App\Models\User::factory()->create();

        $this->projectToOrderService = new ProjectToOrderService();

        // Create required test data
        $this->company = Company::firstOrCreate(
            ['name' => 'TCS Woodwork Test'],
            ['is_active' => true]
        );

        $this->currency = Currency::firstOrCreate(
            ['code' => 'USD'],
            ['name' => 'US Dollar', 'symbol' => '$', 'is_default' => true]
        );

        $this->partner = Partner::firstOrCreate(
            ['name' => 'Integration Test Customer'],
            ['company_id' => $this->company->id, 'is_customer' => true]
        );

        $this->stage = ProjectStage::firstOrCreate(
            ['name' => 'New', 'company_id' => $this->company->id],
            ['sort' => 1]
        );

        $stockLocation = Location::firstOrCreate(
            ['name' => 'Integration Test Stock'],
            ['type' => 'internal', 'company_id' => $this->company->id]
        );

        $outputLocation = Location::firstOrCreate(
            ['name' => 'Integration Test Output'],
            ['type' => 'transit', 'company_id' => $this->company->id]
        );

        $this->warehouse = Warehouse::firstOrCreate(
            ['name' => 'Integration Test Warehouse'],
            [
                'code' => 'ITW',
                'company_id' => $this->company->id,
                'partner_id' => $this->partner->id,
                'lot_stock_location_id' => $stockLocation->id,
                'output_stock_location_id' => $outputLocation->id,
            ]
        );
    }

    // =========================================================================
    // Quote to Project Linking Tests
    // =========================================================================

    /** @test */
    public function it_creates_project_from_quote_specifications(): void
    {
        // Start with a quotation
        $quote = $this->createQuote([
            'name' => 'Kitchen Renovation Quote',
        ]);

        // Create a project linked to this quote
        $project = Project::create([
            'name' => 'Kitchen Renovation Project',
            'partner_id' => $quote->partner_id,
            'company_id' => $quote->company_id,
            'stage_id' => $this->stage->id,
            'source_quote_id' => $quote->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->user->id,
            'creator_id' => $this->user->id,
        ]);

        // Also link the quote back to the project
        $quote->update(['project_id' => $project->id]);

        // Verify bidirectional linking
        $this->assertEquals($quote->id, $project->source_quote_id);
        $this->assertEquals($project->id, $quote->project_id);
    }

    /** @test */
    public function it_generates_order_lines_from_project_and_updates_quote(): void
    {
        // Create a project with cabinets
        $project = $this->createProjectWithFullStructure();

        // Create a quote linked to this project
        $quote = $this->createQuote([
            'project_id' => $project->id,
        ]);

        // Import project specifications into the quote
        $result = $this->projectToOrderService->importFromProject($quote, $project, [
            'include_cabinets' => true,
            'include_rooms' => true,
            'group_by_room' => true,
        ]);

        $this->assertTrue($result['success']);
        $this->assertGreaterThan(0, $result['lines']->count());

        // Verify lines were created
        $quote->refresh();
        $this->assertGreaterThan(0, $quote->lines()->count());

        // Check that amounts are populated
        $this->assertGreaterThan(0, $quote->amount_total);
    }

    /** @test */
    public function it_converts_quote_to_order_preserving_project_link(): void
    {
        // Create project first
        $project = $this->createProjectWithFullStructure();

        // Create quote linked to project
        $quote = $this->createQuote([
            'project_id' => $project->id,
        ]);

        // Add some lines
        $this->addOrderLines($quote, 3);

        // Convert quote to order
        $order = $this->convertQuoteToOrder($quote);

        // Verify project link is preserved
        $this->assertEquals($project->id, $order->project_id);

        // Verify quote lineage is tracked
        $this->assertEquals($quote->id, $order->source_quote_id);
        $this->assertNotNull($order->converted_from_quote_at);
    }

    /** @test */
    public function it_tracks_complete_quote_to_order_lineage(): void
    {
        // Original quote
        $originalQuote = $this->createQuote(['name' => 'Original Quote']);
        $this->addOrderLines($originalQuote, 2);

        // First conversion
        $firstOrder = $this->convertQuoteToOrder($originalQuote);

        // Verify lineage
        $this->assertEquals($originalQuote->id, $firstOrder->source_quote_id);

        // Can query derived orders from original quote
        $derivedOrders = Order::where('source_quote_id', $originalQuote->id)->get();
        $this->assertEquals(1, $derivedOrders->count());
        $this->assertEquals($firstOrder->id, $derivedOrders->first()->id);
    }

    // =========================================================================
    // Project to Order Generation Tests
    // =========================================================================

    /** @test */
    public function it_creates_order_directly_from_project(): void
    {
        $project = $this->createProjectWithFullStructure();

        $order = $this->projectToOrderService->createOrderForProject($project);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals($project->id, $order->project_id);
        $this->assertEquals($project->partner_id, $order->partner_id);
        $this->assertEquals(OrderState::DRAFT, $order->state);
    }

    /** @test */
    public function it_generates_complete_order_from_project(): void
    {
        $project = $this->createProjectWithFullStructure();

        // Create order
        $order = $this->projectToOrderService->createOrderForProject($project);

        // Generate lines
        $result = $this->projectToOrderService->generateOrderLinesFromProject($project, $order, [
            'include_cabinets' => true,
            'include_rooms' => true,
            'group_by_room' => true,
        ]);

        $this->assertTrue($result['success']);

        // Should have section headers for rooms + cabinet lines
        $sectionCount = $result['lines']->where('display_type', 'line_section')->count();
        $cabinetCount = $result['lines']->filter(fn($l) => !isset($l->display_type))->count();

        $this->assertGreaterThan(0, $sectionCount, 'Should have room section headers');
        $this->assertGreaterThan(0, $cabinetCount, 'Should have cabinet lines');
    }

    /** @test */
    public function it_calculates_totals_correctly_from_project(): void
    {
        $project = $this->createProjectWithPricing();
        $order = $this->projectToOrderService->createOrderForProject($project);

        $this->projectToOrderService->generateOrderLinesFromProject($project, $order, [
            'include_cabinets' => true,
            'include_rooms' => true,
        ]);

        $order->refresh();

        // Calculate expected total from project
        $expectedTotal = $project->cabinetSpecifications()->sum('total_price');
        $roomTotal = $project->rooms()->where('total_price', '>', 0)->sum('total_price');
        $expectedTotal += $roomTotal;

        $this->assertEquals($expectedTotal, $order->amount_total);
    }

    // =========================================================================
    // Full Workflow Integration Tests
    // =========================================================================

    /** @test */
    public function complete_workflow_from_quote_to_order_with_project(): void
    {
        // STEP 1: Create initial quote (e.g., from customer inquiry)
        $initialQuote = $this->createQuote([
            'name' => 'Kitchen Remodel Quote',
            'amount_total' => 25000.00,
        ]);

        // STEP 2: Create project from quote specifications
        $project = Project::create([
            'name' => 'Kitchen Remodel - Smith Residence',
            'partner_id' => $initialQuote->partner_id,
            'company_id' => $initialQuote->company_id,
            'stage_id' => $this->stage->id,
            'source_quote_id' => $initialQuote->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->user->id,
            'creator_id' => $this->user->id,
        ]);

        // Link quote to project
        $initialQuote->update(['project_id' => $project->id]);

        // STEP 3: Add detailed specifications to project
        $this->addProjectSpecifications($project);

        // STEP 4: Generate detailed quote from project specifications
        $detailedQuote = Order::create([
            'name' => 'Detailed Kitchen Quote',
            'partner_id' => $project->partner_id,
            'company_id' => $project->company_id,
            'currency_id' => $this->currency->id,
            'project_id' => $project->id,
            'state' => OrderState::DRAFT,
            'date_order' => now(),
            'user_id' => $this->user->id,
            'creator_id' => $this->user->id,
        ]);

        $result = $this->projectToOrderService->importFromProject($detailedQuote, $project);
        $this->assertTrue($result['success']);

        // STEP 5: Convert approved quote to sales order
        $salesOrder = $this->convertQuoteToOrder($detailedQuote);

        // VERIFY: Complete lineage and relationships
        $this->assertEquals($project->id, $salesOrder->project_id);
        $this->assertEquals($detailedQuote->id, $salesOrder->source_quote_id);
        $this->assertEquals(OrderState::SALE, $salesOrder->state);
        $this->assertGreaterThan(0, $salesOrder->lines()->count());

        // VERIFY: Project is linked to order
        $project->refresh();
        $this->assertEquals($initialQuote->id, $project->source_quote_id);
    }

    /** @test */
    public function it_handles_multiple_quotes_from_same_project(): void
    {
        $project = $this->createProjectWithFullStructure();

        // Create first quote
        $quote1 = $this->createQuote(['project_id' => $project->id, 'name' => 'Quote v1']);
        $this->projectToOrderService->importFromProject($quote1, $project);

        // Create second quote (revision)
        $quote2 = $this->createQuote(['project_id' => $project->id, 'name' => 'Quote v2']);
        $this->projectToOrderService->importFromProject($quote2, $project);

        // Both quotes should be linked to the same project
        $this->assertEquals($project->id, $quote1->project_id);
        $this->assertEquals($project->id, $quote2->project_id);

        // Both should have lines
        $this->assertGreaterThan(0, $quote1->lines()->count());
        $this->assertGreaterThan(0, $quote2->lines()->count());
    }

    /** @test */
    public function it_clears_existing_lines_on_reimport(): void
    {
        $project = $this->createProjectWithFullStructure();
        $quote = $this->createQuote(['project_id' => $project->id]);

        // First import
        $this->projectToOrderService->importFromProject($quote, $project);
        $firstCount = $quote->lines()->count();

        // Second import with clear_existing
        $this->projectToOrderService->importFromProject($quote, $project, [
            'clear_existing' => true,
        ]);

        $quote->refresh();
        $this->assertEquals($firstCount, $quote->lines()->count());
    }

    // =========================================================================
    // Edge Case Tests
    // =========================================================================

    /** @test */
    public function it_handles_project_with_no_cabinets(): void
    {
        $project = Project::create([
            'name' => 'Empty Project',
            'partner_id' => $this->partner->id,
            'company_id' => $this->company->id,
            'stage_id' => $this->stage->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->user->id,
            'creator_id' => $this->user->id,
        ]);

        $order = $this->projectToOrderService->createOrderForProject($project);
        $result = $this->projectToOrderService->generateOrderLinesFromProject($project, $order);

        $this->assertTrue($result['success']);
        // Should succeed but with no lines
    }

    /** @test */
    public function it_handles_cabinets_without_pricing(): void
    {
        $project = $this->createProjectWithFullStructure();

        // Update cabinets to have no pricing
        $project->cabinetSpecifications()->update([
            'unit_price_per_lf' => 0,
            'total_price' => 0,
        ]);

        $order = $this->projectToOrderService->createOrderForProject($project);
        $result = $this->projectToOrderService->generateOrderLinesFromProject($project, $order);

        $this->assertTrue($result['success']);
        // Lines should still be created, just with zero pricing
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    protected function createQuote(array $attributes = []): Order
    {
        return Order::create(array_merge([
            'name' => 'Quote-' . uniqid(),
            'partner_id' => $this->partner->id,
            'company_id' => $this->company->id,
            'currency_id' => $this->currency->id,
            'state' => OrderState::DRAFT,
            'date_order' => now(),
            'user_id' => $this->user->id,
            'creator_id' => $this->user->id,
        ], $attributes));
    }

    protected function createProjectWithFullStructure(): Project
    {
        $project = Project::create([
            'name' => 'Full Structure Project',
            'partner_id' => $this->partner->id,
            'company_id' => $this->company->id,
            'stage_id' => $this->stage->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->user->id,
            'creator_id' => $this->user->id,
        ]);

        $this->addProjectSpecifications($project);

        return $project;
    }

    protected function createProjectWithPricing(): Project
    {
        $project = Project::create([
            'name' => 'Priced Project',
            'partner_id' => $this->partner->id,
            'company_id' => $this->company->id,
            'stage_id' => $this->stage->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->user->id,
            'creator_id' => $this->user->id,
        ]);

        $room = Room::create([
            'project_id' => $project->id,
            'name' => 'Kitchen',
            'total_price' => 2000.00, // Room charge
        ]);

        // Add cabinets with pricing
        for ($i = 1; $i <= 3; $i++) {
            CabinetSpecification::create([
                'project_id' => $project->id,
                'room_id' => $room->id,
                'cabinet_number' => "K{$i}",
                'linear_feet' => 5.0,
                'unit_price_per_lf' => 150.00,
                'total_price' => 750.00, // 5 LF * $150
                'creator_id' => $this->user->id,
            ]);
        }

        return $project;
    }

    protected function addProjectSpecifications(Project $project): void
    {
        $room = Room::create([
            'project_id' => $project->id,
            'name' => 'Kitchen',
        ]);

        $location = RoomLocation::create([
            'room_id' => $room->id,
            'name' => 'North Wall',
        ]);

        $run = CabinetRun::create([
            'room_location_id' => $location->id,
            'name' => 'Run 1',
        ]);

        for ($i = 1; $i <= 3; $i++) {
            CabinetSpecification::create([
                'project_id' => $project->id,
                'room_id' => $room->id,
                'cabinet_run_id' => $run->id,
                'cabinet_number' => "K{$i}",
                'linear_feet' => 4.0 + ($i * 0.5),
                'unit_price_per_lf' => 150.00,
                'total_price' => (4.0 + ($i * 0.5)) * 150.00,
                'width_inches' => 24 + ($i * 6),
                'depth_inches' => 24,
                'height_inches' => 34,
                'cabinet_level' => 'base',
                'creator_id' => $this->user->id,
            ]);
        }
    }

    protected function addOrderLines(Order $order, int $count = 3): void
    {
        for ($i = 1; $i <= $count; $i++) {
            OrderLine::create([
                'order_id' => $order->id,
                'company_id' => $order->company_id,
                'currency_id' => $order->currency_id,
                'name' => "Line Item {$i}",
                'product_uom_qty' => $i * 5,
                'price_unit' => 100.00,
                'price_subtotal' => $i * 500.00,
                'price_total' => $i * 500.00,
                'sort' => $i,
            ]);
        }
    }

    protected function convertQuoteToOrder(Order $quote): Order
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($quote) {
            $orderData = $quote->replicate([
                'id',
                'name',
                'source_quote_id',
                'converted_from_quote_at',
                'created_at',
                'updated_at',
            ])->toArray();

            $orderData['state'] = OrderState::SALE;
            $orderData['source_quote_id'] = $quote->id;
            $orderData['converted_from_quote_at'] = now();
            $orderData['date_order'] = now();

            $order = Order::create($orderData);

            foreach ($quote->lines as $line) {
                $lineData = $line->replicate([
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

    /**
     * Check if required database tables exist for these tests
     */
    protected function hasRequiredTables(): bool
    {
        $requiredTables = [
            'projects_projects',
            'projects_project_stages',
            'projects_rooms',
            'projects_room_locations',
            'projects_cabinet_runs',
            'projects_cabinet_specifications',
            'sales_orders',
            'sales_order_lines',
            'inventories_warehouses',
            'inventories_locations',
        ];

        foreach ($requiredTables as $table) {
            if (!\Illuminate\Support\Facades\Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }
}
