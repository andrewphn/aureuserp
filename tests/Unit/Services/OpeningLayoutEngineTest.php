<?php

namespace Tests\Unit\Services;

use App\Services\OpeningLayoutEngine;
use App\Services\OpeningConfiguratorService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Webkul\Project\Models\CabinetSection;
use Webkul\Project\Models\Drawer;
use Webkul\Project\Models\Cabinet;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\ProjectStage;
use Webkul\Partner\Models\Partner;
use Webkul\Support\Models\Company;

/**
 * Unit tests for OpeningLayoutEngine
 *
 * Tests auto-arrangement strategies for component positioning.
 * Uses real project data from 9 Austin Lane bathroom vanity.
 *
 * @see sample/9 Austin Lane/project_10_full_data_map.json
 */
class OpeningLayoutEngineTest extends TestCase
{
    use DatabaseTransactions;

    protected OpeningLayoutEngine $engine;
    protected OpeningConfiguratorService $configurator;
    protected Company $company;
    protected Partner $partner;
    protected ProjectStage $stage;
    protected \App\Models\User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configurator = new OpeningConfiguratorService();
        $this->engine = new OpeningLayoutEngine($this->configurator);
    }

    protected function setUpDatabase(): void
    {
        if (!$this->hasRequiredTables()) {
            $this->markTestSkipped('Required tables not available. Run migrations first.');
        }

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
    // PURE UNIT TESTS (No Database Required)
    // =========================================================================

    /** @test */
    public function it_returns_available_strategies(): void
    {
        $strategies = $this->engine->getAvailableStrategies();

        $this->assertIsArray($strategies);
        $this->assertContains('stack_from_bottom', $strategies);
        $this->assertContains('stack_from_top', $strategies);
        $this->assertContains('equal_distribution', $strategies);
        $this->assertContains('weighted_distribution', $strategies);
    }

    // =========================================================================
    // STACK FROM BOTTOM STRATEGY (Database Required)
    // =========================================================================

    /** @test */
    public function it_stacks_drawers_from_bottom(): void
    {
        $this->setUpDatabase();

        $section = $this->createCabinetSection([
            'opening_height_inches' => 26.0,
            'bottom_reveal_inches' => 0.125,
        ]);

        // Create drawers like 9 Austin Lane vanity
        $drawer1 = $this->createDrawer($section, [
            'front_height_inches' => 17.125,  // Lower drawer
            'sort_order' => 1,
        ]);

        $drawer2 = $this->createDrawer($section, [
            'front_height_inches' => 7.0,  // Upper drawer
            'sort_order' => 2,
        ]);

        // Apply stack from bottom
        $result = $this->engine->applyStrategy($section, 'stack_from_bottom');

        $this->assertTrue($result['success']);

        $drawer1->refresh();
        $drawer2->refresh();

        // First drawer at bottom reveal
        $this->assertEquals(0.125, $drawer1->position_in_opening_inches);

        // Second drawer stacked above
        $expectedPos = 0.125 + 17.125 + 0.125; // bottom + drawer1 + gap
        $this->assertEquals($expectedPos, $drawer2->position_in_opening_inches);
    }

    // =========================================================================
    // STACK FROM TOP STRATEGY (Database Required)
    // =========================================================================

    /** @test */
    public function it_stacks_drawers_from_top(): void
    {
        $this->setUpDatabase();

        $section = $this->createCabinetSection([
            'opening_height_inches' => 26.0,
            'top_reveal_inches' => 0.125,
        ]);

        $drawer1 = $this->createDrawer($section, [
            'front_height_inches' => 7.0,
            'sort_order' => 1,
        ]);

        $drawer2 = $this->createDrawer($section, [
            'front_height_inches' => 17.125,
            'sort_order' => 2,
        ]);

        $result = $this->engine->applyStrategy($section, 'stack_from_top');

        $this->assertTrue($result['success']);

        $drawer1->refresh();
        $drawer2->refresh();

        // Top drawer should be at: opening_height - top_reveal - drawer_height
        $expectedTopDrawerPos = 26.0 - 0.125 - 7.0;
        $this->assertEquals($expectedTopDrawerPos, $drawer1->position_in_opening_inches);
    }

    // =========================================================================
    // EQUAL DISTRIBUTION STRATEGY (Database Required)
    // =========================================================================

    /** @test */
    public function it_distributes_drawers_equally(): void
    {
        $this->setUpDatabase();

        $section = $this->createCabinetSection([
            'opening_height_inches' => 24.0,
            'top_reveal_inches' => 0.125,
            'bottom_reveal_inches' => 0.125,
        ]);

        // Create three equal-height drawers
        $drawer1 = $this->createDrawer($section, ['front_height_inches' => 6.0, 'sort_order' => 1]);
        $drawer2 = $this->createDrawer($section, ['front_height_inches' => 6.0, 'sort_order' => 2]);
        $drawer3 = $this->createDrawer($section, ['front_height_inches' => 6.0, 'sort_order' => 3]);

        $result = $this->engine->applyStrategy($section, 'equal_distribution');

        $this->assertTrue($result['success']);

        $drawer1->refresh();
        $drawer2->refresh();
        $drawer3->refresh();

        // Total drawer height = 18"
        // Available space = 24 - 0.125 - 0.125 = 23.75"
        // Remaining space = 23.75 - 18 = 5.75"
        // Gap count = 2 (between drawers)
        // Each gap = 5.75 / 2 = 2.875"

        // Verify drawers are positioned with equal spacing
        $gap1 = $drawer2->position_in_opening_inches - ($drawer1->position_in_opening_inches + 6.0);
        $gap2 = $drawer3->position_in_opening_inches - ($drawer2->position_in_opening_inches + 6.0);

        // Gaps should be approximately equal
        $this->assertEqualsWithDelta($gap1, $gap2, 0.01);
    }

    // =========================================================================
    // WEIGHTED DISTRIBUTION STRATEGY (Database Required)
    // =========================================================================

    /** @test */
    public function it_distributes_based_on_component_weights(): void
    {
        $this->setUpDatabase();

        $section = $this->createCabinetSection([
            'opening_height_inches' => 30.0,
        ]);

        // Mix of drawer sizes (like real cabinet)
        $drawer1 = $this->createDrawer($section, ['front_height_inches' => 5.0, 'sort_order' => 1]);
        $drawer2 = $this->createDrawer($section, ['front_height_inches' => 10.0, 'sort_order' => 2]);
        $drawer3 = $this->createDrawer($section, ['front_height_inches' => 5.0, 'sort_order' => 3]);

        $result = $this->engine->applyStrategy($section, 'weighted_distribution');

        $this->assertTrue($result['success']);

        $drawer1->refresh();
        $drawer2->refresh();
        $drawer3->refresh();

        // Verify all drawers are positioned
        $this->assertNotNull($drawer1->position_in_opening_inches);
        $this->assertNotNull($drawer2->position_in_opening_inches);
        $this->assertNotNull($drawer3->position_in_opening_inches);

        // Verify no overlap (each drawer above previous)
        $this->assertGreaterThan(
            $drawer1->position_in_opening_inches + 5.0,
            $drawer2->position_in_opening_inches
        );
        $this->assertGreaterThan(
            $drawer2->position_in_opening_inches + 10.0,
            $drawer3->position_in_opening_inches
        );
    }

    // =========================================================================
    // ERROR HANDLING (Database Required)
    // =========================================================================

    /** @test */
    public function it_fails_for_invalid_strategy(): void
    {
        $this->setUpDatabase();

        $section = $this->createCabinetSection();

        $result = $this->engine->applyStrategy($section, 'invalid_strategy');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Unknown strategy', $result['error']);
    }

    /** @test */
    public function it_handles_empty_section(): void
    {
        $this->setUpDatabase();

        $section = $this->createCabinetSection();

        // No drawers or components
        $result = $this->engine->applyStrategy($section, 'stack_from_bottom');

        $this->assertTrue($result['success']);
        $this->assertEmpty($result['positions']);
    }

    /** @test */
    public function it_fails_when_components_overflow(): void
    {
        $this->setUpDatabase();

        $section = $this->createCabinetSection([
            'opening_height_inches' => 10.0,
        ]);

        // Create drawers that exceed opening height
        $this->createDrawer($section, ['front_height_inches' => 8.0, 'sort_order' => 1]);
        $this->createDrawer($section, ['front_height_inches' => 8.0, 'sort_order' => 2]);

        $result = $this->engine->applyStrategy($section, 'stack_from_bottom');

        // Should either fail or report overflow
        if (!$result['success']) {
            $this->assertStringContainsString('overflow', strtolower($result['error'] ?? ''));
        } else {
            $this->assertTrue($result['has_overflow'] ?? false);
        }
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

    protected function createCabinet(Project $project, array $attributes = []): Cabinet
    {
        return Cabinet::create(array_merge([
            'project_id' => $project->id,
            'cabinet_number' => 'TEST-CAB-1',
            'linear_feet' => 3.0,
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
            'name' => 'Test Section',
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
            'drawer_number' => $attributes['sort_order'] ?? 1,
            'drawer_name' => 'Test Drawer ' . ($attributes['sort_order'] ?? 1),
            'front_width_inches' => 37.8125,
            'front_height_inches' => 7.0,
            'box_width_inches' => 37.1875,
            'box_depth_inches' => 18.0,
            'box_height_inches' => 9.0,
            'slide_length_inches' => 18,
            'sort_order' => 1,
        ], $attributes));
    }

    protected function hasRequiredTables(): bool
    {
        $requiredTables = [
            'projects_cabinet_sections',
            'projects_drawers',
        ];

        foreach ($requiredTables as $table) {
            if (!\Illuminate\Support\Facades\Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }
}
