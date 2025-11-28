<?php

namespace Tests\Integration;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Webkul\Inventory\Models\Location;
use Webkul\Inventory\Models\ProductQuantity;
use Webkul\Inventory\Models\Warehouse;
use Webkul\Partner\Models\Partner;
use Webkul\Product\Models\Product;
use Webkul\Project\Events\ProjectStageChanged;
use Webkul\Project\Listeners\HandleProjectStageChange;
use Webkul\Project\Models\CabinetMaterialsBom;
use Webkul\Project\Models\CabinetRun;
use Webkul\Project\Models\CabinetSpecification;
use Webkul\Project\Models\MaterialReservation;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\ProjectStage;
use Webkul\Project\Models\Room;
use Webkul\Project\Models\RoomLocation;
use Webkul\Project\Services\InventoryReservationService;
use Webkul\Project\Services\ProjectToOrderService;
use Webkul\Sale\Enums\OrderState;
use Webkul\Sale\Models\Order;
use Webkul\Sale\Models\OrderLine;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\Currency;

/**
 * End-to-End Workflow Tests
 *
 * Comprehensive tests covering real-world scenarios:
 * 1. Customer Inquiry → Quote → Project → Order → Production
 * 2. Clone project for new customer
 * 3. Multiple revisions workflow
 * 4. Material reservation through stage progression
 */
class EndToEndQuotationWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    protected ProjectToOrderService $projectToOrderService;
    protected InventoryReservationService $reservationService;
    protected HandleProjectStageChange $stageHandler;

    protected Company $company;
    protected Partner $customer;
    protected Currency $currency;
    protected Warehouse $warehouse;
    protected Location $stockLocation;
    protected \App\Models\User $user;

    protected ProjectStage $newStage;
    protected ProjectStage $quotedStage;
    protected ProjectStage $approvedStage;
    protected ProjectStage $materialReservedStage;
    protected ProjectStage $materialIssuedStage;
    protected ProjectStage $productionStage;
    protected ProjectStage $completedStage;

    protected function setUp(): void
    {
        parent::setUp();

        // Check if required tables exist
        if (!$this->hasRequiredTables()) {
            $this->markTestSkipped('Required project/sales/inventory tables not available. Run all migrations first.');
        }

        // Get or create a test user
        $this->user = \App\Models\User::first() ?? \App\Models\User::factory()->create();

        $this->projectToOrderService = new ProjectToOrderService();
        $this->reservationService = new InventoryReservationService();
        $this->stageHandler = new HandleProjectStageChange($this->reservationService);

        // Setup base data
        $this->setupCompanyAndPartner();
        $this->setupWarehouse();
        $this->setupProjectStages();
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
            'projects_cabinet_materials_bom',
            'projects_material_reservations',
            'sales_orders',
            'sales_order_lines',
            'inventories_warehouses',
            'inventories_locations',
            'inventories_product_quantities',
        ];

        foreach ($requiredTables as $table) {
            if (!\Illuminate\Support\Facades\Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    // =========================================================================
    // SCENARIO 1: Complete Sales Workflow
    // Customer Inquiry → Quote → Project → Specifications → Order → Production
    // =========================================================================

    /** @test */
    public function scenario_complete_sales_workflow(): void
    {
        // =====================================================================
        // PHASE 1: Initial Customer Inquiry / Quote Creation
        // =====================================================================

        // Customer contacts us for a kitchen remodel
        $initialQuote = Order::create([
            'name' => 'Q-2024-0001',
            'partner_id' => $this->customer->id,
            'company_id' => $this->company->id,
            'currency_id' => $this->currency->id,
            'state' => OrderState::DRAFT,
            'date_order' => now(),
            'user_id' => $this->user->id,
            'creator_id' => $this->user->id,
            'note' => 'Customer requested quote for kitchen cabinet replacement',
        ]);

        // Add rough estimate lines
        OrderLine::create([
            'order_id' => $initialQuote->id,
            'company_id' => $this->company->id,
            'currency_id' => $this->currency->id,
            'name' => 'Kitchen Cabinet Estimate (Approx. 40 LF)',
            'product_uom_qty' => 40,
            'price_unit' => 175.00,
            'price_subtotal' => 7000.00,
            'price_total' => 7000.00,
            'sort' => 1,
        ]);

        $this->assertEquals(OrderState::DRAFT, $initialQuote->state);
        $this->assertEquals(7000.00, $initialQuote->lines()->sum('price_total'));

        // =====================================================================
        // PHASE 2: Create Project for Detailed Specifications
        // =====================================================================

        $project = Project::create([
            'name' => 'Johnson Kitchen Remodel',
            'partner_id' => $this->customer->id,
            'company_id' => $this->company->id,
            'stage_id' => $this->newStage->id,
            'source_quote_id' => $initialQuote->id,
            'warehouse_id' => $this->warehouse->id,
            'project_type' => 'kitchen',
            'estimated_linear_feet' => 45,
            'user_id' => $this->user->id,
            'creator_id' => $this->user->id,
        ]);

        // Link quote to project
        $initialQuote->update(['project_id' => $project->id]);

        $this->assertEquals($initialQuote->id, $project->source_quote_id);
        $this->assertEquals($project->id, $initialQuote->project_id);

        // =====================================================================
        // PHASE 3: Add Detailed Specifications to Project
        // =====================================================================

        // After site visit, add detailed room and cabinet specifications
        $kitchen = Room::create([
            'project_id' => $project->id,
            'name' => 'Kitchen',
            'total_price' => 500.00, // Room setup charge
        ]);

        $northWall = RoomLocation::create([
            'room_id' => $kitchen->id,
            'name' => 'North Wall',
        ]);

        $baseRun = CabinetRun::create([
            'room_location_id' => $northWall->id,
            'name' => 'Base Cabinet Run',
        ]);

        $upperRun = CabinetRun::create([
            'room_location_id' => $northWall->id,
            'name' => 'Upper Cabinet Run',
        ]);

        // Add base cabinets
        $baseCabinets = [
            ['number' => 'B1', 'width' => 36, 'height' => 34, 'depth' => 24, 'lf' => 3.0, 'price' => 175],
            ['number' => 'B2', 'width' => 30, 'height' => 34, 'depth' => 24, 'lf' => 2.5, 'price' => 175],
            ['number' => 'B3', 'width' => 24, 'height' => 34, 'depth' => 24, 'lf' => 2.0, 'price' => 175],
            ['number' => 'B-Sink', 'width' => 36, 'height' => 34, 'depth' => 24, 'lf' => 3.0, 'price' => 200], // Sink base
        ];

        foreach ($baseCabinets as $cab) {
            CabinetSpecification::create([
                'project_id' => $project->id,
                'room_id' => $kitchen->id,
                'cabinet_run_id' => $baseRun->id,
                'cabinet_number' => $cab['number'],
                'cabinet_level' => 'base',
                'width_inches' => $cab['width'],
                'height_inches' => $cab['height'],
                'depth_inches' => $cab['depth'],
                'linear_feet' => $cab['lf'],
                'unit_price_per_lf' => $cab['price'],
                'total_price' => $cab['lf'] * $cab['price'],
                'material_category' => 'Maple',
                'finish_option' => 'Natural',
                'creator_id' => $this->user->id,
            ]);
        }

        // Add upper cabinets
        $upperCabinets = [
            ['number' => 'U1', 'width' => 36, 'height' => 30, 'depth' => 12, 'lf' => 3.0, 'price' => 150],
            ['number' => 'U2', 'width' => 30, 'height' => 30, 'depth' => 12, 'lf' => 2.5, 'price' => 150],
            ['number' => 'U3', 'width' => 24, 'height' => 30, 'depth' => 12, 'lf' => 2.0, 'price' => 150],
        ];

        foreach ($upperCabinets as $cab) {
            CabinetSpecification::create([
                'project_id' => $project->id,
                'room_id' => $kitchen->id,
                'cabinet_run_id' => $upperRun->id,
                'cabinet_number' => $cab['number'],
                'cabinet_level' => 'upper',
                'width_inches' => $cab['width'],
                'height_inches' => $cab['height'],
                'depth_inches' => $cab['depth'],
                'linear_feet' => $cab['lf'],
                'unit_price_per_lf' => $cab['price'],
                'total_price' => $cab['lf'] * $cab['price'],
                'material_category' => 'Maple',
                'finish_option' => 'Natural',
                'creator_id' => $this->user->id,
            ]);
        }

        // Verify specifications
        $this->assertEquals(7, $project->cabinetSpecifications()->count());
        $cabinetTotal = $project->cabinetSpecifications()->sum('total_price');
        $this->assertGreaterThan(0, $cabinetTotal);

        // =====================================================================
        // PHASE 4: Generate Detailed Quote from Project Specifications
        // =====================================================================

        $detailedQuote = Order::create([
            'name' => 'Q-2024-0001-R1', // Revision 1
            'partner_id' => $project->partner_id,
            'company_id' => $project->company_id,
            'currency_id' => $this->currency->id,
            'project_id' => $project->id,
            'state' => OrderState::DRAFT,
            'date_order' => now(),
            'user_id' => $this->user->id,
            'creator_id' => $this->user->id,
        ]);

        // Import specifications from project
        $result = $this->projectToOrderService->importFromProject($detailedQuote, $project, [
            'include_cabinets' => true,
            'include_rooms' => true,
            'group_by_room' => true,
        ]);

        $this->assertTrue($result['success']);
        $this->assertGreaterThan(0, $detailedQuote->lines()->count());

        // Verify quote totals match project
        $detailedQuote->refresh();
        $expectedTotal = $cabinetTotal + 500.00; // cabinets + room charge
        $this->assertEquals($expectedTotal, $detailedQuote->amount_total);

        // =====================================================================
        // PHASE 5: Customer Approves - Convert Quote to Sales Order
        // =====================================================================

        // Send quote to customer
        $detailedQuote->update(['state' => OrderState::SENT]);

        // Customer approves - convert to sales order
        $salesOrder = DB::transaction(function () use ($detailedQuote) {
            $orderData = $detailedQuote->replicate([
                'id', 'name', 'source_quote_id', 'converted_from_quote_at',
                'created_at', 'updated_at',
            ])->toArray();

            $orderData['name'] = 'SO-2024-0001';
            $orderData['state'] = OrderState::SALE;
            $orderData['source_quote_id'] = $detailedQuote->id;
            $orderData['converted_from_quote_at'] = now();
            $orderData['date_order'] = now();

            $order = Order::create($orderData);

            foreach ($detailedQuote->lines as $line) {
                $lineData = $line->replicate(['id', 'order_id', 'created_at', 'updated_at'])->toArray();
                $lineData['order_id'] = $order->id;
                OrderLine::create($lineData);
            }

            return $order;
        });

        // Verify conversion
        $this->assertEquals(OrderState::SALE, $salesOrder->state);
        $this->assertEquals($detailedQuote->id, $salesOrder->source_quote_id);
        $this->assertEquals($project->id, $salesOrder->project_id);
        $this->assertNotNull($salesOrder->converted_from_quote_at);

        // =====================================================================
        // PHASE 6: Add BOM Materials and Reserve Inventory
        // =====================================================================

        // Add materials to cabinet specifications
        $this->addMaterialsBom($project);
        $this->addInventory(200);

        // Move project to Material Reserved stage
        $project->update(['stage_id' => $this->approvedStage->id]);

        $event = new ProjectStageChanged($project, $this->approvedStage, $this->materialReservedStage);
        $this->stageHandler->handle($event);

        // Verify reservations
        $reservations = MaterialReservation::forProject($project->id)->reserved()->count();
        $this->assertGreaterThan(0, $reservations);

        // =====================================================================
        // PHASE 7: Issue Materials and Start Production
        // =====================================================================

        $event = new ProjectStageChanged($project, $this->materialReservedStage, $this->materialIssuedStage);
        $this->stageHandler->handle($event);

        $issuedCount = MaterialReservation::forProject($project->id)
            ->where('status', MaterialReservation::STATUS_ISSUED)
            ->count();
        $this->assertGreaterThan(0, $issuedCount);

        // Move to production
        $project->update(['stage_id' => $this->productionStage->id]);

        // =====================================================================
        // FINAL VERIFICATION: Complete Workflow Integrity
        // =====================================================================

        // Verify complete chain
        $this->assertNotNull($initialQuote->project_id);
        $this->assertEquals($initialQuote->id, $project->source_quote_id);
        $this->assertEquals($project->id, $detailedQuote->project_id);
        $this->assertEquals($detailedQuote->id, $salesOrder->source_quote_id);
        $this->assertEquals($project->id, $salesOrder->project_id);

        // Verify all orders are in correct states
        $initialQuote->refresh();
        $salesOrder->refresh();
        $this->assertEquals(OrderState::SALE, $salesOrder->state);
    }

    // =========================================================================
    // SCENARIO 2: Clone Project for New Customer
    // =========================================================================

    /** @test */
    public function scenario_clone_project_for_new_customer(): void
    {
        // Create original project with full specifications
        $originalProject = $this->createFullySpecifiedProject('Original Kitchen Project');

        // Create new customer
        $newCustomer = Partner::create([
            'name' => 'New Customer Corp',
            'company_id' => $this->company->id,
            'is_customer' => true,
        ]);

        // Clone project for new customer
        $clonedProject = DB::transaction(function () use ($originalProject, $newCustomer) {
            $projectData = $originalProject->replicate([
                'id', 'project_number', 'source_quote_id',
                'created_at', 'updated_at', 'deleted_at',
            ])->toArray();

            $projectData['name'] = 'Kitchen Project - New Customer';
            $projectData['partner_id'] = $newCustomer->id;
            $projectData['stage_id'] = $this->newStage->id;
            $projectData['source_quote_id'] = null;

            $newProject = Project::create($projectData);

            // Clone rooms and cabinets
            $this->cloneProjectStructure($originalProject, $newProject);

            return $newProject;
        });

        // Verify clone
        $this->assertNotEquals($originalProject->id, $clonedProject->id);
        $this->assertEquals($newCustomer->id, $clonedProject->partner_id);
        $this->assertNull($clonedProject->source_quote_id);

        // Verify specifications were cloned
        $this->assertEquals(
            $originalProject->rooms()->count(),
            $clonedProject->rooms()->count()
        );

        $this->assertEquals(
            $originalProject->cabinetSpecifications()->count(),
            $clonedProject->cabinetSpecifications()->count()
        );

        // Create quote for new customer from cloned project
        $newQuote = Order::create([
            'name' => 'Q-NEW-0001',
            'partner_id' => $newCustomer->id,
            'company_id' => $this->company->id,
            'currency_id' => $this->currency->id,
            'project_id' => $clonedProject->id,
            'state' => OrderState::DRAFT,
            'date_order' => now(),
            'user_id' => $this->user->id,
            'creator_id' => $this->user->id,
        ]);

        $result = $this->projectToOrderService->importFromProject($newQuote, $clonedProject);

        $this->assertTrue($result['success']);
        $this->assertGreaterThan(0, $newQuote->lines()->count());
    }

    // =========================================================================
    // SCENARIO 3: Multiple Quote Revisions
    // =========================================================================

    /** @test */
    public function scenario_multiple_quote_revisions(): void
    {
        $project = $this->createFullySpecifiedProject('Revision Test Project');

        // Create initial quote
        $quoteV1 = Order::create([
            'name' => 'Q-REV-0001',
            'partner_id' => $this->customer->id,
            'company_id' => $this->company->id,
            'currency_id' => $this->currency->id,
            'project_id' => $project->id,
            'state' => OrderState::DRAFT,
            'date_order' => now(),
            'user_id' => $this->user->id,
            'creator_id' => $this->user->id,
        ]);

        $this->projectToOrderService->importFromProject($quoteV1, $project);
        $v1Total = $quoteV1->refresh()->amount_total;

        // Customer requests changes - add more cabinets
        CabinetSpecification::create([
            'project_id' => $project->id,
            'room_id' => $project->rooms()->first()->id,
            'cabinet_number' => 'Extra-1',
            'cabinet_level' => 'base',
            'linear_feet' => 4.0,
            'unit_price_per_lf' => 175.00,
            'total_price' => 700.00,
            'creator_id' => $this->user->id,
        ]);

        // Create revision 2
        $quoteV2 = Order::create([
            'name' => 'Q-REV-0001-R2',
            'partner_id' => $this->customer->id,
            'company_id' => $this->company->id,
            'currency_id' => $this->currency->id,
            'project_id' => $project->id,
            'state' => OrderState::DRAFT,
            'date_order' => now(),
            'user_id' => $this->user->id,
            'creator_id' => $this->user->id,
        ]);

        $this->projectToOrderService->importFromProject($quoteV2, $project);
        $v2Total = $quoteV2->refresh()->amount_total;

        // V2 should be higher
        $this->assertGreaterThan($v1Total, $v2Total);

        // Both quotes linked to same project
        $this->assertEquals($project->id, $quoteV1->project_id);
        $this->assertEquals($project->id, $quoteV2->project_id);

        // Can track all quotes for a project
        $projectQuotes = Order::where('project_id', $project->id)->count();
        $this->assertEquals(2, $projectQuotes);
    }

    // =========================================================================
    // SCENARIO 4: Material Shortage Handling
    // =========================================================================

    /** @test */
    public function scenario_material_shortage_handling(): void
    {
        $project = $this->createFullySpecifiedProject('Material Shortage Test');
        $this->addMaterialsBom($project);

        // Add only partial inventory (not enough for all materials)
        $this->addInventory(10); // Only 10 units available

        // Try to reserve materials
        $result = $this->reservationService->reserveMaterialsForProject($project);

        // Should succeed for some, fail for others
        $this->assertIsArray($result);
        $this->assertArrayHasKey('reservations', $result);
        $this->assertArrayHasKey('errors', $result);

        // Some reservations may have failed due to shortage
        if (!empty($result['errors'])) {
            foreach ($result['errors'] as $error) {
                $this->assertStringContainsString('Insufficient', $error);
            }
        }

        // Get summary to see status
        $summary = $this->reservationService->getProjectReservationSummary($project);
        $this->assertIsArray($summary);
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    protected function setupCompanyAndPartner(): void
    {
        $this->company = Company::firstOrCreate(
            ['name' => 'TCS Woodwork E2E Test'],
            ['is_active' => true]
        );

        $this->currency = Currency::firstOrCreate(
            ['code' => 'USD'],
            ['name' => 'US Dollar', 'symbol' => '$', 'is_default' => true]
        );

        $this->customer = Partner::firstOrCreate(
            ['name' => 'E2E Test Customer'],
            ['company_id' => $this->company->id, 'is_customer' => true]
        );
    }

    protected function setupWarehouse(): void
    {
        $this->stockLocation = Location::firstOrCreate(
            ['name' => 'E2E Stock'],
            ['type' => 'internal', 'company_id' => $this->company->id]
        );

        $outputLocation = Location::firstOrCreate(
            ['name' => 'E2E Output'],
            ['type' => 'transit', 'company_id' => $this->company->id]
        );

        $this->warehouse = Warehouse::firstOrCreate(
            ['name' => 'E2E Warehouse'],
            [
                'code' => 'E2E',
                'company_id' => $this->company->id,
                'partner_id' => $this->customer->id,
                'lot_stock_location_id' => $this->stockLocation->id,
                'output_stock_location_id' => $outputLocation->id,
            ]
        );
    }

    protected function setupProjectStages(): void
    {
        $stages = [
            ['name' => 'New', 'key' => 'new', 'sort' => 1],
            ['name' => 'Quoted', 'key' => 'quoted', 'sort' => 2],
            ['name' => 'Approved', 'key' => 'approved', 'sort' => 3],
            ['name' => 'Material Reserved', 'key' => 'material_reserved', 'sort' => 4],
            ['name' => 'Material Issued', 'key' => 'material_issued', 'sort' => 5],
            ['name' => 'Production', 'key' => 'production', 'sort' => 6],
            ['name' => 'Completed', 'key' => 'completed', 'sort' => 7],
        ];

        foreach ($stages as $stageData) {
            $stage = ProjectStage::firstOrCreate(
                ['name' => $stageData['name'], 'company_id' => $this->company->id],
                ['sort' => $stageData['sort']]
            );

            $propertyName = str_replace([' ', '-'], '', lcfirst($stageData['name'])) . 'Stage';
            $this->{$propertyName} = $stage;
        }
    }

    protected function createFullySpecifiedProject(string $name): Project
    {
        $project = Project::create([
            'name' => $name,
            'partner_id' => $this->customer->id,
            'company_id' => $this->company->id,
            'stage_id' => $this->newStage->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->user->id,
            'creator_id' => $this->user->id,
        ]);

        $room = Room::create([
            'project_id' => $project->id,
            'name' => 'Kitchen',
            'total_price' => 500.00,
        ]);

        $location = RoomLocation::create([
            'room_id' => $room->id,
            'name' => 'North Wall',
        ]);

        $run = CabinetRun::create([
            'room_location_id' => $location->id,
            'name' => 'Main Run',
        ]);

        for ($i = 1; $i <= 5; $i++) {
            CabinetSpecification::create([
                'project_id' => $project->id,
                'room_id' => $room->id,
                'cabinet_run_id' => $run->id,
                'cabinet_number' => "CAB-{$i}",
                'cabinet_level' => $i <= 3 ? 'base' : 'upper',
                'width_inches' => 30,
                'height_inches' => $i <= 3 ? 34 : 30,
                'depth_inches' => $i <= 3 ? 24 : 12,
                'linear_feet' => 2.5,
                'unit_price_per_lf' => $i <= 3 ? 175.00 : 150.00,
                'total_price' => 2.5 * ($i <= 3 ? 175.00 : 150.00),
                'creator_id' => $this->user->id,
            ]);
        }

        return $project;
    }

    protected function cloneProjectStructure(Project $source, Project $target): void
    {
        foreach ($source->rooms as $room) {
            $newRoom = Room::create([
                'project_id' => $target->id,
                'name' => $room->name,
                'total_price' => $room->total_price,
            ]);

            foreach ($room->roomLocations as $location) {
                $newLocation = RoomLocation::create([
                    'room_id' => $newRoom->id,
                    'name' => $location->name,
                ]);

                foreach ($location->cabinetRuns as $run) {
                    $newRun = CabinetRun::create([
                        'room_location_id' => $newLocation->id,
                        'name' => $run->name,
                    ]);

                    foreach ($run->cabinetSpecifications as $cabinet) {
                        $cabData = $cabinet->replicate([
                            'id', 'project_id', 'room_id', 'cabinet_run_id',
                            'created_at', 'updated_at',
                        ])->toArray();

                        $cabData['project_id'] = $target->id;
                        $cabData['room_id'] = $newRoom->id;
                        $cabData['cabinet_run_id'] = $newRun->id;

                        CabinetSpecification::create($cabData);
                    }
                }
            }
        }
    }

    protected function addMaterialsBom(Project $project): void
    {
        $materials = [
            ['name' => 'Maple Plywood', 'qty' => 20],
            ['name' => 'Hinges', 'qty' => 30],
            ['name' => 'Drawer Slides', 'qty' => 15],
        ];

        $cabinet = $project->cabinetSpecifications()->first();

        foreach ($materials as $mat) {
            $product = Product::create([
                'name' => $mat['name'],
                'type' => 'product',
                'is_active' => true,
                'company_id' => $this->company->id,
            ]);

            CabinetMaterialsBom::create([
                'cabinet_specification_id' => $cabinet->id,
                'component_name' => $mat['name'],
                'product_id' => $product->id,
                'quantity_required' => $mat['qty'],
                'unit_of_measure' => 'unit',
                'material_allocated' => false,
            ]);
        }
    }

    protected function addInventory(float $quantity): void
    {
        $productIds = Product::where('company_id', $this->company->id)->pluck('id');

        foreach ($productIds as $productId) {
            ProductQuantity::updateOrCreate(
                [
                    'product_id' => $productId,
                    'location_id' => $this->stockLocation->id,
                ],
                [
                    'quantity' => $quantity,
                    'reserved_quantity' => 0,
                ]
            );
        }
    }
}
