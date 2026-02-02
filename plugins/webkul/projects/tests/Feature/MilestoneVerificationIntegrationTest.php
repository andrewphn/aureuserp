<?php

namespace Webkul\Project\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;
use Webkul\Project\Models\Milestone;
use Webkul\Project\Models\MilestoneRequirement;
use Webkul\Project\Models\MilestoneTemplate;
use Webkul\Project\Models\MilestoneRequirementTemplate;
use Webkul\Project\Models\Project;
use Webkul\Project\Services\MilestoneRequirementVerifier;
use Webkul\Project\Services\ProjectMilestoneService;

class MilestoneVerificationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestTables();
    }

    protected function createTestTables(): void
    {
        // Create users table if not exists
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->timestamps();
            });
        }

        // Create projects table
        if (!Schema::hasTable('projects_projects')) {
            Schema::create('projects_projects', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('project_number')->nullable();
                $table->foreignId('partner_id')->nullable();
                $table->foreignId('creator_id')->nullable();
                $table->string('current_production_stage')->nullable();
                $table->timestamp('design_approved_at')->nullable();
                $table->string('google_drive_root_folder_id')->nullable();
                $table->timestamps();
            });
        }

        // Create milestones table
        if (!Schema::hasTable('projects_milestones')) {
            Schema::create('projects_milestones', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('production_stage')->nullable();
                $table->boolean('is_critical')->default(false);
                $table->integer('sort_order')->default(0);
                $table->date('deadline')->nullable();
                $table->boolean('is_completed')->default(false);
                $table->timestamp('completed_at')->nullable();
                $table->string('completed_by_gate')->nullable();
                $table->foreignId('project_id');
                $table->foreignId('creator_id')->nullable();
                $table->timestamps();
            });
        }

        // Create milestone requirements table
        if (!Schema::hasTable('projects_milestone_requirements')) {
            Schema::create('projects_milestone_requirements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('milestone_id');
                $table->foreignId('template_id')->nullable();
                $table->string('name');
                $table->string('requirement_type');
                $table->text('description')->nullable();
                $table->json('config')->nullable();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_required')->default(true);
                $table->boolean('is_verified')->default(false);
                $table->timestamp('verified_at')->nullable();
                $table->foreignId('verified_by')->nullable();
                $table->text('verification_notes')->nullable();
                $table->timestamps();
            });
        }

        // Create cabinets table for relation tests
        if (!Schema::hasTable('projects_cabinets')) {
            Schema::create('projects_cabinets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id');
                $table->string('cabinet_label')->nullable();
                $table->decimal('width', 8, 2)->nullable();
                $table->decimal('height', 8, 2)->nullable();
                $table->decimal('depth', 8, 2)->nullable();
                $table->timestamps();
            });
        }
    }

    public function test_verify_milestone_returns_correct_structure(): void
    {
        $project = Project::create([
            'name' => 'Test Project',
            'project_number' => 'TEST-001',
        ]);

        $milestone = Milestone::create([
            'name' => 'Test Milestone',
            'production_stage' => 'design',
            'project_id' => $project->id,
        ]);

        MilestoneRequirement::create([
            'milestone_id' => $milestone->id,
            'name' => 'Test Requirement',
            'requirement_type' => 'checklist_item',
            'is_required' => true,
        ]);

        $verifier = new MilestoneRequirementVerifier();
        $result = $verifier->verifyMilestone($milestone);

        $this->assertArrayHasKey('can_complete', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('verified', $result);
        $this->assertArrayHasKey('pending', $result);
        $this->assertArrayHasKey('requirements', $result);
        $this->assertEquals(1, $result['total']);
    }

    public function test_milestone_can_complete_when_all_required_verified(): void
    {
        $project = Project::create([
            'name' => 'Test Project',
            'project_number' => 'TEST-002',
        ]);

        $milestone = Milestone::create([
            'name' => 'Test Milestone',
            'production_stage' => 'design',
            'project_id' => $project->id,
        ]);

        // Create a verified requirement
        MilestoneRequirement::create([
            'milestone_id' => $milestone->id,
            'name' => 'Verified Requirement',
            'requirement_type' => 'checklist_item',
            'is_required' => true,
            'is_verified' => true,
        ]);

        $verifier = new MilestoneRequirementVerifier();
        $result = $verifier->verifyMilestone($milestone);

        $this->assertTrue($result['can_complete']);
        $this->assertEquals(1, $result['verified']);
    }

    public function test_milestone_cannot_complete_with_unverified_required(): void
    {
        $project = Project::create([
            'name' => 'Test Project',
            'project_number' => 'TEST-003',
        ]);

        $milestone = Milestone::create([
            'name' => 'Test Milestone',
            'production_stage' => 'design',
            'project_id' => $project->id,
        ]);

        // Create an unverified required requirement
        MilestoneRequirement::create([
            'milestone_id' => $milestone->id,
            'name' => 'Unverified Requirement',
            'requirement_type' => 'checklist_item',
            'is_required' => true,
            'is_verified' => false,
        ]);

        $verifier = new MilestoneRequirementVerifier();
        $result = $verifier->verifyMilestone($milestone);

        $this->assertFalse($result['can_complete']);
        $this->assertEquals(0, $result['verified']);
        $this->assertCount(1, $result['pending']);
    }

    public function test_optional_requirements_dont_block_completion(): void
    {
        $project = Project::create([
            'name' => 'Test Project',
            'project_number' => 'TEST-004',
        ]);

        $milestone = Milestone::create([
            'name' => 'Test Milestone',
            'production_stage' => 'design',
            'project_id' => $project->id,
        ]);

        // Create an unverified optional requirement
        MilestoneRequirement::create([
            'milestone_id' => $milestone->id,
            'name' => 'Optional Requirement',
            'requirement_type' => 'checklist_item',
            'is_required' => false,
            'is_verified' => false,
        ]);

        $verifier = new MilestoneRequirementVerifier();
        $result = $verifier->verifyMilestone($milestone);

        $this->assertTrue($result['can_complete']);
    }

    public function test_field_check_auto_verifies_when_field_has_value(): void
    {
        $project = Project::create([
            'name' => 'Test Project',
            'project_number' => 'TEST-005',
            'design_approved_at' => now(),
        ]);

        $milestone = Milestone::create([
            'name' => 'Test Milestone',
            'production_stage' => 'design',
            'project_id' => $project->id,
        ]);

        $requirement = MilestoneRequirement::create([
            'milestone_id' => $milestone->id,
            'name' => 'Design Approved',
            'requirement_type' => 'field_check',
            'config' => ['field' => 'design_approved_at'],
            'is_required' => true,
            'is_verified' => false,
        ]);

        $verifier = new MilestoneRequirementVerifier();
        $result = $verifier->verifyMilestone($milestone);

        // Should auto-verify and count as verified
        $this->assertEquals(1, $result['verified']);
        $this->assertTrue($result['can_complete']);

        // Requirement should be updated
        $requirement->refresh();
        $this->assertTrue($requirement->is_verified);
        $this->assertStringContainsString('Auto-verified', $requirement->verification_notes);
    }

    public function test_relation_exists_passes_with_related_records(): void
    {
        $project = Project::create([
            'name' => 'Test Project',
            'project_number' => 'TEST-006',
        ]);

        // Create cabinets for the project
        \DB::table('projects_cabinets')->insert([
            ['project_id' => $project->id, 'cabinet_label' => 'A1', 'width' => 24, 'height' => 30, 'depth' => 12],
            ['project_id' => $project->id, 'cabinet_label' => 'A2', 'width' => 24, 'height' => 30, 'depth' => 12],
        ]);

        $milestone = Milestone::create([
            'name' => 'Test Milestone',
            'production_stage' => 'design',
            'project_id' => $project->id,
        ]);

        MilestoneRequirement::create([
            'milestone_id' => $milestone->id,
            'name' => 'Cabinets Created',
            'requirement_type' => 'relation_exists',
            'config' => ['relation' => 'cabinets', 'min_count' => 1],
            'is_required' => true,
            'is_verified' => false,
        ]);

        $verifier = new MilestoneRequirementVerifier();
        $result = $verifier->verifyMilestone($milestone);

        $this->assertTrue($result['can_complete']);
        $this->assertEquals(1, $result['verified']);
    }

    public function test_verify_project_milestones_returns_summary(): void
    {
        $project = Project::create([
            'name' => 'Test Project',
            'project_number' => 'TEST-007',
        ]);

        // Create milestones for different stages
        Milestone::create([
            'name' => 'Discovery Milestone',
            'production_stage' => 'discovery',
            'project_id' => $project->id,
        ]);

        Milestone::create([
            'name' => 'Design Milestone',
            'production_stage' => 'design',
            'project_id' => $project->id,
        ]);

        $verifier = new MilestoneRequirementVerifier();
        $summary = $verifier->verifyProjectMilestones($project);

        $this->assertArrayHasKey('total_milestones', $summary);
        $this->assertArrayHasKey('completable', $summary);
        $this->assertArrayHasKey('blocked', $summary);
        $this->assertArrayHasKey('by_stage', $summary);
        $this->assertEquals(2, $summary['total_milestones']);
    }
}
