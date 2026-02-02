<?php

namespace Tests\Feature\GoogleDrive;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Webkul\Project\Models\Project;
use Webkul\Project\Jobs\CreateProjectDriveFoldersJob;
use Webkul\Project\Jobs\RenameProjectDriveFolderJob;
use Webkul\Project\Jobs\ArchiveProjectDriveFolderJob;
use Webkul\Project\Jobs\RestoreProjectDriveFolderJob;
use Webkul\Project\Jobs\DeleteProjectDriveFolderJob;
use Webkul\Project\Jobs\WatchProjectDriveFolderJob;
use Webkul\Project\Services\GoogleDrive\GoogleDriveService;
use Webkul\Project\Services\GoogleDrive\GoogleDriveWebhookService;

class GoogleDriveIntegrationTest extends TestCase
{
    protected ?Project $testProject = null;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        // Cleanup test project and Google Drive folder
        if ($this->testProject) {
            $this->cleanupTestProject();
        }

        parent::tearDown();
    }

    /**
     * Clean up test project and its Google Drive folder
     */
    protected function cleanupTestProject(): void
    {
        if (!$this->testProject) {
            return;
        }

        // Delete Google Drive folder if it exists
        if ($this->testProject->google_drive_root_folder_id) {
            $driveService = app(GoogleDriveService::class);
            if ($driveService->isConfigured()) {
                $driveService->folders()->deleteFolder($this->testProject->google_drive_root_folder_id);
            }
        }

        // Force delete the project
        $this->testProject->forceDelete();
        $this->testProject = null;
    }

    /**
     * Test that creating a project dispatches folder creation job
     */
    public function test_creating_project_dispatches_folder_creation_job(): void
    {
        Queue::fake();

        $this->testProject = Project::create([
            'name' => 'Test Google Drive Integration',
            'project_number' => 'TEST-GD-' . time(),
            'google_drive_enabled' => true,
            'company_id' => 1,
        ]);

        Queue::assertPushed(CreateProjectDriveFoldersJob::class, function ($job) {
            return $job->project->id === $this->testProject->id;
        });

        // Cleanup (queue is fake, no real folder created)
        $this->testProject->forceDelete();
        $this->testProject = null;
    }

    /**
     * Test that project with google_drive_enabled=false does not dispatch job
     */
    public function test_disabled_google_drive_does_not_dispatch_job(): void
    {
        Queue::fake();

        $this->testProject = Project::create([
            'name' => 'Test No Google Drive',
            'project_number' => 'TEST-NO-GD-' . time(),
            'google_drive_enabled' => false,
            'company_id' => 1,
        ]);

        Queue::assertNotPushed(CreateProjectDriveFoldersJob::class);

        // Cleanup
        $this->testProject->forceDelete();
        $this->testProject = null;
    }

    /**
     * Test that updating project number dispatches rename job
     */
    public function test_updating_project_number_dispatches_rename_job(): void
    {
        Queue::fake();

        // Create project with a fake folder ID
        $this->testProject = Project::create([
            'name' => 'Test Rename',
            'project_number' => 'TEST-RENAME-OLD-' . time(),
            'google_drive_enabled' => true,
            'google_drive_root_folder_id' => 'fake-folder-id',
            'company_id' => 1,
        ]);

        Queue::fake(); // Reset after creation

        // Update project number
        $newNumber = 'TEST-RENAME-NEW-' . time();
        $this->testProject->update(['project_number' => $newNumber]);

        Queue::assertPushed(RenameProjectDriveFolderJob::class, function ($job) use ($newNumber) {
            return $job->newName === $newNumber;
        });

        // Cleanup
        $this->testProject->forceDelete();
        $this->testProject = null;
    }

    /**
     * Test that soft deleting project dispatches archive job
     */
    public function test_soft_deleting_project_dispatches_archive_job(): void
    {
        Queue::fake();

        // Create project with a fake folder ID
        $this->testProject = Project::create([
            'name' => 'Test Archive',
            'project_number' => 'TEST-ARCHIVE-' . time(),
            'google_drive_enabled' => true,
            'google_drive_root_folder_id' => 'fake-folder-id',
            'company_id' => 1,
        ]);

        Queue::fake(); // Reset after creation

        // Soft delete
        $this->testProject->delete();

        Queue::assertPushed(ArchiveProjectDriveFolderJob::class);

        // Cleanup
        $this->testProject->forceDelete();
        $this->testProject = null;
    }

    /**
     * Test that restoring project dispatches restore job
     */
    public function test_restoring_project_dispatches_restore_job(): void
    {
        Queue::fake();

        // Create and soft delete project
        $this->testProject = Project::create([
            'name' => 'Test Restore',
            'project_number' => 'TEST-RESTORE-' . time(),
            'google_drive_enabled' => true,
            'google_drive_root_folder_id' => 'fake-folder-id',
            'company_id' => 1,
        ]);
        $this->testProject->delete();

        Queue::fake(); // Reset

        // Restore
        $this->testProject->restore();

        Queue::assertPushed(RestoreProjectDriveFolderJob::class);

        // Cleanup
        $this->testProject->forceDelete();
        $this->testProject = null;
    }

    /**
     * Test that force deleting project dispatches delete job
     */
    public function test_force_deleting_project_dispatches_delete_job(): void
    {
        Queue::fake();

        // Create project with a fake folder ID
        $project = Project::create([
            'name' => 'Test Force Delete',
            'project_number' => 'TEST-FORCE-DELETE-' . time(),
            'google_drive_enabled' => true,
            'google_drive_root_folder_id' => 'fake-folder-id',
            'company_id' => 1,
        ]);

        Queue::fake(); // Reset after creation

        $folderId = $project->google_drive_root_folder_id;

        // Force delete
        $project->forceDelete();

        Queue::assertPushed(DeleteProjectDriveFolderJob::class, function ($job) use ($folderId) {
            return $job->folderId === $folderId;
        });

        // No cleanup needed - project is already deleted
    }

    /**
     * Integration test: Full folder creation flow (requires Google Drive credentials)
     *
     * @group integration
     * @group google-drive
     */
    public function test_full_folder_creation_integration(): void
    {
        $driveService = app(GoogleDriveService::class);

        if (!$driveService->isConfigured()) {
            $this->markTestSkipped('Google Drive not configured');
        }

        // Create project
        $this->testProject = Project::create([
            'name' => 'Integration Test Project',
            'project_number' => 'TEST-INT-' . time(),
            'google_drive_enabled' => true,
            'company_id' => 1,
        ]);

        // Manually run the job (instead of queue)
        $job = new CreateProjectDriveFoldersJob($this->testProject);
        $job->handle($driveService);

        // Refresh from database
        $this->testProject->refresh();

        // Assert folder was created
        $this->assertNotNull($this->testProject->google_drive_root_folder_id);
        $this->assertNotNull($this->testProject->google_drive_folder_url);
        $this->assertNotNull($this->testProject->google_drive_synced_at);

        // Verify folder exists in Google Drive
        $this->assertTrue($driveService->projectHasFolders($this->testProject));

        // Cleanup happens in tearDown
    }

    /**
     * Integration test: Folder rename (requires Google Drive credentials)
     *
     * @group integration
     * @group google-drive
     */
    public function test_folder_rename_integration(): void
    {
        $driveService = app(GoogleDriveService::class);

        if (!$driveService->isConfigured()) {
            $this->markTestSkipped('Google Drive not configured');
        }

        // Create project with folder
        $this->testProject = Project::create([
            'name' => 'Rename Test Project',
            'project_number' => 'TEST-RENAME-INT-' . time(),
            'google_drive_enabled' => true,
            'company_id' => 1,
        ]);

        // Create folder
        $job = new CreateProjectDriveFoldersJob($this->testProject);
        $job->handle($driveService);
        $this->testProject->refresh();

        // Rename folder
        $newName = 'TEST-RENAMED-' . time();
        $result = $driveService->renameProjectFolder($this->testProject, $newName);

        $this->assertTrue($result);

        // Verify by getting folder info
        $folderInfo = $driveService->folders()->getFolderInfo($this->testProject->google_drive_root_folder_id);
        $this->assertEquals($newName, $folderInfo['name']);

        // Cleanup happens in tearDown
    }

    /**
     * Test webhook endpoint returns 200 for valid request
     */
    public function test_webhook_endpoint_returns_200(): void
    {
        $response = $this->postJson('/api/v1/google-drive/webhook', [], [
            'X-Goog-Channel-ID' => 'test-channel-id',
            'X-Goog-Resource-ID' => 'test-resource-id',
            'X-Goog-Resource-State' => 'sync',
        ]);

        $response->assertStatus(200);
    }

    /**
     * Test webhook endpoint returns 400 for missing headers
     */
    public function test_webhook_endpoint_returns_400_for_missing_headers(): void
    {
        $response = $this->postJson('/api/v1/google-drive/webhook');

        $response->assertStatus(400);
    }
}
