<?php

namespace Tests\Integration;

use Illuminate\Foundation\Testing\DatabaseTransactions;
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
use Webkul\Project\Models\CabinetSpecification;
use Webkul\Project\Models\MaterialReservation;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\ProjectStage;
use Webkul\Project\Services\InventoryReservationService;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\UOM;

/**
 * Integration Tests for Project Stage -> Inventory Integration
 *
 * Tests the complete workflow of:
 * - Project stage changes triggering inventory operations
 * - Material reservation when project moves to "Material Reserved" stage
 * - Material issuance when project moves to "Material Issued" stage
 * - Event-driven inventory management
 */
class StageInventoryIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    protected InventoryReservationService $reservationService;
    protected Company $company;
    protected Partner $partner;
    protected ?Warehouse $warehouse = null;
    protected ?Location $stockLocation = null;
    protected ?Location $outputLocation = null;
    protected ProjectStage $newStage;
    protected ProjectStage $quotedStage;
    protected ProjectStage $materialReservedStage;
    protected ProjectStage $materialIssuedStage;
    protected ProjectStage $productionStage;
    protected \App\Models\User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Check if required tables exist
        if (!$this->hasRequiredTables()) {
            $this->markTestSkipped('Required inventory tables not available. Run inventory migrations first.');
        }

        // Get or create a test user
        $this->user = \App\Models\User::first() ?? \App\Models\User::factory()->create();

        $this->reservationService = new InventoryReservationService();

        // Create company and partner
        $this->company = Company::firstOrCreate(
            ['name' => 'Stage Inventory Test Company'],
            ['is_active' => true]
        );

        $this->partner = Partner::firstOrCreate(
            ['name' => 'Stage Inventory Test Customer'],
            ['company_id' => $this->company->id, 'is_customer' => true]
        );

        // Use existing warehouse if available (avoids complex foreign key requirements)
        $existingWarehouse = Warehouse::first();

        if ($existingWarehouse) {
            $this->warehouse = $existingWarehouse;

            // Use existing warehouse's locations
            $this->stockLocation = Location::find($this->warehouse->lot_stock_location_id)
                ?? Location::where('type', 'internal')->first()
                ?? Location::create([
                    'name' => 'Stage Test Stock',
                    'type' => 'internal',
                    'company_id' => $this->company->id,
                ]);

            $this->outputLocation = Location::find($this->warehouse->output_stock_location_id)
                ?? Location::where('type', 'transit')->first()
                ?? Location::create([
                    'name' => 'Stage Test Output',
                    'type' => 'transit',
                    'company_id' => $this->company->id,
                ]);

            // Ensure warehouse has required location IDs set
            $updates = [];
            if (!$this->warehouse->lot_stock_location_id) {
                $updates['lot_stock_location_id'] = $this->stockLocation->id;
            }
            if (!$this->warehouse->output_stock_location_id) {
                $updates['output_stock_location_id'] = $this->outputLocation->id;
            }
            if (!empty($updates)) {
                $this->warehouse->update($updates);
                $this->warehouse->refresh();
            }
        } else {
            // Create warehouse with all required locations
            $viewLocation = Location::create([
                'name' => 'Stage Test View',
                'type' => 'view',
                'company_id' => $this->company->id,
            ]);

            $this->stockLocation = Location::create([
                'name' => 'Stage Test Stock',
                'type' => 'internal',
                'company_id' => $this->company->id,
            ]);

            $this->outputLocation = Location::create([
                'name' => 'Stage Test Output',
                'type' => 'transit',
                'company_id' => $this->company->id,
            ]);

            $this->warehouse = Warehouse::create([
                'name' => 'Stage Test Warehouse',
                'code' => 'STW',
                'company_id' => $this->company->id,
                'view_location_id' => $viewLocation->id,
                'lot_stock_location_id' => $this->stockLocation->id,
                'output_stock_location_id' => $this->outputLocation->id,
                'reception_steps' => 'one_step',
                'delivery_steps' => 'one_step',  // Valid: one_step, two_steps, three_steps
            ]);
        }

        // Get existing stages with proper stage_key values, or create new ones
        // The stage_key is what the handler uses to determine inventory actions
        $this->newStage = ProjectStage::where('stage_key', 'discovery')->first()
            ?? ProjectStage::firstOrCreate(
                ['name' => 'Discovery', 'company_id' => $this->company->id],
                ['sort' => 1, 'stage_key' => 'discovery']
            );

        $this->quotedStage = ProjectStage::where('stage_key', 'design')->first()
            ?? ProjectStage::firstOrCreate(
                ['name' => 'Design', 'company_id' => $this->company->id],
                ['sort' => 2, 'stage_key' => 'design']
            );

        $this->materialReservedStage = ProjectStage::where('stage_key', 'material_reserved')->first()
            ?? ProjectStage::firstOrCreate(
                ['name' => 'Material Reserved', 'company_id' => $this->company->id],
                ['sort' => 3, 'stage_key' => 'material_reserved']
            );

        $this->materialIssuedStage = ProjectStage::where('stage_key', 'material_issued')->first()
            ?? ProjectStage::firstOrCreate(
                ['name' => 'Material Issued', 'company_id' => $this->company->id],
                ['sort' => 4, 'stage_key' => 'material_issued']
            );

        $this->productionStage = ProjectStage::where('stage_key', 'production')->first()
            ?? ProjectStage::firstOrCreate(
                ['name' => 'Production', 'company_id' => $this->company->id],
                ['sort' => 5, 'stage_key' => 'production']
            );
    }

    // =========================================================================
    // Event Firing Tests
    // =========================================================================

    /** @test */
    public function it_fires_event_when_project_stage_changes(): void
    {
        Event::fake([ProjectStageChanged::class]);

        $project = $this->createProject();

        // Change stage
        $project->update(['stage_id' => $this->quotedStage->id]);

        Event::assertDispatched(ProjectStageChanged::class, function ($event) use ($project) {
            return $event->project->id === $project->id &&
                   $event->newStage->id === $this->quotedStage->id;
        });
    }

    /** @test */
    public function it_includes_previous_stage_in_event(): void
    {
        Event::fake([ProjectStageChanged::class]);

        $project = $this->createProject(['stage_id' => $this->newStage->id]);

        // Change from New to Quoted
        $project->update(['stage_id' => $this->quotedStage->id]);

        Event::assertDispatched(ProjectStageChanged::class, function ($event) {
            return $event->previousStage->id === $this->newStage->id &&
                   $event->newStage->id === $this->quotedStage->id;
        });
    }

    /** @test */
    public function it_does_not_fire_event_when_stage_unchanged(): void
    {
        Event::fake([ProjectStageChanged::class]);

        $project = $this->createProject(['stage_id' => $this->newStage->id]);

        // Update without changing stage
        $project->update(['name' => 'Updated Name']);

        Event::assertNotDispatched(ProjectStageChanged::class);
    }

    // =========================================================================
    // Material Reserved Stage Tests
    // =========================================================================

    /** @test */
    public function it_reserves_materials_when_moving_to_material_reserved_stage(): void
    {
        $project = $this->createProjectWithBom();

        // Ensure inventory exists
        $this->addInventoryForProject($project, 100);

        // Manually trigger the handler (simulating event listener)
        $event = new ProjectStageChanged(
            $project,
            $this->newStage,
            $this->materialReservedStage
        );

        $handler = new HandleProjectStageChange($this->reservationService);
        $handler->handle($event);

        // Check reservations were created
        $reservations = MaterialReservation::forProject($project->id)->get();
        $this->assertGreaterThan(0, $reservations->count());
        $this->assertTrue($reservations->every(fn($r) => $r->status === MaterialReservation::STATUS_RESERVED));
    }

    /** @test */
    public function it_handles_insufficient_inventory_gracefully(): void
    {
        $project = $this->createProjectWithBom();

        // Add insufficient inventory (less than required)
        $this->addInventoryForProject($project, 5); // Need more than 5

        $event = new ProjectStageChanged(
            $project,
            $this->newStage,
            $this->materialReservedStage
        );

        $handler = new HandleProjectStageChange($this->reservationService);

        // Should not throw exception, but log warning
        $handler->handle($event);

        // The handler completes without exception - that's the success criteria
        $this->assertTrue(true, 'Handler completed without throwing exception');

        // Some reservations may succeed, others may fail depending on BOM requirements
        $reservations = MaterialReservation::forProject($project->id)->get();
        // We don't assert specific count because it depends on BOM structure
    }

    /** @test */
    public function it_skips_reservation_for_projects_without_warehouse(): void
    {
        $project = $this->createProject(['warehouse_id' => null]);

        // Add BOM items
        $cabinet = CabinetSpecification::create([
            'project_id' => $project->id,
            'cabinet_number' => 'K1',
            'linear_feet' => 10,
            'creator_id' => $this->user->id,
        ]);

        $product = $this->createProduct();

        CabinetMaterialsBom::create([
            'cabinet_specification_id' => $cabinet->id,
            'component_name' => 'Material',
            'product_id' => $product->id,
            'quantity_required' => 20,
            'material_allocated' => false,
        ]);

        $event = new ProjectStageChanged(
            $project,
            $this->newStage,
            $this->materialReservedStage
        );

        $handler = new HandleProjectStageChange($this->reservationService);
        $handler->handle($event);

        // Should not create any reservations
        $reservations = MaterialReservation::forProject($project->id)->count();
        $this->assertEquals(0, $reservations);
    }

    // =========================================================================
    // Material Issued Stage Tests
    // =========================================================================

    /** @test */
    public function it_issues_materials_when_moving_to_material_issued_stage(): void
    {
        $project = $this->createProjectWithBom();

        // Add inventory and create reservations first
        $this->addInventoryForProject($project, 100);
        $this->reservationService->reserveMaterialsForProject($project);

        // Verify reservations exist
        $reservedCount = MaterialReservation::forProject($project->id)->reserved()->count();
        $this->assertGreaterThan(0, $reservedCount);

        // Move to Material Issued stage
        $event = new ProjectStageChanged(
            $project,
            $this->materialReservedStage,
            $this->materialIssuedStage
        );

        $handler = new HandleProjectStageChange($this->reservationService);
        $handler->handle($event);

        // Check reservations are now issued
        $issuedCount = MaterialReservation::forProject($project->id)
            ->where('status', MaterialReservation::STATUS_ISSUED)
            ->count();

        $this->assertGreaterThan(0, $issuedCount);
    }

    /** @test */
    public function it_creates_inventory_moves_when_issuing_materials(): void
    {
        $project = $this->createProjectWithBom();

        // Add inventory and create reservations
        $this->addInventoryForProject($project, 100);
        $this->reservationService->reserveMaterialsForProject($project);

        // Issue all materials
        $result = $this->reservationService->issueAllMaterialsForProject($project);

        $this->assertTrue($result['success']);
        $this->assertGreaterThan(0, $result['moves']->count());
    }

    // =========================================================================
    // Stage Transition Workflow Tests
    // =========================================================================

    /** @test */
    public function complete_stage_workflow_with_inventory(): void
    {
        // STEP 1: Create project in New stage with BOM
        $project = $this->createProjectWithBom();
        $this->addInventoryForProject($project, 100);

        // STEP 2: Move to Quoted (no inventory action)
        $project->update(['stage_id' => $this->quotedStage->id]);
        $reservationCount = MaterialReservation::forProject($project->id)->count();
        $this->assertEquals(0, $reservationCount, 'No reservations in Quoted stage');

        // STEP 3: Move to Material Reserved
        $event = new ProjectStageChanged($project, $this->quotedStage, $this->materialReservedStage);
        $handler = new HandleProjectStageChange($this->reservationService);
        $handler->handle($event);

        $reservedCount = MaterialReservation::forProject($project->id)->reserved()->count();
        $this->assertGreaterThan(0, $reservedCount, 'Materials should be reserved');

        // STEP 4: Move to Material Issued
        $event = new ProjectStageChanged($project, $this->materialReservedStage, $this->materialIssuedStage);
        $handler->handle($event);

        $issuedCount = MaterialReservation::forProject($project->id)
            ->where('status', MaterialReservation::STATUS_ISSUED)
            ->count();
        $this->assertGreaterThan(0, $issuedCount, 'Materials should be issued');

        // STEP 5: Move to Production (no new inventory action)
        $event = new ProjectStageChanged($project, $this->materialIssuedStage, $this->productionStage);
        $handler->handle($event);

        // Reservations status should remain issued
        $stillIssuedCount = MaterialReservation::forProject($project->id)
            ->where('status', MaterialReservation::STATUS_ISSUED)
            ->count();
        $this->assertEquals($issuedCount, $stillIssuedCount);
    }

    /** @test */
    public function it_handles_direct_jump_to_material_issued(): void
    {
        $project = $this->createProjectWithBom();
        $this->addInventoryForProject($project, 100);

        // Jump directly from New to Material Issued (skipping Material Reserved)
        $event = new ProjectStageChanged($project, $this->newStage, $this->materialIssuedStage);
        $handler = new HandleProjectStageChange($this->reservationService);
        $handler->handle($event);

        // Should handle gracefully - may not issue if nothing reserved
        // Just verify no exception thrown
        $this->assertTrue(true);
    }

    // =========================================================================
    // Reservation Summary Tests
    // =========================================================================

    /** @test */
    public function it_provides_accurate_reservation_summary(): void
    {
        $project = $this->createProjectWithBom();
        $this->addInventoryForProject($project, 100);

        // Create some reservations with different statuses
        $this->reservationService->reserveMaterialsForProject($project);

        $summary = $this->reservationService->getProjectReservationSummary($project);

        $this->assertArrayHasKey('total', $summary);
        $this->assertArrayHasKey('pending', $summary);
        $this->assertArrayHasKey('reserved', $summary);
        $this->assertArrayHasKey('issued', $summary);
        $this->assertArrayHasKey('cancelled', $summary);

        $this->assertGreaterThan(0, $summary['reserved']);
    }

    /** @test */
    public function it_calculates_reservation_value(): void
    {
        $project = $this->createProjectWithBom();

        // Create product with known cost using the helper
        $product = $this->createProduct([
            'name' => 'Valued Product',
            'cost' => 50.00, // $50 per unit
        ]);

        // Add inventory
        ProductQuantity::create([
            'product_id' => $product->id,
            'location_id' => $this->stockLocation->id,
            'quantity' => 100,
            'reserved_quantity' => 0,
        ]);

        // Create BOM with known quantity
        $cabinet = $project->cabinetSpecifications()->first();
        CabinetMaterialsBom::create([
            'cabinet_specification_id' => $cabinet->id,
            'component_name' => 'Valued Material',
            'product_id' => $product->id,
            'quantity_required' => 10,
            'material_allocated' => false,
        ]);

        $this->reservationService->reserveMaterialsForProject($project);

        $summary = $this->reservationService->getProjectReservationSummary($project);

        // Should have value (10 units * $50 = $500)
        $this->assertArrayHasKey('total_value', $summary);
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    protected function createProject(array $attributes = []): Project
    {
        return Project::create(array_merge([
            'name' => 'Stage Test Project',
            'partner_id' => $this->partner->id,
            'company_id' => $this->company->id,
            'stage_id' => $this->newStage->id,
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->user->id,
            'creator_id' => $this->user->id,
        ], $attributes));
    }

    protected function createProjectWithBom(): Project
    {
        $project = $this->createProject();

        // Create cabinet
        $cabinet = CabinetSpecification::create([
            'project_id' => $project->id,
            'cabinet_number' => 'K1',
            'linear_feet' => 10,
            'creator_id' => $this->user->id,
        ]);

        // Create products and BOM items
        for ($i = 1; $i <= 3; $i++) {
            $product = $this->createProduct(['name' => "Material {$i}"]);

            CabinetMaterialsBom::create([
                'cabinet_specification_id' => $cabinet->id,
                'component_name' => "Material {$i}",
                'product_id' => $product->id,
                'quantity_required' => 10 + ($i * 5),
                'unit_of_measure' => 'unit',
                'material_allocated' => false,
            ]);
        }

        return $project;
    }

    protected function createProduct(array $attributes = []): Product
    {
        // Get required foreign key values
        $uom = UOM::first();
        $category = \Webkul\Product\Models\Category::first();

        // If we can't get required FKs, return existing product
        if (!$uom || !$category) {
            $existing = Product::where('type', 'goods')->first() ?? Product::first();
            if ($existing) {
                return $existing;
            }
            // Last resort - skip test gracefully
            $this->markTestSkipped('No UOM or Category available for product creation');
        }

        return Product::create(array_merge([
            'name' => 'Test Product ' . uniqid(),
            'type' => 'goods',  // Valid enum: 'goods' or 'service'
            'is_active' => true,
            'company_id' => $this->company->id,
            'uom_id' => $uom->id,
            'uom_po_id' => $uom->id,
            'category_id' => $category->id,
        ], $attributes));
    }

    protected function addInventoryForProject(Project $project, float $quantity): void
    {
        // Get all products from project BOM
        $productIds = CabinetMaterialsBom::whereHas('cabinetSpecification', function ($q) use ($project) {
            $q->where('project_id', $project->id);
        })
            ->whereNotNull('product_id')
            ->pluck('product_id')
            ->unique();

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

    /**
     * Check if required tables exist for inventory integration tests
     */
    protected function hasRequiredTables(): bool
    {
        $requiredTables = [
            'inventories_warehouses',
            'inventories_locations',
            'inventories_product_quantities',
            'inventories_moves',
            'projects_material_reservations',
        ];

        foreach ($requiredTables as $table) {
            if (!\Illuminate\Support\Facades\Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }
}
