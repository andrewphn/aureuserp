<?php

namespace Tests\Unit\Services;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
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
use Webkul\Support\Models\UOM;

class ProjectToOrderServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected ProjectToOrderService $service;
    protected Partner $partner;
    protected Company $company;
    protected Currency $currency;
    protected ProjectStage $stage;
    protected \App\Models\User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Check if required tables exist
        if (!$this->hasRequiredTables()) {
            $this->markTestSkipped('Required project/sales tables not available. Run all migrations first.');
        }

        $this->service = new ProjectToOrderService();

        // Get or create a test user
        $this->user = \App\Models\User::first() ?? \App\Models\User::factory()->create();

        // Create required test data
        $this->company = Company::firstOrCreate(
            ['name' => 'Test Company'],
            ['is_active' => true]
        );

        $this->currency = Currency::firstOrCreate(
            ['code' => 'USD'],
            ['name' => 'US Dollar', 'symbol' => '$', 'is_default' => true]
        );

        $this->partner = Partner::firstOrCreate(
            ['name' => 'Test Customer'],
            ['company_id' => $this->company->id, 'is_customer' => true]
        );

        $this->stage = ProjectStage::firstOrCreate(
            ['name' => 'New', 'company_id' => $this->company->id],
            ['sort' => 1]
        );
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
        ];

        foreach ($requiredTables as $table) {
            if (!\Illuminate\Support\Facades\Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    /** @test */
    public function it_creates_order_for_project(): void
    {
        $project = $this->createProject();

        $order = $this->service->createOrderForProject($project);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals($project->id, $order->project_id);
        $this->assertEquals($project->partner_id, $order->partner_id);
        $this->assertEquals($project->company_id, $order->company_id);
        $this->assertEquals(OrderState::DRAFT, $order->state);
    }

    /** @test */
    public function it_creates_order_with_custom_data(): void
    {
        $project = $this->createProject();
        $customData = [
            'name' => 'Custom Order Name',
        ];

        $order = $this->service->createOrderForProject($project, $customData);

        $this->assertEquals('Custom Order Name', $order->name);
        $this->assertEquals($project->id, $order->project_id);
    }

    /** @test */
    public function it_generates_order_lines_from_project_with_cabinets(): void
    {
        $project = $this->createProjectWithCabinets();
        $order = $this->createOrder($project);

        $result = $this->service->generateOrderLinesFromProject($project, $order, [
            'include_cabinets' => true,
            'include_rooms' => false,
            'group_by_room' => true,
        ]);

        $this->assertTrue($result['success'], 'Service failed: ' . json_encode($result['errors'] ?? []));
        $this->assertNotEmpty($result['lines']);
        $this->assertEmpty($result['errors']);

        // Should have section header + cabinet lines
        $sectionLines = $result['lines']->where('display_type', 'line_section');
        $this->assertGreaterThanOrEqual(1, $sectionLines->count());
    }

    /** @test */
    public function it_generates_order_lines_grouped_by_room(): void
    {
        $project = $this->createProjectWithCabinets();
        $order = $this->createOrder($project);

        $result = $this->service->generateOrderLinesFromProject($project, $order, [
            'include_cabinets' => true,
            'group_by_room' => true,
        ]);

        $this->assertTrue($result['success']);

        // Check for room section headers
        $sectionLines = $result['lines']->where('display_type', 'line_section');
        $this->assertGreaterThanOrEqual(1, $sectionLines->count());
    }

    /** @test */
    public function it_generates_order_lines_without_room_grouping(): void
    {
        $project = $this->createProjectWithCabinets();
        $order = $this->createOrder($project);

        $result = $this->service->generateOrderLinesFromProject($project, $order, [
            'include_cabinets' => true,
            'group_by_room' => false,
        ]);

        $this->assertTrue($result['success']);
    }

    /** @test */
    public function it_includes_room_charges_when_enabled(): void
    {
        $project = $this->createProject();
        $room = Room::create([
            'project_id' => $project->id,
            'name' => 'Kitchen',
            'quoted_price' => 5000.00,
        ]);

        $order = $this->createOrder($project);

        $result = $this->service->generateOrderLinesFromProject($project, $order, [
            'include_rooms' => true,
            'include_cabinets' => false,
        ]);

        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['lines']);

        // Should have room charge line
        $roomChargeLine = $result['lines']->first(fn($line) =>
            str_contains($line->name ?? '', 'Room Charges')
        );
        $this->assertNotNull($roomChargeLine);
        $this->assertEquals(5000.00, $roomChargeLine->price_unit);
    }

    /** @test */
    public function it_handles_standalone_cabinets(): void
    {
        $project = $this->createProject();

        // Create cabinet not attached to any room
        CabinetSpecification::create([
            'project_id' => $project->id,
            'room_id' => null,
            'cabinet_run_id' => null,
            'cabinet_number' => 'S1',
            'linear_feet' => 10.5,
            'unit_price_per_lf' => 150.00,
            'total_price' => 1575.00,
            'creator_id' => 1,
        ]);

        $order = $this->createOrder($project);

        $result = $this->service->generateOrderLinesFromProject($project, $order, [
            'include_cabinets' => true,
            'group_by_room' => true,
        ]);

        $this->assertTrue($result['success']);

        // Should have "Unassigned Cabinets" section
        $unassignedSection = $result['lines']->first(fn($line) =>
            ($line->display_type ?? null) === 'line_section' &&
            str_contains($line->name ?? '', 'Unassigned')
        );
        $this->assertNotNull($unassignedSection);
    }

    /** @test */
    public function it_imports_from_project_into_existing_order(): void
    {
        $project = $this->createProjectWithCabinets();
        $order = $this->createOrder($project);

        $result = $this->service->importFromProject($order, $project, [
            'include_cabinets' => true,
        ]);

        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['lines']);
    }

    /** @test */
    public function it_clears_existing_lines_when_requested(): void
    {
        $project = $this->createProjectWithCabinets();
        $order = $this->createOrder($project);

        // Create some existing lines
        OrderLine::create([
            'order_id' => $order->id,
            'company_id' => $order->company_id,
            'currency_id' => $order->currency_id,
            'name' => 'Existing Line',
            'product_uom_qty' => 1,
            'price_unit' => 100,
            'price_subtotal' => 100,
            'price_total' => 100,
            'sort' => 1,
        ]);

        $this->assertEquals(1, $order->lines()->count());

        $result = $this->service->importFromProject($order, $project, [
            'include_cabinets' => true,
            'clear_existing' => true,
        ]);

        $this->assertTrue($result['success']);

        // Old lines should be deleted
        $order->refresh();
        $this->assertFalse($order->lines()->where('name', 'Existing Line')->exists());
    }

    /** @test */
    public function it_calculates_cabinet_line_pricing_correctly(): void
    {
        $project = $this->createProject();
        $room = Room::create([
            'project_id' => $project->id,
            'name' => 'Kitchen',
        ]);

        CabinetSpecification::create([
            'project_id' => $project->id,
            'room_id' => $room->id,
            'cabinet_number' => 'K1',
            'linear_feet' => 12.5,
            'unit_price_per_lf' => 200.00,
            'quantity' => 1,
            'total_price' => 2500.00,
            'width_inches' => 36,
            'depth_inches' => 24,
            'height_inches' => 34,
            'creator_id' => $this->user->id,
        ]);

        $order = $this->createOrder($project);

        $result = $this->service->generateOrderLinesFromProject($project, $order, [
            'include_cabinets' => true,
            'group_by_room' => true,
        ]);

        $this->assertTrue($result['success']);

        // Find the cabinet line (not section header)
        $cabinetLine = $result['lines']->first(fn($line) =>
            !isset($line->display_type) &&
            str_contains($line->name ?? '', 'Cabinet')
        );

        $this->assertNotNull($cabinetLine);
        $this->assertEquals(200.00, $cabinetLine->price_unit);
        $this->assertEquals(12.5, $cabinetLine->product_uom_qty);
    }

    /** @test */
    public function it_handles_empty_project(): void
    {
        $project = $this->createProject();
        $order = $this->createOrder($project);

        $result = $this->service->generateOrderLinesFromProject($project, $order, [
            'include_cabinets' => true,
            'include_rooms' => true,
        ]);

        $this->assertTrue($result['success']);
        $this->assertEmpty($result['errors']);
    }

    /** @test */
    public function it_updates_order_totals(): void
    {
        $project = $this->createProjectWithCabinets();
        $order = $this->createOrder($project);

        $this->service->generateOrderLinesFromProject($project, $order, [
            'include_cabinets' => true,
        ]);

        $order->refresh();

        $this->assertGreaterThan(0, $order->amount_untaxed);
        $this->assertGreaterThan(0, $order->amount_total);
    }

    /** @test */
    public function it_builds_cabinet_description_with_all_fields(): void
    {
        $project = $this->createProject();
        $room = Room::create([
            'project_id' => $project->id,
            'name' => 'Kitchen',
        ]);

        CabinetSpecification::create([
            'project_id' => $project->id,
            'room_id' => $room->id,
            'cabinet_number' => 'K-001',
            'linear_feet' => 8.5,
            'unit_price_per_lf' => 175.00,
            'width_inches' => 36,
            'depth_inches' => 24,
            'height_inches' => 34,
            'box_material' => 'Maple',
            'creator_id' => $this->user->id,
        ]);

        $order = $this->createOrder($project);

        $result = $this->service->generateOrderLinesFromProject($project, $order);

        $cabinetLine = $result['lines']->first(fn($line) =>
            !isset($line->display_type) &&
            str_contains($line->name ?? '', 'K-001')
        );

        $this->assertNotNull($cabinetLine);
        // Note: Dimensions are formatted with 2 decimal places
        $this->assertStringContainsString('36.00"W', $cabinetLine->name);
        $this->assertStringContainsString('24.00"D', $cabinetLine->name);
        $this->assertStringContainsString('34.00"H', $cabinetLine->name);
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

    protected function createProjectWithCabinets(): Project
    {
        $project = $this->createProject();

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

        // Create a few cabinets
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
                'creator_id' => $this->user->id,
            ]);
        }

        return $project;
    }

    protected function createOrder(Project $project, array $attributes = []): Order
    {
        return Order::create(array_merge([
            'name' => 'Test Order',
            'project_id' => $project->id,
            'partner_id' => $project->partner_id,
            'partner_invoice_id' => $project->partner_id,
            'partner_shipping_id' => $project->partner_id,
            'company_id' => $project->company_id,
            'currency_id' => $this->currency->id,
            'state' => OrderState::DRAFT,
            'date_order' => now(),
            'user_id' => $this->user->id,
            'creator_id' => $this->user->id,
        ], $attributes));
    }
}
