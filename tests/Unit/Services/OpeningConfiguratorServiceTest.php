<?php

namespace Tests\Unit\Services;

use App\Services\OpeningConfiguratorService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Webkul\Project\Models\CabinetSection;
use Webkul\Project\Models\Drawer;
use Webkul\Project\Models\Shelf;
use Webkul\Project\Models\Door;
use Webkul\Project\Models\Cabinet;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\ProjectStage;
use Webkul\Partner\Models\Partner;
use Webkul\Support\Models\Company;

/**
 * Unit tests for OpeningConfiguratorService
 *
 * Test data based on real project: 9 Austin Lane Bathroom Vanity
 * - Opening: 37-13/16" W × ~26" H (opening within 32-3/4" cabinet)
 * - 2 Drawers: Upper 7" front, Lower 17-1/8" front
 *
 * @see sample/9 Austin Lane/project_10_full_data_map.json
 */
class OpeningConfiguratorServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected OpeningConfiguratorService $service;
    protected ?Company $company = null;
    protected ?Partner $partner = null;
    protected ?ProjectStage $stage = null;
    protected ?\App\Models\User $user = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OpeningConfiguratorService();
    }

    /**
     * Set up database dependencies for tests that need them
     */
    protected function setUpDatabase(): void
    {
        if (!$this->hasRequiredTables()) {
            $this->markTestSkipped('Required tables not available. Run migrations first.');
        }

        // Get or create test data
        $this->user = \App\Models\User::first() ?? \App\Models\User::factory()->create();

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
    }

    // =========================================================================
    // FRACTION CONVERSION TESTS
    // =========================================================================

    /** @test */
    public function it_converts_decimal_to_fraction_for_whole_numbers(): void
    {
        $this->assertEquals('7', $this->service->toFraction(7.0));
        $this->assertEquals('18', $this->service->toFraction(18.0));
        $this->assertEquals('0', $this->service->toFraction(0.0));
    }

    /** @test */
    public function it_converts_decimal_to_common_fractions(): void
    {
        // 1/2
        $this->assertEquals('7-1/2', $this->service->toFraction(7.5));

        // 1/4, 3/4
        $this->assertEquals('6-1/4', $this->service->toFraction(6.25));
        $this->assertEquals('6-3/4', $this->service->toFraction(6.75));

        // 1/8, 3/8, 5/8, 7/8
        $this->assertEquals('7-1/8', $this->service->toFraction(7.125));
        $this->assertEquals('7-3/8', $this->service->toFraction(7.375));
        $this->assertEquals('7-5/8', $this->service->toFraction(7.625));
        $this->assertEquals('7-7/8', $this->service->toFraction(7.875));

        // 1/16 - Real project dimension: 37-13/16"
        $this->assertEquals('37-13/16', $this->service->toFraction(37.8125));
    }

    /** @test */
    public function it_converts_real_project_dimensions(): void
    {
        // From 9 Austin Lane bathroom vanity
        // Upper drawer front: 7"
        $this->assertEquals('7', $this->service->toFraction(7.0));

        // Lower drawer front: 17-1/8"
        $this->assertEquals('17-1/8', $this->service->toFraction(17.125));

        // Opening width: 37-13/16"
        $this->assertEquals('37-13/16', $this->service->toFraction(37.8125));

        // Box width: 37-3/16"
        $this->assertEquals('37-3/16', $this->service->toFraction(37.1875));

        // Cabinet height: 32-3/4"
        $this->assertEquals('32-3/4', $this->service->toFraction(32.75));
    }

    // =========================================================================
    // CONSTANT VERIFICATION TESTS
    // =========================================================================

    /** @test */
    public function it_has_correct_default_gap_constants(): void
    {
        // 1/8" gaps are standard shop practice
        $this->assertEquals(0.125, OpeningConfiguratorService::GAP_TOP_REVEAL_INCHES);
        $this->assertEquals(0.125, OpeningConfiguratorService::GAP_BOTTOM_REVEAL_INCHES);
        $this->assertEquals(0.125, OpeningConfiguratorService::GAP_BETWEEN_COMPONENTS_INCHES);

        // 1/16" side reveal for doors
        $this->assertEquals(0.0625, OpeningConfiguratorService::GAP_DOOR_SIDE_REVEAL_INCHES);
    }

    /** @test */
    public function it_has_correct_minimum_height_constants(): void
    {
        // Minimum shelf opening (from carpenter specs)
        $this->assertEquals(5.5, OpeningConfiguratorService::MIN_SHELF_OPENING_HEIGHT_INCHES);

        // Minimum drawer front
        $this->assertEquals(4.0, OpeningConfiguratorService::MIN_DRAWER_FRONT_HEIGHT_INCHES);
    }

    // =========================================================================
    // LAYOUT CALCULATION TESTS (Database Required)
    // =========================================================================

    /** @test */
    public function it_calculates_vertical_layout_for_two_drawers(): void
    {
        $this->setUpDatabase();

        // Create test cabinet section based on 9 Austin Lane bathroom vanity
        $section = $this->createCabinetSection([
            'opening_width_inches' => 37.8125,  // 37-13/16"
            'opening_height_inches' => 26.0,     // Approximate usable opening
            'layout_direction' => 'vertical',
        ]);

        // Create two drawers like the bathroom vanity
        $drawer1 = $this->createDrawer($section, [
            'drawer_number' => 1,
            'front_height_inches' => 17.125,  // 17-1/8" lower drawer
            'sort_order' => 1,
        ]);

        $drawer2 = $this->createDrawer($section, [
            'drawer_number' => 2,
            'front_height_inches' => 7.0,  // 7" upper drawer
            'sort_order' => 2,
        ]);

        // Calculate layout
        $result = $this->service->calculateSectionLayout($section);

        // Verify layout was calculated
        $this->assertEquals('vertical', $result['layout']);
        $this->assertCount(2, $result['positions']);

        // Refresh models
        $drawer1->refresh();
        $drawer2->refresh();

        // First drawer (lower) should be at bottom reveal position
        $this->assertEquals(0.125, $drawer1->position_in_opening_inches);

        // Second drawer should be above first with gap
        $expectedPosition2 = 0.125 + 17.125 + 0.125; // bottom_reveal + drawer1_height + gap
        $this->assertEquals($expectedPosition2, $drawer2->position_in_opening_inches);
    }

    /** @test */
    public function it_calculates_consumed_height_correctly(): void
    {
        $this->setUpDatabase();

        $section = $this->createCabinetSection([
            'opening_width_inches' => 37.8125,
            'opening_height_inches' => 26.0,
            'layout_direction' => 'vertical',
        ]);

        $drawer1 = $this->createDrawer($section, [
            'front_height_inches' => 17.125,
            'sort_order' => 1,
        ]);

        $drawer2 = $this->createDrawer($section, [
            'front_height_inches' => 7.0,
            'sort_order' => 2,
        ]);

        $result = $this->service->calculateSectionLayout($section);

        $drawer1->refresh();
        $drawer2->refresh();

        // First drawer consumed height = height + gap (not last)
        $this->assertEquals(17.125 + 0.125, $drawer1->consumed_height_inches);

        // Last drawer consumed height = height only (no gap after)
        $this->assertEquals(7.0, $drawer2->consumed_height_inches);
    }

    /** @test */
    public function it_updates_section_space_tracking(): void
    {
        $this->setUpDatabase();

        $section = $this->createCabinetSection([
            'opening_width_inches' => 37.8125,
            'opening_height_inches' => 26.0,
            'layout_direction' => 'vertical',
            'top_reveal_inches' => 0.125,
            'bottom_reveal_inches' => 0.125,
            'component_gap_inches' => 0.125,
        ]);

        $drawer1 = $this->createDrawer($section, [
            'front_height_inches' => 17.125,
            'sort_order' => 1,
        ]);

        $drawer2 = $this->createDrawer($section, [
            'front_height_inches' => 7.0,
            'sort_order' => 2,
        ]);

        $this->service->calculateSectionLayout($section);
        $section->refresh();

        // Total consumed = bottom_reveal + drawer1 + gap + drawer2 + top_reveal
        // = 0.125 + 17.125 + 0.125 + 7.0 + 0.125 = 24.5
        // The calculation includes reveals in the total consumed height
        $expectedConsumed = 0.125 + 17.125 + 0.125 + 7.0 + 0.125;
        $this->assertEquals($expectedConsumed, $section->total_consumed_height_inches);

        // Remaining = opening - consumed
        $expectedRemaining = 26.0 - $expectedConsumed;
        $this->assertEquals($expectedRemaining, $section->remaining_height_inches);
    }

    // =========================================================================
    // REMAINING SPACE TESTS (Database Required)
    // =========================================================================

    /** @test */
    public function it_gets_remaining_space_correctly(): void
    {
        $this->setUpDatabase();

        $section = $this->createCabinetSection([
            'opening_width_inches' => 37.8125,
            'opening_height_inches' => 26.0,
            'layout_direction' => 'vertical',
        ]);

        // With no components, remaining space accounts for reveals (0.125 each)
        $space = $this->service->getRemainingSpace($section);
        $this->assertEquals(26.0 - 0.25, $space['remaining_height']); // 26 - (top + bottom reveal)
        $this->assertEquals(37.8125, $space['remaining_width']);

        // Add a drawer
        $this->createDrawer($section, [
            'front_height_inches' => 10.0,
            'sort_order' => 1,
        ]);

        $space = $this->service->getRemainingSpace($section);
        // remaining = 26.0 - 10.0 - 0.25 (reveals) = 15.75
        $this->assertEquals(15.75, $space['remaining_height']);
    }

    // =========================================================================
    // CAN FIT COMPONENT TESTS (Database Required)
    // =========================================================================

    /** @test */
    public function it_checks_if_drawer_can_fit(): void
    {
        $this->setUpDatabase();

        $section = $this->createCabinetSection([
            'opening_width_inches' => 37.8125,
            'opening_height_inches' => 26.0,
            'layout_direction' => 'vertical',
        ]);

        // Empty section - should fit a 10" drawer
        $this->assertTrue($this->service->canFitComponent($section, 'drawer', 10.0));

        // Add existing drawers (17.125 + 7 = 24.125")
        $this->createDrawer($section, ['front_height_inches' => 17.125, 'sort_order' => 1]);
        $this->createDrawer($section, ['front_height_inches' => 7.0, 'sort_order' => 2]);

        // Remaining ~1.875" - can't fit another 4" drawer (minimum)
        $this->assertFalse($this->service->canFitComponent($section, 'drawer', 4.0));

        // But might fit a very small component
        // After 2 components: consumed = 17.125 + 0.125 + 7.0 = 24.25
        // Remaining = 26.0 - 24.25 = 1.75
        // New component needs height + gap (0.125) = minimum 4.125 for drawer
        $this->assertFalse($this->service->canFitComponent($section, 'drawer', 4.0));
    }

    /** @test */
    public function it_accounts_for_gap_when_checking_fit(): void
    {
        $this->setUpDatabase();

        $section = $this->createCabinetSection([
            'opening_height_inches' => 10.0,
            'layout_direction' => 'vertical',
        ]);

        // Add one drawer of 5"
        $this->createDrawer($section, ['front_height_inches' => 5.0, 'sort_order' => 1]);

        // Remaining = 10 - 5 = 5"
        // But adding another component needs: height + gap (0.125)
        // So a 4.875" drawer would need 4.875 + 0.125 = 5.0" - should just fit
        // Actually canFitComponent for drawer uses MIN_DRAWER_FRONT_HEIGHT_INCHES (4.0)
        // And adds gap (0.125) for existing components
        // Let's check if a 4" drawer fits (needs 4 + 0.125 = 4.125")
        // Consumed so far = 5.0, remaining = 5.0
        $this->assertTrue($this->service->canFitComponent($section, 'drawer', 4.0));
    }

    // =========================================================================
    // COMPONENT HEIGHT CALCULATION TESTS (Database Required)
    // =========================================================================

    /** @test */
    public function it_gets_consumed_height_for_drawer(): void
    {
        $this->setUpDatabase();

        $section = $this->createCabinetSection();
        $drawer = $this->createDrawer($section, ['front_height_inches' => 7.0]);

        $height = $this->service->getComponentConsumedHeight($drawer, 'drawer');
        $this->assertEquals(7.0, $height);
    }

    /** @test */
    public function it_gets_consumed_height_for_shelf(): void
    {
        $this->setUpDatabase();

        $section = $this->createCabinetSection();
        $shelf = $this->createShelf($section, ['thickness_inches' => 0.75]);

        // Shelves consume minimum opening height (5.5") regardless of thickness
        $height = $this->service->getComponentConsumedHeight($shelf, 'shelf');
        $this->assertEquals(5.5, $height);
    }

    /** @test */
    public function it_gets_consumed_height_for_door(): void
    {
        $this->setUpDatabase();

        $section = $this->createCabinetSection();

        // Create a door model for testing
        $door = \Webkul\Project\Models\Door::create([
            'section_id' => $section->id,
            'cabinet_id' => $section->cabinet_specification_id,
            'door_number' => 1,
            'height_inches' => 24.0,
            'width_inches' => 15.0,
            'sort_order' => 1,
        ]);

        $height = $this->service->getComponentConsumedHeight($door, 'door');
        $this->assertEquals(24.0, $height);
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    protected function createProject(array $attributes = []): Project
    {
        return Project::create(array_merge([
            'name' => 'Test Project - 9 Austin Lane',
            'partner_id' => $this->partner->id,
            'company_id' => $this->company->id,
            'stage_id' => $this->stage->id,
            'user_id' => $this->user->id,
            'creator_id' => $this->user->id,
        ], $attributes));
    }

    protected function createCabinet(Project $project, array $attributes = []): Cabinet
    {
        return Cabinet::create(array_merge([
            'project_id' => $project->id,
            'cabinet_number' => 'BTH1-B1-C1',
            'linear_feet' => 3.44,
            'creator_id' => $this->user->id,
        ], $attributes));
    }

    protected function createCabinetSection(array $attributes = []): CabinetSection
    {
        $project = $this->createProject();
        $cabinet = $this->createCabinet($project);

        return CabinetSection::create(array_merge([
            'cabinet_specification_id' => $cabinet->id,
            'section_number' => 1,
            'name' => 'Main Drawer Section',
            'section_type' => 'drawer_bank',
            'width_inches' => 37.8125,
            'height_inches' => 32.75,
            'opening_width_inches' => 37.8125,
            'opening_height_inches' => 26.0,
            'layout_direction' => 'vertical',
            'top_reveal_inches' => 0.125,
            'bottom_reveal_inches' => 0.125,
            'component_gap_inches' => 0.125,
        ], $attributes));
    }

    protected function createDrawer(CabinetSection $section, array $attributes = []): Drawer
    {
        return Drawer::create(array_merge([
            'section_id' => $section->id,
            'cabinet_id' => $section->cabinet_specification_id,
            'drawer_number' => 1,
            'drawer_name' => 'Test Drawer',
            'front_width_inches' => 37.8125,
            'front_height_inches' => 7.0,
            'box_width_inches' => 37.1875,
            'box_depth_inches' => 18.0,
            'box_height_inches' => 9.0,
            'slide_length_inches' => 18,
            'sort_order' => 1,
        ], $attributes));
    }

    protected function createShelf(CabinetSection $section, array $attributes = []): Shelf
    {
        return Shelf::create(array_merge([
            'section_id' => $section->id,
            'cabinet_id' => $section->cabinet_specification_id,
            'shelf_number' => 1,
            'width_inches' => 37.8125,
            'depth_inches' => 18.0,
            'thickness_inches' => 0.75,
            'sort_order' => 1,
        ], $attributes));
    }

    protected function hasRequiredTables(): bool
    {
        $requiredTables = [
            'projects_cabinet_sections',
            'projects_drawers',
            'projects_shelves',
            'projects_doors',
        ];

        foreach ($requiredTables as $table) {
            if (!\Illuminate\Support\Facades\Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }
}
