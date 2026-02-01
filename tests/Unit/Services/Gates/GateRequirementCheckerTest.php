<?php

namespace Tests\Unit\Services\Gates;

use Tests\TestCase;
use Webkul\Project\Models\Gate;
use Webkul\Project\Models\GateRequirement;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\Room;
use Webkul\Project\Models\Task;
use Webkul\Project\Services\Gates\GateRequirementChecker;
use Webkul\Project\Services\Gates\RequirementCheckResult;
use Webkul\Partner\Models\Partner;
use Webkul\Sale\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GateRequirementCheckerTest extends TestCase
{
    use RefreshDatabase;

    protected GateRequirementChecker $checker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->checker = new GateRequirementChecker();
    }

    // ========================
    // TYPE_FIELD_NOT_NULL Tests
    // ========================

    /** @test */
    public function field_not_null_passes_when_field_has_value()
    {
        $project = Project::factory()->create(['name' => 'Test Project']);
        $requirement = GateRequirement::factory()->fieldNotNull('name')->create();

        $result = $this->checker->check($project, $requirement);

        $this->assertTrue($result->passed);
        $this->assertStringContainsString('has value', $result->message);
    }

    /** @test */
    public function field_not_null_fails_when_field_is_null()
    {
        $project = Project::factory()->create(['description' => null]);
        $requirement = GateRequirement::factory()->fieldNotNull('description')->create();

        $result = $this->checker->check($project, $requirement);

        $this->assertFalse($result->passed);
        $this->assertStringContainsString('empty', $result->message);
    }

    /** @test */
    public function field_not_null_fails_when_field_is_empty_string()
    {
        $project = Project::factory()->create(['description' => '']);
        $requirement = GateRequirement::factory()->fieldNotNull('description')->create();

        $result = $this->checker->check($project, $requirement);

        $this->assertFalse($result->passed);
    }

    /** @test */
    public function field_not_null_can_target_partner_model()
    {
        $partner = Partner::factory()->create(['name' => 'Test Partner']);
        $project = Project::factory()->create(['partner_id' => $partner->id]);
        $requirement = GateRequirement::factory()
            ->fieldNotNull('name', 'Partner')
            ->create();

        $result = $this->checker->check($project, $requirement);

        $this->assertTrue($result->passed);
    }

    // ========================
    // TYPE_FIELD_EQUALS Tests
    // ========================

    /** @test */
    public function field_equals_passes_when_field_matches()
    {
        $project = Project::factory()->create(['visibility' => 'public']);
        $requirement = GateRequirement::factory()
            ->fieldEquals('visibility', 'public')
            ->create();

        $result = $this->checker->check($project, $requirement);

        $this->assertTrue($result->passed);
    }

    /** @test */
    public function field_equals_fails_when_field_does_not_match()
    {
        $project = Project::factory()->create(['visibility' => 'private']);
        $requirement = GateRequirement::factory()
            ->fieldEquals('visibility', 'public')
            ->create();

        $result = $this->checker->check($project, $requirement);

        $this->assertFalse($result->passed);
    }

    /** @test */
    public function field_equals_works_with_boolean_values()
    {
        $project = Project::factory()->create(['is_active' => true]);
        $requirement = GateRequirement::factory()
            ->fieldEquals('is_active', 'true')
            ->create();

        $result = $this->checker->check($project, $requirement);

        $this->assertTrue($result->passed);
    }

    // ========================
    // TYPE_FIELD_GREATER_THAN Tests
    // ========================

    /** @test */
    public function field_greater_than_passes_when_value_exceeds()
    {
        $project = Project::factory()->create(['allocated_hours' => 100]);
        $requirement = GateRequirement::factory()
            ->fieldGreaterThan('allocated_hours', 50)
            ->create();

        $result = $this->checker->check($project, $requirement);

        $this->assertTrue($result->passed);
    }

    /** @test */
    public function field_greater_than_fails_when_value_is_less()
    {
        $project = Project::factory()->create(['allocated_hours' => 30]);
        $requirement = GateRequirement::factory()
            ->fieldGreaterThan('allocated_hours', 50)
            ->create();

        $result = $this->checker->check($project, $requirement);

        $this->assertFalse($result->passed);
    }

    /** @test */
    public function field_greater_than_fails_when_equal()
    {
        $project = Project::factory()->create(['allocated_hours' => 50]);
        $requirement = GateRequirement::factory()
            ->fieldGreaterThan('allocated_hours', 50)
            ->create();

        $result = $this->checker->check($project, $requirement);

        $this->assertFalse($result->passed);
    }

    // ========================
    // TYPE_RELATION_EXISTS Tests
    // ========================

    /** @test */
    public function relation_exists_passes_when_relation_has_records()
    {
        $project = Project::factory()->create();
        Room::factory()->create(['project_id' => $project->id]);
        $requirement = GateRequirement::factory()
            ->relationExists('rooms')
            ->create();

        $result = $this->checker->check($project, $requirement);

        $this->assertTrue($result->passed);
    }

    /** @test */
    public function relation_exists_fails_when_relation_is_empty()
    {
        $project = Project::factory()->create();
        $requirement = GateRequirement::factory()
            ->relationExists('rooms')
            ->create();

        $result = $this->checker->check($project, $requirement);

        $this->assertFalse($result->passed);
    }

    /** @test */
    public function relation_exists_fails_for_nonexistent_relation()
    {
        $project = Project::factory()->create();
        $requirement = GateRequirement::factory()->create([
            'requirement_type' => GateRequirement::TYPE_RELATION_EXISTS,
            'target_relation' => 'nonexistentRelation',
        ]);

        $result = $this->checker->check($project, $requirement);

        $this->assertFalse($result->passed);
        $this->assertStringContainsString('does not exist', $result->message);
    }

    // ========================
    // TYPE_RELATION_COUNT Tests
    // ========================

    /** @test */
    public function relation_count_passes_with_equals_operator()
    {
        $project = Project::factory()->create();
        Room::factory()->count(3)->create(['project_id' => $project->id]);
        $requirement = GateRequirement::factory()
            ->relationCount('rooms', 3, '=')
            ->create();

        $result = $this->checker->check($project, $requirement);

        $this->assertTrue($result->passed);
    }

    /** @test */
    public function relation_count_passes_with_greater_than_or_equal_operator()
    {
        $project = Project::factory()->create();
        Room::factory()->count(5)->create(['project_id' => $project->id]);
        $requirement = GateRequirement::factory()
            ->relationCount('rooms', 3, '>=')
            ->create();

        $result = $this->checker->check($project, $requirement);

        $this->assertTrue($result->passed);
    }

    /** @test */
    public function relation_count_fails_when_count_is_less()
    {
        $project = Project::factory()->create();
        Room::factory()->count(2)->create(['project_id' => $project->id]);
        $requirement = GateRequirement::factory()
            ->relationCount('rooms', 5, '>=')
            ->create();

        $result = $this->checker->check($project, $requirement);

        $this->assertFalse($result->passed);
    }

    /** @test */
    public function relation_count_supports_less_than_operator()
    {
        $project = Project::factory()->create();
        Room::factory()->count(2)->create(['project_id' => $project->id]);
        $requirement = GateRequirement::factory()
            ->relationCount('rooms', 5, '<')
            ->create();

        $result = $this->checker->check($project, $requirement);

        $this->assertTrue($result->passed);
    }

    /** @test */
    public function relation_count_supports_not_equals_operator()
    {
        $project = Project::factory()->create();
        Room::factory()->count(2)->create(['project_id' => $project->id]);
        $requirement = GateRequirement::factory()
            ->relationCount('rooms', 5, '!=')
            ->create();

        $result = $this->checker->check($project, $requirement);

        $this->assertTrue($result->passed);
    }

    // ========================
    // TYPE_ALL_CHILDREN_PASS Tests
    // ========================

    /** @test */
    public function all_children_pass_succeeds_when_all_match()
    {
        $project = Project::factory()->create();
        Room::factory()->count(3)->create([
            'project_id' => $project->id,
            'room_type' => 'kitchen',
        ]);
        $requirement = GateRequirement::factory()
            ->allChildrenPass('rooms', 'room_type', 'kitchen')
            ->create();

        $result = $this->checker->check($project, $requirement);

        $this->assertTrue($result->passed);
    }

    /** @test */
    public function all_children_pass_fails_when_some_dont_match()
    {
        $project = Project::factory()->create();
        Room::factory()->count(2)->create([
            'project_id' => $project->id,
            'room_type' => 'kitchen',
        ]);
        Room::factory()->create([
            'project_id' => $project->id,
            'room_type' => 'bathroom',
        ]);
        $requirement = GateRequirement::factory()
            ->allChildrenPass('rooms', 'room_type', 'kitchen')
            ->create();

        $result = $this->checker->check($project, $requirement);

        $this->assertFalse($result->passed);
        $this->assertStringContainsString('2/3', $result->message);
    }

    /** @test */
    public function all_children_pass_fails_when_no_children_exist()
    {
        $project = Project::factory()->create();
        $requirement = GateRequirement::factory()
            ->allChildrenPass('rooms', 'room_type', 'kitchen')
            ->create();

        $result = $this->checker->check($project, $requirement);

        $this->assertFalse($result->passed);
        $this->assertStringContainsString('No', $result->message);
    }

    // ========================
    // TYPE_TASK_COMPLETED Tests
    // ========================
    // NOTE: Task model doesn't have task_type field in current schema.
    // These tests verify the requirement checker logic works correctly.
    // In production, task_type would need to be added to Task model or
    // the requirement type would need to use a different field.

    /** @test */
    public function task_completed_fails_when_no_matching_task()
    {
        $project = Project::factory()->create();
        // No task created with matching task_type
        $requirement = GateRequirement::factory()
            ->taskCompleted('design_review')
            ->create();

        $result = $this->checker->check($project, $requirement);

        // Should fail because no task with task_type='design_review' exists
        $this->assertFalse($result->passed);
    }

    // ========================
    // TYPE_CUSTOM_CHECK Tests
    // ========================

    /** @test */
    public function custom_check_fails_when_class_not_found()
    {
        $project = Project::factory()->create();
        $requirement = GateRequirement::factory()
            ->customCheck('App\\NonExistentClass', 'check')
            ->create();

        $result = $this->checker->check($project, $requirement);

        $this->assertFalse($result->passed);
        $this->assertStringContainsString('not found', $result->message);
    }

    // ========================
    // Error Handling Tests
    // ========================

    /** @test */
    public function check_returns_result_object()
    {
        $project = Project::factory()->create();
        $requirement = GateRequirement::factory()->create();

        $result = $this->checker->check($project, $requirement);

        $this->assertInstanceOf(RequirementCheckResult::class, $result);
    }

    /** @test */
    public function check_includes_details_in_result()
    {
        $project = Project::factory()->create(['name' => 'Test']);
        $requirement = GateRequirement::factory()->fieldNotNull('name')->create();

        $result = $this->checker->check($project, $requirement);

        $this->assertArrayHasKey('field', $result->details);
        $this->assertArrayHasKey('value', $result->details);
        $this->assertEquals('name', $result->details['field']);
    }

    /** @test */
    public function unknown_requirement_type_fails_gracefully()
    {
        $project = Project::factory()->create();
        $requirement = GateRequirement::factory()->create([
            'requirement_type' => 'unknown_type',
        ]);

        $result = $this->checker->check($project, $requirement);

        $this->assertFalse($result->passed);
        $this->assertStringContainsString('Unknown requirement type', $result->message);
    }

    /** @test */
    public function exception_during_check_returns_error_result()
    {
        $project = Project::factory()->create();
        // Create requirement that might cause an error (e.g., accessing non-existent property)
        $requirement = GateRequirement::factory()->create([
            'requirement_type' => GateRequirement::TYPE_FIELD_NOT_NULL,
            'target_model' => 'NonExistentModel',
            'target_field' => 'some_field',
        ]);

        $result = $this->checker->check($project, $requirement);

        // Should not throw, should return error result
        $this->assertFalse($result->passed);
    }
}
