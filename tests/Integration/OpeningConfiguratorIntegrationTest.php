<?php

namespace Tests\Integration;

use App\Services\OpeningConfiguratorService;
use App\Services\OpeningLayoutEngine;
use App\Services\OpeningValidator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Webkul\Project\Models\CabinetSection;
use Webkul\Project\Models\Drawer;
use Webkul\Project\Models\Shelf;
use Webkul\Project\Models\Cabinet;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\ProjectStage;
use Webkul\Partner\Models\Partner;
use Webkul\Support\Models\Company;

/**
 * Integration tests for Opening Configurator System
 *
 * Tests the complete workflow of:
 * 1. Creating a cabinet section (opening)
 * 2. Adding components (drawers, shelves)
 * 3. Calculating layout
 * 4. Validating configuration
 * 5. Applying auto-arrangement strategies
 *
 * Uses real project data from 9 Austin Lane Bathroom Vanity
 * @see sample/9 Austin Lane/project_10_full_data_map.json
 */
class OpeningConfiguratorIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    protected OpeningConfiguratorService $configurator;
    protected OpeningLayoutEngine $layoutEngine;
    protected OpeningValidator $validator;

    protected Company $company;
    protected Partner $partner;
    protected ProjectStage $stage;
    protected \App\Models\User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configurator = new OpeningConfiguratorService();
        $this->layoutEngine = new OpeningLayoutEngine($this->configurator);
        $this->validator = new OpeningValidator($this->configurator);
    }

    protected function setUpDatabase(): void
    {
        if (!$this->hasRequiredTables()) {
            $this->markTestSkipped('Required tables not available. Run migrations first.');
        }

        $this->user = \App\Models\User::first() ?? \App\Models\User::factory()->create();

        $this->company = Company::firstOrCreate(
            ['name' => 'TCS Woodwork'],
            ['is_active' => true]
        );

        $this->partner = Partner::firstOrCreate(
            ['name' => 'Trottier Woodworking'],
            ['company_id' => $this->company->id, 'is_customer' => true]
        );

        $this->stage = ProjectStage::firstOrCreate(
            ['name' => 'Discovery', 'company_id' => $this->company->id],
            ['sort' => 1]
        );
    }

    // =========================================================================
    // FULL WORKFLOW TEST - 9 AUSTIN LANE BATHROOM VANITY
    // =========================================================================

    /** @test */
    public function it_completes_full_workflow_for_bathroom_vanity(): void
    {
        $this->setUpDatabase();

        // Step 1: Create project matching 9 Austin Lane
        $project = $this->createProject([
            'name' => '9 Austin Farm Road - Residential',
            'project_number' => 'TCS-001-9AustinFarmRoad',
            'project_type' => 'residential',
        ]);

        // Step 2: Create cabinet
        $cabinet = Cabinet::create([
            'project_id' => $project->id,
            'cabinet_number' => 'BTH1-B1-C1',
            'linear_feet' => 3.44,  // ~41.3125" = 3.44 LF
            'creator_id' => $this->user->id,
        ]);

        // Step 3: Create cabinet section (the opening)
        $section = CabinetSection::create([
            'cabinet_specification_id' => $cabinet->id,
            'section_number' => 1,
            'name' => 'Main Drawer Section',
            'section_type' => 'drawer_bank',
            'width_inches' => 37.8125,      // 37-13/16"
            'height_inches' => 32.75,        // 32-3/4"
            'opening_width_inches' => 37.8125,
            'opening_height_inches' => 26.0,  // Usable opening (accounting for face frame)
            'layout_direction' => 'vertical',
            'top_reveal_inches' => 0.125,
            'bottom_reveal_inches' => 0.125,
            'component_gap_inches' => 0.125,
        ]);

        // Step 4: Add drawers from CAD specs
        $lowerDrawer = Drawer::create([
            'section_id' => $section->id,
            'cabinet_id' => $section->cabinet_specification_id,
            'drawer_number' => 1,
            'drawer_name' => 'Lower Drawer',
            'drawer_position' => 'lower',
            'front_width_inches' => 37.8125,
            'front_height_inches' => 17.125,  // 17-1/8"
            'box_width_inches' => 37.1875,    // 37-3/16"
            'box_depth_inches' => 18.0,
            'box_height_inches' => 9.0,
            'slide_type' => 'Blum Tandem',
            'slide_model' => '563H4570B',
            'slide_length_inches' => 18,
            'soft_close' => true,
            'sort_order' => 1,
        ]);

        $upperDrawer = Drawer::create([
            'section_id' => $section->id,
            'cabinet_id' => $section->cabinet_specification_id,
            'drawer_number' => 2,
            'drawer_name' => 'U-Shaped Upper Drawer',
            'drawer_position' => 'upper',
            'front_width_inches' => 37.8125,
            'front_height_inches' => 7.0,
            'box_width_inches' => 37.1875,
            'box_depth_inches' => 18.0,
            'box_height_inches' => 9.0,
            'slide_type' => 'Blum Tandem',
            'slide_model' => '563H4570B',
            'slide_length_inches' => 18,
            'soft_close' => true,
            'notes' => 'U-Shaped drawer wraps around sink plumbing',
            'sort_order' => 2,
        ]);

        // Step 5: Calculate layout
        $layoutResult = $this->configurator->calculateSectionLayout($section);

        $this->assertEquals('vertical', $layoutResult['layout']);
        $this->assertCount(2, $layoutResult['positions']);

        // Step 6: Verify drawer positions
        $lowerDrawer->refresh();
        $upperDrawer->refresh();

        // Lower drawer at bottom reveal
        $this->assertEquals(0.125, $lowerDrawer->position_in_opening_inches);
        $this->assertNotNull($lowerDrawer->consumed_height_inches);

        // Upper drawer above lower
        $expectedUpperPos = 0.125 + 17.125 + 0.125;  // bottom + lower_height + gap
        $this->assertEquals($expectedUpperPos, $upperDrawer->position_in_opening_inches);

        // Step 7: Verify section space tracking
        $section->refresh();

        $this->assertNotNull($section->total_consumed_height_inches);
        $this->assertNotNull($section->remaining_height_inches);
        $this->assertGreaterThan(0, $section->remaining_height_inches);

        // Step 8: Validate configuration
        $validationResult = $this->validator->validateSection($section);

        $this->assertTrue($validationResult->isValid());
        $this->assertFalse($validationResult->hasHeightOverflow());
        $this->assertFalse($validationResult->hasOverlaps());
    }

    // =========================================================================
    // LAYOUT STRATEGY WORKFLOW
    // =========================================================================

    /** @test */
    public function it_applies_layout_strategy_and_validates(): void
    {
        $this->setUpDatabase();

        $section = $this->createFullSection();

        // Add drawers without positions
        $drawer1 = Drawer::create([
            'section_id' => $section->id,
            'cabinet_id' => $section->cabinet_specification_id,
            'drawer_number' => 1,
            'drawer_name' => 'Drawer 1',
            'front_width_inches' => 30.0,
            'front_height_inches' => 6.0,
            'box_width_inches' => 29.5,
            'box_depth_inches' => 18.0,
            'box_height_inches' => 5.0,
            'slide_length_inches' => 18,
            'sort_order' => 1,
        ]);

        $drawer2 = Drawer::create([
            'section_id' => $section->id,
            'cabinet_id' => $section->cabinet_specification_id,
            'drawer_number' => 2,
            'drawer_name' => 'Drawer 2',
            'front_width_inches' => 30.0,
            'front_height_inches' => 8.0,
            'box_width_inches' => 29.5,
            'box_depth_inches' => 18.0,
            'box_height_inches' => 7.0,
            'slide_length_inches' => 18,
            'sort_order' => 2,
        ]);

        $drawer3 = Drawer::create([
            'section_id' => $section->id,
            'cabinet_id' => $section->cabinet_specification_id,
            'drawer_number' => 3,
            'drawer_name' => 'Drawer 3',
            'front_width_inches' => 30.0,
            'front_height_inches' => 6.0,
            'box_width_inches' => 29.5,
            'box_depth_inches' => 18.0,
            'box_height_inches' => 5.0,
            'slide_length_inches' => 18,
            'sort_order' => 3,
        ]);

        // Apply layout strategy
        $layoutResult = $this->layoutEngine->applyStrategy($section, 'stack_from_bottom');
        $this->assertTrue($layoutResult['success']);

        // Validate
        $validationResult = $this->validator->validateSection($section);
        $this->assertTrue($validationResult->isValid());

        // Verify all drawers have positions
        $drawer1->refresh();
        $drawer2->refresh();
        $drawer3->refresh();

        $this->assertNotNull($drawer1->position_in_opening_inches);
        $this->assertNotNull($drawer2->position_in_opening_inches);
        $this->assertNotNull($drawer3->position_in_opening_inches);

        // Verify stacking order (bottom to top)
        $this->assertLessThan($drawer2->position_in_opening_inches, $drawer1->position_in_opening_inches + 6.0);
        $this->assertLessThan($drawer3->position_in_opening_inches, $drawer2->position_in_opening_inches + 8.0);
    }

    // =========================================================================
    // MIXED COMPONENT WORKFLOW
    // =========================================================================

    /** @test */
    public function it_handles_mixed_drawers_and_shelves(): void
    {
        $this->setUpDatabase();

        $section = $this->createFullSection([
            'opening_height_inches' => 36.0,  // Taller opening for mixed components
        ]);

        // Add drawer at bottom
        $drawer = Drawer::create([
            'section_id' => $section->id,
            'cabinet_id' => $section->cabinet_specification_id,
            'drawer_number' => 1,
            'drawer_name' => 'Bottom Drawer',
            'front_width_inches' => 30.0,
            'front_height_inches' => 8.0,
            'box_width_inches' => 29.5,
            'box_depth_inches' => 18.0,
            'box_height_inches' => 7.0,
            'slide_length_inches' => 18,
            'sort_order' => 1,
        ]);

        // Add shelf above
        $shelf = Shelf::create([
            'section_id' => $section->id,
            'cabinet_id' => $section->cabinet_specification_id,
            'shelf_number' => 1,
            'width_inches' => 30.0,
            'depth_inches' => 18.0,
            'thickness_inches' => 0.75,
            'sort_order' => 2,
        ]);

        // Calculate layout
        $result = $this->configurator->calculateSectionLayout($section);

        $this->assertEquals('vertical', $result['layout']);
        $this->assertCount(2, $result['positions']);

        // Validate
        $validationResult = $this->validator->validateSection($section);
        $this->assertTrue($validationResult->isValid());

        // Verify positions
        $drawer->refresh();
        $shelf->refresh();

        $this->assertNotNull($drawer->position_in_opening_inches);
        $this->assertNotNull($shelf->position_in_opening_inches);

        // Shelf should be above drawer
        $this->assertGreaterThan(
            $drawer->position_in_opening_inches + 8.0,
            $shelf->position_in_opening_inches
        );
    }

    // =========================================================================
    // SPACE CHECK WORKFLOW
    // =========================================================================

    /** @test */
    public function it_accurately_tracks_remaining_space(): void
    {
        $this->setUpDatabase();

        $section = $this->createFullSection([
            'opening_height_inches' => 24.0,
        ]);

        // Initial: all space available
        $initialSpace = $this->configurator->getRemainingSpace($section);
        $this->assertEquals(24.0, $initialSpace['remaining_height']);

        // Add 10" drawer
        Drawer::create([
            'section_id' => $section->id,
            'cabinet_id' => $section->cabinet_specification_id,
            'drawer_number' => 1,
            'drawer_name' => 'Drawer 1',
            'front_width_inches' => 30.0,
            'front_height_inches' => 10.0,
            'box_width_inches' => 29.5,
            'box_depth_inches' => 18.0,
            'box_height_inches' => 9.0,
            'slide_length_inches' => 18,
            'sort_order' => 1,
        ]);

        // Check remaining (should be 24 - 10 = 14")
        $afterFirst = $this->configurator->getRemainingSpace($section);
        $this->assertEquals(14.0, $afterFirst['remaining_height']);

        // Check if 10" drawer can fit (needs 10 + 0.125 gap = 10.125")
        $this->assertTrue($this->configurator->canFitComponent($section, 'drawer', 10.0));

        // Add another 10" drawer
        Drawer::create([
            'section_id' => $section->id,
            'cabinet_id' => $section->cabinet_specification_id,
            'drawer_number' => 2,
            'drawer_name' => 'Drawer 2',
            'front_width_inches' => 30.0,
            'front_height_inches' => 10.0,
            'box_width_inches' => 29.5,
            'box_depth_inches' => 18.0,
            'box_height_inches' => 9.0,
            'slide_length_inches' => 18,
            'sort_order' => 2,
        ]);

        // Check remaining (should be 24 - 20 - 0.125 gap = ~3.875")
        $afterSecond = $this->configurator->getRemainingSpace($section);
        $this->assertLessThan(5.0, $afterSecond['remaining_height']);

        // Can't fit another 4" drawer (minimum height)
        $this->assertFalse($this->configurator->canFitComponent($section, 'drawer', 4.0));
    }

    // =========================================================================
    // ERROR RECOVERY WORKFLOW
    // =========================================================================

    /** @test */
    public function it_recovers_from_invalid_configuration(): void
    {
        $this->setUpDatabase();

        $section = $this->createFullSection([
            'opening_height_inches' => 15.0,
        ]);

        // Create invalid configuration (overflow)
        $drawer1 = Drawer::create([
            'section_id' => $section->id,
            'cabinet_id' => $section->cabinet_specification_id,
            'drawer_number' => 1,
            'drawer_name' => 'Large Drawer 1',
            'front_width_inches' => 30.0,
            'front_height_inches' => 10.0,
            'box_width_inches' => 29.5,
            'box_depth_inches' => 18.0,
            'box_height_inches' => 9.0,
            'slide_length_inches' => 18,
            'sort_order' => 1,
        ]);

        $drawer2 = Drawer::create([
            'section_id' => $section->id,
            'cabinet_id' => $section->cabinet_specification_id,
            'drawer_number' => 2,
            'drawer_name' => 'Large Drawer 2',
            'front_width_inches' => 30.0,
            'front_height_inches' => 10.0,  // Total 20" in 15" opening
            'box_width_inches' => 29.5,
            'box_depth_inches' => 18.0,
            'box_height_inches' => 9.0,
            'slide_length_inches' => 18,
            'sort_order' => 2,
        ]);

        // Calculate layout (will overflow)
        $this->configurator->calculateSectionLayout($section);

        // Validate (should fail)
        $validationResult = $this->validator->validateSection($section);
        $this->assertFalse($validationResult->isValid());
        $this->assertTrue($validationResult->hasHeightOverflow());

        // Get overflow amount for user feedback
        $overflow = $validationResult->getHeightOverflow();
        $this->assertGreaterThan(0, $overflow);

        // Recovery: Remove one drawer
        $drawer2->delete();

        // Recalculate
        $this->configurator->calculateSectionLayout($section);

        // Should now be valid
        $validationAfterFix = $this->validator->validateSection($section);
        $this->assertTrue($validationAfterFix->isValid());
    }

    // =========================================================================
    // HELPER METHODS
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

    protected function createFullSection(array $sectionAttributes = []): CabinetSection
    {
        $project = $this->createProject();

        $cabinet = Cabinet::create([
            'project_id' => $project->id,
            'cabinet_number' => 'TEST-CAB-1',
            'linear_feet' => 3.0,
            'creator_id' => $this->user->id,
        ]);

        return CabinetSection::create(array_merge([
            'cabinet_specification_id' => $cabinet->id,
            'section_number' => 1,
            'name' => 'Test Section',
            'section_type' => 'drawer_bank',
            'width_inches' => 30.0,
            'height_inches' => 30.0,
            'opening_width_inches' => 30.0,
            'opening_height_inches' => 26.0,
            'layout_direction' => 'vertical',
            'top_reveal_inches' => 0.125,
            'bottom_reveal_inches' => 0.125,
            'component_gap_inches' => 0.125,
        ], $sectionAttributes));
    }

    protected function hasRequiredTables(): bool
    {
        $requiredTables = [
            'projects_projects',
            'projects_cabinet_specifications',
            'projects_cabinet_sections',
            'projects_drawers',
            'projects_shelves',
        ];

        foreach ($requiredTables as $table) {
            if (!\Illuminate\Support\Facades\Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }
}
