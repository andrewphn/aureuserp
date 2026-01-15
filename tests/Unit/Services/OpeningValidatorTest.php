<?php

namespace Tests\Unit\Services;

use App\Services\OpeningValidator;
use App\Services\OpeningConfiguratorService;
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
 * Unit tests for OpeningValidator
 *
 * Tests validation logic for component configurations within openings.
 * Uses real project data from 9 Austin Lane bathroom vanity.
 *
 * @see sample/9 Austin Lane/project_10_full_data_map.json
 */
class OpeningValidatorTest extends TestCase
{
    use DatabaseTransactions;

    protected OpeningValidator $validator;
    protected OpeningConfiguratorService $configurator;
    protected Company $company;
    protected Partner $partner;
    protected ProjectStage $stage;
    protected \App\Models\User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configurator = new OpeningConfiguratorService();
        $this->validator = new OpeningValidator($this->configurator);
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
    // VALID CONFIGURATION TESTS (Database Required)
    // =========================================================================

    /** @test */
    public function it_validates_correct_two_drawer_configuration(): void
    {
        $this->setUpDatabase();

        // Real 9 Austin Lane bathroom vanity configuration
        $section = $this->createCabinetSection([
            'opening_width_inches' => 37.8125,  // 37-13/16"
            'opening_height_inches' => 26.0,
        ]);

        $drawer1 = $this->createDrawer($section, [
            'front_height_inches' => 17.125,  // 17-1/8"
            'sort_order' => 1,
        ]);

        $drawer2 = $this->createDrawer($section, [
            'front_height_inches' => 7.0,
            'sort_order' => 2,
        ]);

        // Calculate positions
        $this->configurator->calculateSectionLayout($section);

        // Validate
        $result = $this->validator->validateSection($section);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->errors);
    }

    /** @test */
    public function it_validates_empty_section(): void
    {
        $this->setUpDatabase();

        $section = $this->createCabinetSection();

        $result = $this->validator->validateSection($section);

        // Empty section should be valid (no components = no violations)
        $this->assertTrue($result->isValid());
    }

    // =========================================================================
    // HEIGHT OVERFLOW TESTS (Database Required)
    // =========================================================================

    /** @test */
    public function it_detects_height_overflow(): void
    {
        $this->setUpDatabase();

        $section = $this->createCabinetSection([
            'opening_height_inches' => 10.0,  // Small opening
        ]);

        // Create drawers that exceed the opening
        $this->createDrawer($section, ['front_height_inches' => 8.0, 'sort_order' => 1]);
        $this->createDrawer($section, ['front_height_inches' => 8.0, 'sort_order' => 2]);

        // Calculate positions (will overflow)
        $this->configurator->calculateSectionLayout($section);

        $result = $this->validator->validateSection($section);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasHeightOverflow());
    }

    /** @test */
    public function it_calculates_overflow_amount(): void
    {
        $this->setUpDatabase();

        $section = $this->createCabinetSection([
            'opening_height_inches' => 10.0,
        ]);

        // 8 + 8 = 16" of drawers in 10" opening = 6" overflow (plus gaps)
        $this->createDrawer($section, ['front_height_inches' => 8.0, 'sort_order' => 1]);
        $this->createDrawer($section, ['front_height_inches' => 8.0, 'sort_order' => 2]);

        $this->configurator->calculateSectionLayout($section);

        $result = $this->validator->validateSection($section);

        // Overflow should be positive
        $this->assertGreaterThan(0, $result->getOverflowAmount());
    }

    // =========================================================================
    // WIDTH OVERFLOW TESTS (Database Required)
    // =========================================================================

    /** @test */
    public function it_detects_width_overflow_for_horizontal_layout(): void
    {
        $this->setUpDatabase();

        $section = $this->createCabinetSection([
            'opening_width_inches' => 20.0,
            'layout_direction' => 'horizontal',
        ]);

        // Create components that exceed width
        $this->createDrawer($section, ['front_width_inches' => 15.0, 'sort_order' => 1]);
        $this->createDrawer($section, ['front_width_inches' => 15.0, 'sort_order' => 2]);

        $this->configurator->calculateSectionLayout($section);

        $result = $this->validator->validateSection($section);

        // Should detect width overflow for horizontal layout
        $this->assertFalse($result->isValid());
    }

    // =========================================================================
    // OVERLAP DETECTION TESTS (Database Required)
    // =========================================================================

    /** @test */
    public function it_detects_overlapping_components(): void
    {
        $this->setUpDatabase();

        $section = $this->createCabinetSection([
            'opening_height_inches' => 26.0,
        ]);

        // Create two drawers
        $drawer1 = $this->createDrawer($section, [
            'front_height_inches' => 10.0,
            'sort_order' => 1,
        ]);

        $drawer2 = $this->createDrawer($section, [
            'front_height_inches' => 10.0,
            'sort_order' => 2,
        ]);

        // Manually set overlapping positions (bypassing layout engine)
        $drawer1->position_in_opening_inches = 5.0;
        $drawer1->consumed_height_inches = 10.0;
        $drawer1->save();

        $drawer2->position_in_opening_inches = 8.0;  // Overlaps with drawer1 (5+10=15, but starts at 8)
        $drawer2->consumed_height_inches = 10.0;
        $drawer2->save();

        $result = $this->validator->validateSection($section);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasOverlaps());
    }

    /** @test */
    public function it_allows_adjacent_non_overlapping_components(): void
    {
        $this->setUpDatabase();

        $section = $this->createCabinetSection([
            'opening_height_inches' => 26.0,
        ]);

        $drawer1 = $this->createDrawer($section, [
            'front_height_inches' => 10.0,
            'sort_order' => 1,
        ]);

        $drawer2 = $this->createDrawer($section, [
            'front_height_inches' => 10.0,
            'sort_order' => 2,
        ]);

        // Set adjacent positions with small gap
        $drawer1->position_in_opening_inches = 0.125;
        $drawer1->consumed_height_inches = 10.125;  // height + gap
        $drawer1->save();

        $drawer2->position_in_opening_inches = 10.25;  // Just above drawer1
        $drawer2->consumed_height_inches = 10.0;
        $drawer2->save();

        $result = $this->validator->validateSection($section);

        // Should not report overlaps
        $this->assertFalse($result->hasOverlaps());
    }

    // =========================================================================
    // MINIMUM HEIGHT VALIDATION (Database Required)
    // =========================================================================

    /** @test */
    public function it_validates_minimum_drawer_height(): void
    {
        $this->setUpDatabase();

        $section = $this->createCabinetSection();

        // Create drawer below minimum height (4")
        $drawer = $this->createDrawer($section, [
            'front_height_inches' => 3.0,  // Below 4" minimum
            'sort_order' => 1,
        ]);

        $result = $this->validator->validateSection($section);

        // Should report minimum height violation
        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasMinimumHeightViolation());
    }

    /** @test */
    public function it_validates_minimum_shelf_opening(): void
    {
        $this->setUpDatabase();

        $section = $this->createCabinetSection([
            'opening_height_inches' => 4.0,  // Below 5.5" minimum for shelf
        ]);

        // Create a shelf
        $shelf = Shelf::create([
            'section_id' => $section->id,
            'cabinet_id' => $section->cabinet_specification_id,
            'shelf_number' => 1,
            'width_inches' => 37.8125,
            'depth_inches' => 18.0,
            'thickness_inches' => 0.75,
            'sort_order' => 1,
        ]);

        $result = $this->validator->validateSection($section);

        // Should report minimum height violation for shelf
        $this->assertFalse($result->isValid());
    }

    // =========================================================================
    // WOULD FIT CHECK (Database Required)
    // =========================================================================

    /** @test */
    public function it_checks_if_new_component_would_fit(): void
    {
        $this->setUpDatabase();

        $section = $this->createCabinetSection([
            'opening_height_inches' => 26.0,
        ]);

        // Add one drawer
        $this->createDrawer($section, [
            'front_height_inches' => 17.125,
            'sort_order' => 1,
        ]);

        $this->configurator->calculateSectionLayout($section);

        // Check if 7" drawer would fit (should fit - matches 9 Austin Lane)
        $this->assertTrue($this->validator->wouldFit($section, 'drawer', 7.0));

        // Check if 15" drawer would fit (should not - would overflow)
        $this->assertFalse($this->validator->wouldFit($section, 'drawer', 15.0));
    }

    /** @test */
    public function it_accounts_for_gaps_in_would_fit_check(): void
    {
        $this->setUpDatabase();

        $section = $this->createCabinetSection([
            'opening_height_inches' => 20.0,
            'component_gap_inches' => 0.125,
        ]);

        // Add drawer consuming 10"
        $this->createDrawer($section, [
            'front_height_inches' => 10.0,
            'sort_order' => 1,
        ]);

        $this->configurator->calculateSectionLayout($section);

        // Remaining = 20 - 10 = 10"
        // Adding new component needs: height + gap (0.125)
        // So a 9.875" drawer needs 10" total - should just fit
        $this->assertTrue($this->validator->wouldFit($section, 'drawer', 9.75));

        // But 10" drawer needs 10.125" - should not fit
        $this->assertFalse($this->validator->wouldFit($section, 'drawer', 10.0));
    }

    // =========================================================================
    // VALIDATION RESULT STRUCTURE (Database Required)
    // =========================================================================

    /** @test */
    public function validation_result_contains_all_required_fields(): void
    {
        $this->setUpDatabase();

        $section = $this->createCabinetSection();

        $this->createDrawer($section, ['front_height_inches' => 7.0, 'sort_order' => 1]);
        $this->configurator->calculateSectionLayout($section);

        $result = $this->validator->validateSection($section);

        // Check result structure
        $this->assertIsBool($result->isValid());
        $this->assertIsArray($result->errors);
        $this->assertIsArray($result->warnings);
        $this->assertIsFloat($result->getHeightOverflow());
        $this->assertIsFloat($result->getWidthOverflow());
    }

    /** @test */
    public function validation_result_includes_detailed_messages(): void
    {
        $this->setUpDatabase();

        $section = $this->createCabinetSection([
            'opening_height_inches' => 10.0,
        ]);

        // Create overflow condition
        $this->createDrawer($section, ['front_height_inches' => 8.0, 'sort_order' => 1]);
        $this->createDrawer($section, ['front_height_inches' => 8.0, 'sort_order' => 2]);

        $this->configurator->calculateSectionLayout($section);

        $result = $this->validator->validateSection($section);

        $errors = $result->errors;
        $this->assertNotEmpty($errors);

        // Error messages should be human-readable
        $errorText = implode(' ', $errors);
        $this->assertTrue(
            str_contains($errorText, 'height') ||
            str_contains($errorText, 'overflow') ||
            str_contains($errorText, 'exceed')
        );
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

    protected function hasRequiredTables(): bool
    {
        $requiredTables = [
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
