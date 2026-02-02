<?php

namespace Webkul\Project\Tests\Unit;

use Mockery;
use PHPUnit\Framework\TestCase;
use Webkul\Project\Models\Milestone;
use Webkul\Project\Models\MilestoneRequirement;
use Webkul\Project\Models\Project;
use Webkul\Project\Services\GoogleDrive\GoogleDriveFolderService;
use Webkul\Project\Services\GoogleDrive\GoogleDriveService;
use Webkul\Project\Services\MilestoneRequirementVerifier;

class MilestoneRequirementVerifierTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function createMockRequirement(string $type, array $config, bool $isVerified = false): MilestoneRequirement
    {
        $requirement = Mockery::mock(MilestoneRequirement::class)->makePartial();
        $requirement->shouldReceive('getAttribute')->andReturnUsing(function ($key) use ($type, $config, $isVerified) {
            return match ($key) {
                'requirement_type' => $type,
                'config' => $config,
                'is_verified' => $isVerified,
                default => null,
            };
        });
        $requirement->shouldReceive('offsetExists')->andReturn(true);
        $requirement->shouldReceive('offsetGet')->andReturnUsing(function ($key) use ($type, $config, $isVerified) {
            return match ($key) {
                'requirement_type' => $type,
                'config' => $config,
                'is_verified' => $isVerified,
                default => null,
            };
        });
        return $requirement;
    }

    protected function createMockProject(array $attributes = []): Project
    {
        $project = Mockery::mock(Project::class)->makePartial();
        $project->shouldReceive('getAttribute')->andReturnUsing(function ($key) use ($attributes) {
            return $attributes[$key] ?? null;
        });
        $project->shouldReceive('offsetExists')->andReturn(true);
        $project->shouldReceive('offsetGet')->andReturnUsing(function ($key) use ($attributes) {
            return $attributes[$key] ?? null;
        });
        return $project;
    }

    public function test_check_field_check_passes_when_field_has_value(): void
    {
        $project = $this->createMockProject(['design_approved_at' => '2024-01-15']);
        $requirement = $this->createMockRequirement('field_check', ['field' => 'design_approved_at']);

        $verifier = new MilestoneRequirementVerifier();
        $result = $verifier->checkRequirement($requirement, $project);

        $this->assertTrue($result['passed']);
        $this->assertTrue($result['auto_verify']);
        $this->assertStringContainsString('has value', $result['message']);
    }

    public function test_check_field_check_fails_when_field_is_null(): void
    {
        $project = $this->createMockProject(['design_approved_at' => null]);
        $requirement = $this->createMockRequirement('field_check', ['field' => 'design_approved_at']);

        $verifier = new MilestoneRequirementVerifier();
        $result = $verifier->checkRequirement($requirement, $project);

        $this->assertFalse($result['passed']);
        $this->assertTrue($result['auto_verify']);
        $this->assertStringContainsString('not set', $result['message']);
    }

    /**
     * Note: relation_exists tests require database connection.
     * They are covered in:
     * - MilestoneVerificationIntegrationTest::test_relation_exists_passes_with_related_records
     * - test_relation_exists_handles_missing_config (tests config validation only)
     */

    public function test_check_checklist_item_requires_manual_verification(): void
    {
        $project = $this->createMockProject([]);
        $requirement = $this->createMockRequirement('checklist_item', [], false);

        $verifier = new MilestoneRequirementVerifier();
        $result = $verifier->checkRequirement($requirement, $project);

        $this->assertFalse($result['passed']);
        $this->assertFalse($result['auto_verify']);
        $this->assertStringContainsString('manual verification', $result['message']);
    }

    public function test_check_checklist_item_passes_when_manually_verified(): void
    {
        $project = $this->createMockProject([]);
        $requirement = $this->createMockRequirement('checklist_item', [], true);

        $verifier = new MilestoneRequirementVerifier();
        $result = $verifier->checkRequirement($requirement, $project);

        $this->assertTrue($result['passed']);
        $this->assertFalse($result['auto_verify']);
    }

    public function test_check_document_upload_falls_back_to_manual_without_folder_config(): void
    {
        $project = $this->createMockProject(['google_drive_root_folder_id' => null]);
        $requirement = $this->createMockRequirement('document_upload', [
            'document_type' => 'some_doc',
        ]); // No folder or extensions

        $verifier = new MilestoneRequirementVerifier();
        $result = $verifier->checkRequirement($requirement, $project);

        $this->assertFalse($result['passed']);
        $this->assertFalse($result['auto_verify']);
        $this->assertStringContainsString('manual verification', $result['message']);
    }

    public function test_check_document_upload_requires_google_drive_folder(): void
    {
        $project = $this->createMockProject(['google_drive_root_folder_id' => null]);
        $requirement = $this->createMockRequirement('document_upload', [
            'folder' => '02_Design/DWG_Imports',
            'extensions' => ['3dm', 'dwg'],
        ]);

        $verifier = new MilestoneRequirementVerifier();
        $result = $verifier->checkRequirement($requirement, $project);

        $this->assertFalse($result['passed']);
        $this->assertFalse($result['auto_verify']);
        $this->assertStringContainsString('not configured', $result['message']);
    }

    public function test_unknown_requirement_type_fails(): void
    {
        $project = $this->createMockProject([]);
        $requirement = $this->createMockRequirement('unknown_type', []);

        $verifier = new MilestoneRequirementVerifier();
        $result = $verifier->checkRequirement($requirement, $project);

        $this->assertFalse($result['passed']);
        $this->assertFalse($result['auto_verify']);
        $this->assertStringContainsString('Unknown requirement type', $result['message']);
    }

    public function test_check_approval_required_fails_when_not_verified(): void
    {
        $project = $this->createMockProject([]);
        $requirement = $this->createMockRequirement('approval_required', [], false);

        $verifier = new MilestoneRequirementVerifier();
        $result = $verifier->checkRequirement($requirement, $project);

        $this->assertFalse($result['passed']);
        $this->assertFalse($result['auto_verify']);
        $this->assertStringContainsString('Awaiting approval', $result['message']);
    }

    public function test_check_approval_required_passes_when_verified(): void
    {
        $project = $this->createMockProject([]);
        $requirement = $this->createMockRequirement('approval_required', [], true);

        $verifier = new MilestoneRequirementVerifier();
        $result = $verifier->checkRequirement($requirement, $project);

        $this->assertTrue($result['passed']);
        $this->assertFalse($result['auto_verify']);
    }

    public function test_field_check_with_empty_string_fails(): void
    {
        $project = $this->createMockProject(['description' => '']);
        $requirement = $this->createMockRequirement('field_check', ['field' => 'description']);

        $verifier = new MilestoneRequirementVerifier();
        $result = $verifier->checkRequirement($requirement, $project);

        $this->assertFalse($result['passed']);
    }

    public function test_field_check_with_false_value_fails(): void
    {
        $project = $this->createMockProject(['is_active' => false]);
        $requirement = $this->createMockRequirement('field_check', ['field' => 'is_active']);

        $verifier = new MilestoneRequirementVerifier();
        $result = $verifier->checkRequirement($requirement, $project);

        $this->assertFalse($result['passed']);
    }

    public function test_relation_exists_handles_missing_config(): void
    {
        $project = $this->createMockProject([]);
        $requirement = $this->createMockRequirement('relation_exists', []);

        $verifier = new MilestoneRequirementVerifier();
        $result = $verifier->checkRequirement($requirement, $project);

        $this->assertFalse($result['passed']);
        $this->assertStringContainsString('No relation configured', $result['message']);
    }

    public function test_field_check_handles_missing_field_config(): void
    {
        $project = $this->createMockProject([]);
        $requirement = $this->createMockRequirement('field_check', []);

        $verifier = new MilestoneRequirementVerifier();
        $result = $verifier->checkRequirement($requirement, $project);

        $this->assertFalse($result['passed']);
        $this->assertStringContainsString('No field configured', $result['message']);
    }
}
