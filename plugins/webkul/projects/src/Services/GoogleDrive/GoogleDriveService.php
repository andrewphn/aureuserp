<?php

namespace Webkul\Project\Services\GoogleDrive;

use Illuminate\Support\Facades\Log;
use Webkul\Project\Models\Project;
use Exception;

/**
 * Google Drive Service Facade
 *
 * Main service that coordinates Google Drive operations for projects.
 */
class GoogleDriveService
{
    protected GoogleDriveAuthService $authService;
    protected GoogleDriveFolderService $folderService;
    protected ?GoogleDriveSyncService $syncService = null;

    public function __construct(
        GoogleDriveAuthService $authService,
        GoogleDriveFolderService $folderService
    ) {
        $this->authService = $authService;
        $this->folderService = $folderService;
    }

    /**
     * Set the sync service (injected after construction to avoid circular dependency)
     */
    public function setSyncService(GoogleDriveSyncService $syncService): void
    {
        $this->syncService = $syncService;
    }

    /**
     * Check if Google Drive integration is configured and ready
     */
    public function isConfigured(): bool
    {
        return $this->authService->isConfigured();
    }

    /**
     * Test the Google Drive connection
     */
    public function testConnection(): array
    {
        return $this->authService->testConnection();
    }

    /**
     * Create folder structure for a project
     *
     * @param Project $project
     * @return array|null Returns folder info or null on failure
     */
    public function createProjectFolders(Project $project): ?array
    {
        if (!$this->isConfigured()) {
            Log::warning('Google Drive not configured, skipping folder creation', [
                'project_id' => $project->id,
            ]);
            return null;
        }

        try {
            $result = $this->folderService->createProjectFolderStructure($project);

            // Update project with Google Drive info
            $project->update([
                'google_drive_root_folder_id' => $result['root_folder_id'],
                'google_drive_folder_url' => $result['folder_url'],
                'google_drive_synced_at' => now(),
            ]);

            return $result;
        } catch (Exception $e) {
            Log::error('Failed to create project Google Drive folders', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get folder service for direct folder operations
     */
    public function folders(): GoogleDriveFolderService
    {
        return $this->folderService;
    }

    /**
     * Get auth service for authentication operations
     */
    public function auth(): GoogleDriveAuthService
    {
        return $this->authService;
    }

    /**
     * Get the root folder ID
     */
    public function getRootFolderId(): ?string
    {
        return $this->authService->getFolderId();
    }

    /**
     * List files in a project's Google Drive folder
     */
    public function listProjectFiles(Project $project, ?string $subfolder = null): array
    {
        if (!$project->google_drive_root_folder_id) {
            return [];
        }

        $folderId = $project->google_drive_root_folder_id;

        // If subfolder specified, find it first
        if ($subfolder) {
            $files = $this->folderService->listFiles($folderId);
            foreach ($files as $file) {
                if ($file['is_folder'] && $file['name'] === $subfolder) {
                    $folderId = $file['id'];
                    break;
                }
            }
        }

        return $this->folderService->listFiles($folderId);
    }

    /**
     * Check if project has Google Drive folders set up
     */
    public function projectHasFolders(Project $project): bool
    {
        if (!$project->google_drive_root_folder_id) {
            return false;
        }

        return $this->folderService->folderExists($project->google_drive_root_folder_id);
    }

    /**
     * Get sync service for sync operations
     */
    public function sync(): ?GoogleDriveSyncService
    {
        return $this->syncService;
    }

    /**
     * Sync project with Google Drive and log changes to chatter
     */
    public function syncProject(Project $project): array
    {
        if (!$this->syncService) {
            return ['success' => false, 'message' => 'Sync service not available'];
        }

        return $this->syncService->syncProject($project);
    }

    /**
     * Check if project needs sync
     */
    public function projectNeedsSync(Project $project, int $thresholdMinutes = 5): bool
    {
        if (!$this->syncService) {
            return false;
        }

        return $this->syncService->needsSync($project, $thresholdMinutes);
    }

    /**
     * Get file statistics for a project
     */
    public function getProjectFileStats(Project $project): array
    {
        if (!$this->syncService) {
            return [];
        }

        return $this->syncService->getFileStats($project);
    }

    /**
     * Archive a project folder (move from Active to Archived)
     */
    public function archiveProject(Project $project): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        $result = $this->folderService->archiveProjectFolder($project);

        if ($result) {
            // Log to chatter
            $this->logArchiveToChatter($project, 'archived');
        }

        return $result;
    }

    /**
     * Reactivate a project folder (move from Archived to Active)
     */
    public function reactivateProject(Project $project): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        $result = $this->folderService->reactivateProjectFolder($project);

        if ($result) {
            // Log to chatter
            $this->logArchiveToChatter($project, 'reactivated');
        }

        return $result;
    }

    /**
     * Log archive/reactivate action to chatter
     */
    protected function logArchiveToChatter(Project $project, string $action): void
    {
        try {
            $actionText = $action === 'archived'
                ? '📦 Project folder moved to **Archived** in Google Drive'
                : '📂 Project folder moved back to **Active** in Google Drive';

            $project->addMessage([
                'type' => 'activity',
                'subject' => 'Google Drive Folder Moved',
                'body' => $actionText,
                'is_internal' => true,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to log archive action to chatter', [
                'project_id' => $project->id,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
