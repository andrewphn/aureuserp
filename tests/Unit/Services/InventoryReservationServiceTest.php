<?php

namespace Tests\Unit\Services;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Webkul\Inventory\Models\Location;
use Webkul\Inventory\Models\ProductQuantity;
use Webkul\Inventory\Models\Warehouse;
use Webkul\Partner\Models\Partner;
use Webkul\Product\Models\Product;
use Webkul\Project\Models\CabinetMaterialsBom;
use Webkul\Project\Models\CabinetSpecification;
use Webkul\Project\Models\MaterialReservation;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\ProjectStage;
use Webkul\Project\Services\InventoryReservationService;
use Webkul\Support\Models\Company;
use Webkul\Support\Models\UOM;

class InventoryReservationServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected InventoryReservationService $service;
    protected Company $company;
    protected Partner $partner;
    protected ProjectStage $stage;
    protected Warehouse $warehouse;
    protected ?Location $stockLocation = null;
    protected ?Location $outputLocation = null;
    protected ?Location $viewLocation = null;
    protected \App\Models\User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Check if required tables exist
        if (!$this->hasRequiredTables()) {
            $this->markTestSkipped('Required inventory tables not available. Run inventory migrations first.');
        }

        $this->service = new InventoryReservationService();

        // Get or create a test user
        $this->user = \App\Models\User::first() ?? \App\Models\User::factory()->create();

        // Create required test data
        $this->company = Company::firstOrCreate(
            ['name' => 'Test Company'],
            ['is_active' => true]
        );

        $this->partner = Partner::firstOrCreate(
            ['name' => 'Test Customer'],
            ['company_id' => $this->company->id, 'is_customer' => true]
        );

        $this->stage = ProjectStage::firstOrCreate(
            ['name' => 'New', 'company_id' => $this->company->id],
            ['sort' => 1]
        );

        // Use existing warehouse if available (avoids complex foreign key requirements)
        $this->warehouse = Warehouse::first();

        if (!$this->warehouse) {
            // Create warehouse with all required locations
            $this->viewLocation = Location::create([
                'name' => 'View Location',
                'type' => 'view',
                'company_id' => $this->company->id,
            ]);

            $this->stockLocation = Location::create([
                'name' => 'Stock',
                'type' => 'internal',
                'company_id' => $this->company->id,
            ]);

            $this->outputLocation = Location::create([
                'name' => 'Output',
                'type' => 'transit',
                'company_id' => $this->company->id,
            ]);

            $this->warehouse = Warehouse::create([
                'name' => 'Main Warehouse',
                'code' => 'WH',
                'company_id' => $this->company->id,
                'view_location_id' => $this->viewLocation->id,
                'lot_stock_location_id' => $this->stockLocation->id,
                'output_stock_location_id' => $this->outputLocation->id,
                'reception_steps' => 'one_step',
                'delivery_steps' => 'one_step',  // Valid: one_step, two_steps, three_steps
            ]);
        } else {
            // Use existing warehouse's locations - create if not found
            $this->stockLocation = Location::find($this->warehouse->lot_stock_location_id)
                ?? Location::where('type', 'internal')->first()
                ?? Location::create([
                    'name' => 'Stock',
                    'type' => 'internal',
                    'company_id' => $this->company->id,
                ]);

            $this->outputLocation = Location::find($this->warehouse->output_stock_location_id)
                ?? Location::where('type', 'transit')->first()
                ?? Location::create([
                    'name' => 'Output',
                    'type' => 'transit',
                    'company_id' => $this->company->id,
                ]);

            // Update warehouse with stock location if it wasn't set
            if (!$this->warehouse->lot_stock_location_id) {
                $this->warehouse->update(['lot_stock_location_id' => $this->stockLocation->id]);
            }
        }
    }

    /** @test */
    public function it_requires_warehouse_to_reserve_materials(): void
    {
        $project = $this->createProject(['warehouse_id' => null]);

        $result = $this->service->reserveMaterialsForProject($project);

        $this->assertFalse($result['success']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('warehouse', $result['errors'][0]);
    }

    /** @test */
    public function it_returns_success_for_empty_bom(): void
    {
        $project = $this->createProject(['warehouse_id' => $this->warehouse->id]);

        $result = $this->service->reserveMaterialsForProject($project);

        $this->assertTrue($result['success']);
        $this->assertEmpty($result['reservations']);
        $this->assertArrayHasKey('message', $result);
    }

    /** @test */
    public function it_checks_availability_correctly(): void
    {
        $product = $this->createProduct();

        // Add inventory
        ProductQuantity::create([
            'product_id' => $product->id,
            'location_id' => $this->stockLocation->id,
            'quantity' => 100,
            'reserved_quantity' => 20,
        ]);

        // Available = 100 - 20 = 80
        $this->assertTrue($this->service->checkAvailability($product->id, $this->warehouse->id, 80));
        $this->assertTrue($this->service->checkAvailability($product->id, $this->warehouse->id, 50));
        $this->assertFalse($this->service->checkAvailability($product->id, $this->warehouse->id, 81));
        $this->assertFalse($this->service->checkAvailability($product->id, $this->warehouse->id, 100));
    }

    /** @test */
    public function it_returns_false_for_nonexistent_product(): void
    {
        $this->assertFalse($this->service->checkAvailability(99999, $this->warehouse->id, 10));
    }

    /** @test */
    public function it_gets_available_quantity(): void
    {
        $product = $this->createProduct();

        ProductQuantity::create([
            'product_id' => $product->id,
            'location_id' => $this->stockLocation->id,
            'quantity' => 150,
            'reserved_quantity' => 30,
        ]);

        $available = $this->service->getAvailableQuantity($product->id, $this->warehouse->id);

        $this->assertEquals(120, $available);
    }

    /** @test */
    public function it_returns_zero_for_product_not_in_warehouse(): void
    {
        $product = $this->createProduct();

        $available = $this->service->getAvailableQuantity($product->id, $this->warehouse->id);

        $this->assertEquals(0, $available);
    }

    /** @test */
    public function it_reserves_bom_item_successfully(): void
    {
        $project = $this->createProject(['warehouse_id' => $this->warehouse->id]);
        $product = $this->createProduct();

        // Add inventory
        ProductQuantity::create([
            'product_id' => $product->id,
            'location_id' => $this->stockLocation->id,
            'quantity' => 100,
            'reserved_quantity' => 0,
        ]);

        $cabinet = CabinetSpecification::create([
            'project_id' => $project->id,
            'cabinet_number' => 'K1',
            'linear_feet' => 10,
            'creator_id' => $this->user->id,
        ]);

        $bom = CabinetMaterialsBom::create([
            'cabinet_specification_id' => $cabinet->id,
            'component_name' => 'Plywood',
            'product_id' => $product->id,
            'quantity_required' => 20,
            'unit_of_measure' => 'sheet',
            'material_allocated' => false,
        ]);

        $result = $this->service->reserveBomItem($bom, $this->warehouse);

        $this->assertTrue($result['success']);
        $this->assertInstanceOf(MaterialReservation::class, $result['reservation']);
        $this->assertEquals(MaterialReservation::STATUS_RESERVED, $result['reservation']->status);
        $this->assertEquals(20, $result['reservation']->quantity_reserved);

        // BOM should be marked as allocated
        $bom->refresh();
        $this->assertTrue($bom->material_allocated);
    }

    /** @test */
    public function it_fails_reservation_for_insufficient_inventory(): void
    {
        $project = $this->createProject(['warehouse_id' => $this->warehouse->id]);
        $product = $this->createProduct();

        // Add insufficient inventory
        ProductQuantity::create([
            'product_id' => $product->id,
            'location_id' => $this->stockLocation->id,
            'quantity' => 10,
            'reserved_quantity' => 0,
        ]);

        $cabinet = CabinetSpecification::create([
            'project_id' => $project->id,
            'cabinet_number' => 'K1',
            'linear_feet' => 10,
            'creator_id' => $this->user->id,
        ]);

        $bom = CabinetMaterialsBom::create([
            'cabinet_specification_id' => $cabinet->id,
            'component_name' => 'Plywood',
            'product_id' => $product->id,
            'quantity_required' => 50, // More than available
            'material_allocated' => false,
        ]);

        $result = $this->service->reserveBomItem($bom, $this->warehouse);

        $this->assertFalse($result['success']);
        $this->assertNull($result['reservation']);
        $this->assertStringContainsString('Insufficient', $result['error']);
    }

    /** @test */
    public function it_reserves_all_materials_for_project(): void
    {
        $project = $this->createProject(['warehouse_id' => $this->warehouse->id]);
        $product1 = $this->createProduct(['name' => 'Material 1']);
        $product2 = $this->createProduct(['name' => 'Material 2']);

        // Add inventory for both products
        ProductQuantity::create([
            'product_id' => $product1->id,
            'location_id' => $this->stockLocation->id,
            'quantity' => 100,
            'reserved_quantity' => 0,
        ]);

        ProductQuantity::create([
            'product_id' => $product2->id,
            'location_id' => $this->stockLocation->id,
            'quantity' => 100,
            'reserved_quantity' => 0,
        ]);

        $cabinet = CabinetSpecification::create([
            'project_id' => $project->id,
            'cabinet_number' => 'K1',
            'linear_feet' => 10,
            'creator_id' => $this->user->id,
        ]);

        CabinetMaterialsBom::create([
            'cabinet_specification_id' => $cabinet->id,
            'component_name' => 'Material 1',
            'product_id' => $product1->id,
            'quantity_required' => 20,
            'material_allocated' => false,
        ]);

        CabinetMaterialsBom::create([
            'cabinet_specification_id' => $cabinet->id,
            'component_name' => 'Material 2',
            'product_id' => $product2->id,
            'quantity_required' => 30,
            'material_allocated' => false,
        ]);

        $result = $this->service->reserveMaterialsForProject($project);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['reservations']->count());
    }

    /** @test */
    public function it_releases_reservation(): void
    {
        $project = $this->createProject(['warehouse_id' => $this->warehouse->id]);
        $product = $this->createProduct();

        ProductQuantity::create([
            'product_id' => $product->id,
            'location_id' => $this->stockLocation->id,
            'quantity' => 100,
            'reserved_quantity' => 20,
        ]);

        $cabinet = CabinetSpecification::create([
            'project_id' => $project->id,
            'cabinet_number' => 'K1',
            'linear_feet' => 10,
            'creator_id' => $this->user->id,
        ]);

        $bom = CabinetMaterialsBom::create([
            'cabinet_specification_id' => $cabinet->id,
            'component_name' => 'Material',
            'product_id' => $product->id,
            'quantity_required' => 20,
            'material_allocated' => true,
        ]);

        $reservation = MaterialReservation::create([
            'project_id' => $project->id,
            'bom_id' => $bom->id,
            'product_id' => $product->id,
            'warehouse_id' => $this->warehouse->id,
            'location_id' => $this->stockLocation->id,
            'quantity_reserved' => 20,
            'status' => MaterialReservation::STATUS_RESERVED,
            'reserved_at' => now(),
        ]);

        $result = $this->service->releaseReservation($reservation, 'Test cancellation');

        $this->assertTrue($result);

        $reservation->refresh();
        $this->assertEquals(MaterialReservation::STATUS_CANCELLED, $reservation->status);

        $bom->refresh();
        $this->assertFalse($bom->material_allocated);
    }

    /** @test */
    public function it_gets_project_reservation_summary(): void
    {
        $project = $this->createProject(['warehouse_id' => $this->warehouse->id]);
        $product = $this->createProduct();

        // Create different status reservations
        MaterialReservation::create([
            'project_id' => $project->id,
            'product_id' => $product->id,
            'warehouse_id' => $this->warehouse->id,
            'location_id' => $this->stockLocation->id,
            'quantity_reserved' => 10,
            'status' => MaterialReservation::STATUS_RESERVED,
            'reserved_at' => now(),
        ]);

        MaterialReservation::create([
            'project_id' => $project->id,
            'product_id' => $product->id,
            'warehouse_id' => $this->warehouse->id,
            'location_id' => $this->stockLocation->id,
            'quantity_reserved' => 15,
            'status' => MaterialReservation::STATUS_ISSUED,
            'reserved_at' => now(),
        ]);

        MaterialReservation::create([
            'project_id' => $project->id,
            'product_id' => $product->id,
            'warehouse_id' => $this->warehouse->id,
            'location_id' => $this->stockLocation->id,
            'quantity_reserved' => 5,
            'status' => MaterialReservation::STATUS_CANCELLED,
            'reserved_at' => now(),
        ]);

        $summary = $this->service->getProjectReservationSummary($project);

        $this->assertEquals(3, $summary['total']);
        $this->assertEquals(1, $summary['reserved']);
        $this->assertEquals(1, $summary['issued']);
        $this->assertEquals(1, $summary['cancelled']);
    }

    /** @test */
    public function it_releases_all_reservations_for_project(): void
    {
        $project = $this->createProject(['warehouse_id' => $this->warehouse->id]);
        $product = $this->createProduct();

        ProductQuantity::create([
            'product_id' => $product->id,
            'location_id' => $this->stockLocation->id,
            'quantity' => 100,
            'reserved_quantity' => 30,
        ]);

        // Create multiple reservations
        for ($i = 0; $i < 3; $i++) {
            MaterialReservation::create([
                'project_id' => $project->id,
                'product_id' => $product->id,
                'warehouse_id' => $this->warehouse->id,
                'location_id' => $this->stockLocation->id,
                'quantity_reserved' => 10,
                'status' => MaterialReservation::STATUS_RESERVED,
                'reserved_at' => now(),
            ]);
        }

        $released = $this->service->releaseAllReservationsForProject($project, 'Project cancelled');

        $this->assertEquals(3, $released);

        // All should be cancelled
        $activeCount = MaterialReservation::forProject($project->id)
            ->whereIn('status', [MaterialReservation::STATUS_RESERVED, MaterialReservation::STATUS_PENDING])
            ->count();

        $this->assertEquals(0, $activeCount);
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    protected function createProject(array $attributes = []): Project
    {
        return Project::create(array_merge([
            'name' => 'Test Project',
            'partner_id' => $this->partner->id,
            'company_id' => $this->company->id,
            'stage_id' => $this->stage->id,
            'user_id' => $this->user->id,
            'creator_id' => $this->user->id,
        ], $attributes));
    }

    protected function createProduct(array $attributes = []): Product
    {
        // Try to use existing product first (avoids complex FK requirements)
        $existingProduct = Product::where('type', 'goods')->first();
        if ($existingProduct && !isset($attributes['name'])) {
            // Return existing if we're not specifically naming a new one
            return $existingProduct;
        }

        // Get required foreign key values
        $uom = UOM::first();
        $category = \Webkul\Product\Models\Category::first();

        if (!$uom || !$category) {
            // Fall back to existing product
            return Product::first() ?? Product::create([
                'name' => 'Test Product ' . uniqid(),
                'type' => 'goods',
                'company_id' => $this->company->id,
            ]);
        }

        return Product::create(array_merge([
            'name' => 'Test Product ' . uniqid(),
            'type' => 'goods',
            'is_active' => true,
            'company_id' => $this->company->id,
            'uom_id' => $uom->id,
            'uom_po_id' => $uom->id,
            'category_id' => $category->id,
        ], $attributes));
    }

    /**
     * Check if required database tables exist for these tests
     */
    protected function hasRequiredTables(): bool
    {
        $requiredTables = [
            'inventories_warehouses',
            'inventories_locations',
            'inventories_product_quantities',
            'projects_material_reservations',
            'projects_cabinet_specifications',
            'projects_cabinet_materials_bom',
        ];

        foreach ($requiredTables as $table) {
            if (!\Illuminate\Support\Facades\Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }
}
