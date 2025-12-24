<?php

namespace Webkul\Project\Services\GoogleDrive;

use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Webkul\Project\Models\Project;
use Exception;

/**
 * Google Drive Folder Service
 *
 * Handles folder creation and management for project documents.
 */
class GoogleDriveFolderService
{
    protected ?Drive $driveService;
    protected GoogleDriveAuthService $authService;

    /**
     * Standard folder structure for TCS projects
     * Based on Google Drive template: Folders Template/
     */
    public const FOLDER_STRUCTURE = [
        '01_Discovery',
        '02_Design',
        '03_Sourcing',
        '04_Production',
        '05_Delivery',
    ];

    /**
     * Top-level organization folders
     */
    public const ACTIVE_FOLDER_NAME = 'Active';
    public const ARCHIVED_FOLDER_NAME = 'Archived';

    /**
     * Cache keys for folder IDs
     */
    private const CACHE_PREFIX = 'google_drive_folder_';
    private const CACHE_TTL = 86400; // 24 hours

    public function __construct(GoogleDriveAuthService $authService)
    {
        $this->authService = $authService;
        $this->driveService = $authService->getDriveService();
    }

    /**
     * Get or create the Active folder ID
     */
    public function getActiveFolderId(): string
    {
        return $this->getOrCreateOrganizationFolder(self::ACTIVE_FOLDER_NAME);
    }

    /**
     * Get or create the Archived folder ID
     */
    public function getArchivedFolderId(): string
    {
        return $this->getOrCreateOrganizationFolder(self::ARCHIVED_FOLDER_NAME);
    }

    /**
     * Get or create an organization folder (Active/Archived)
     */
    protected function getOrCreateOrganizationFolder(string $folderName): string
    {
        $cacheKey = self::CACHE_PREFIX . strtolower($folderName) . '_folder_id';

        // Check cache first
        $folderId = Cache::get($cacheKey);
        if ($folderId && $this->folderExists($folderId)) {
            return $folderId;
        }

        $rootFolderId = $this->authService->getFolderId();
        if (!$rootFolderId) {
            throw new Exception('Google Drive root folder ID not configured');
        }

        // Search for existing folder
        $folderId = $this->findFolderByName($folderName, $rootFolderId);

        // Create if doesn't exist
        if (!$folderId) {
            $folderId = $this->createFolder($folderName, $rootFolderId);
            Log::info("Created Google Drive organization folder: {$folderName}", [
                'folder_id' => $folderId,
            ]);
        }

        // Cache the folder ID
        Cache::put($cacheKey, $folderId, self::CACHE_TTL);

        return $folderId;
    }

    /**
     * Find a folder by name within a parent folder
     */
    public function findFolderByName(string $name, string $parentId): ?string
    {
        if (!$this->isReady()) {
            return null;
        }

        try {
            $query = "name = '{$name}' and '{$parentId}' in parents and mimeType = 'application/vnd.google-apps.folder' and trashed = false";
            $response = $this->driveService->files->listFiles([
                'q' => $query,
                'fields' => 'files(id)',
                'pageSize' => 1,
            ]);

            $files = $response->getFiles();
            return count($files) > 0 ? $files[0]->getId() : null;
        } catch (Exception $e) {
            Log::warning('Failed to find folder by name', [
                'name' => $name,
                'parent_id' => $parentId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Check if service is ready
     */
    public function isReady(): bool
    {
        return $this->driveService !== null;
    }

    /**
     * Create complete project folder structure in Google Drive
     * Projects are created inside the "Active" folder
     *
     * @param Project $project The project to create folders for
     * @return array Array with root_folder_id, folder_url, and folders
     * @throws Exception
     */
    public function createProjectFolderStructure(Project $project): array
    {
        if (!$this->isReady()) {
            throw new Exception('Google Drive service not configured');
        }

        try {
            // Get or create the Active folder
            $activeFolderId = $this->getActiveFolderId();

            // Create main project folder inside Active
            $projectFolderName = $this->generateProjectFolderName($project);
            $projectFolderId = $this->createFolder($projectFolderName, $activeFolderId);
            $projectFolderUrl = $this->getFolderUrl($projectFolderId);

            // Create the 5 standard subfolders
            $folders = [];
            foreach (self::FOLDER_STRUCTURE as $folderName) {
                $folderId = $this->createFolder($folderName, $projectFolderId);
                $folders[$folderName] = [
                    'id' => $folderId,
                    'name' => $folderName,
                    'url' => $this->getFolderUrl($folderId),
                ];
            }

            Log::info('Google Drive project folders created in Active', [
                'project_id' => $project->id,
                'project_number' => $project->project_number,
                'root_folder_id' => $projectFolderId,
                'parent_folder' => 'Active',
                'folders_created' => count($folders),
            ]);

            return [
                'root_folder_id' => $projectFolderId,
                'folder_url' => $projectFolderUrl,
                'folders' => $folders,
            ];
        } catch (Exception $e) {
            Log::error('Failed to create Google Drive project folders', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Move a project folder from Active to Archived
     *
     * @param Project $project The project to archive
     * @return bool Success status
     */
    public function archiveProjectFolder(Project $project): bool
    {
        if (!$this->isReady() || !$project->google_drive_root_folder_id) {
            return false;
        }

        try {
            $archivedFolderId = $this->getArchivedFolderId();
            $activeFolderId = $this->getActiveFolderId();

            // Move folder: remove from Active, add to Archived
            $this->driveService->files->update(
                $project->google_drive_root_folder_id,
                new DriveFile(),
                [
                    'addParents' => $archivedFolderId,
                    'removeParents' => $activeFolderId,
                    'fields' => 'id, parents',
                ]
            );

            Log::info('Project folder moved to Archived', [
                'project_id' => $project->id,
                'project_number' => $project->project_number,
                'folder_id' => $project->google_drive_root_folder_id,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to archive project folder', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Move a project folder from Archived back to Active
     *
     * @param Project $project The project to reactivate
     * @return bool Success status
     */
    public function reactivateProjectFolder(Project $project): bool
    {
        if (!$this->isReady() || !$project->google_drive_root_folder_id) {
            return false;
        }

        try {
            $archivedFolderId = $this->getArchivedFolderId();
            $activeFolderId = $this->getActiveFolderId();

            // Move folder: remove from Archived, add to Active
            $this->driveService->files->update(
                $project->google_drive_root_folder_id,
                new DriveFile(),
                [
                    'addParents' => $activeFolderId,
                    'removeParents' => $archivedFolderId,
                    'fields' => 'id, parents',
                ]
            );

            Log::info('Project folder moved back to Active', [
                'project_id' => $project->id,
                'project_number' => $project->project_number,
                'folder_id' => $project->google_drive_root_folder_id,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to reactivate project folder', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Generate project folder name
     */
    protected function generateProjectFolderName(Project $project): string
    {
        // Use project number if available, otherwise use name
        if ($project->project_number) {
            return $project->project_number;
        }

        // Fallback to name with sanitization
        $name = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $project->name ?? 'Project');
        return substr($name, 0, 100);
    }

    /**
     * Create a single folder in Google Drive
     *
     * @param string $name Folder name
     * @param string $parentId Parent folder ID
     * @return string Created folder ID
     */
    public function createFolder(string $name, string $parentId): string
    {
        $fileMetadata = new DriveFile([
            'name' => $name,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$parentId],
        ]);

        $folder = $this->driveService->files->create($fileMetadata, [
            'fields' => 'id,name,parents,webViewLink',
        ]);

        return $folder->getId();
    }

    /**
     * Get folder URL for direct access
     */
    public function getFolderUrl(string $folderId): string
    {
        return "https://drive.google.com/drive/folders/{$folderId}";
    }

    /**
     * Check if a folder exists
     */
    public function folderExists(string $folderId): bool
    {
        try {
            $this->driveService->files->get($folderId, ['fields' => 'id']);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get folder info
     */
    public function getFolderInfo(string $folderId): ?array
    {
        try {
            $file = $this->driveService->files->get($folderId, [
                'fields' => 'id,name,parents,webViewLink,createdTime,modifiedTime',
            ]);

            return [
                'id' => $file->getId(),
                'name' => $file->getName(),
                'parents' => $file->getParents(),
                'url' => $file->getWebViewLink(),
                'created_at' => $file->getCreatedTime(),
                'updated_at' => $file->getModifiedTime(),
            ];
        } catch (Exception $e) {
            Log::warning('Failed to get folder info', [
                'folder_id' => $folderId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * List files in a folder
     */
    public function listFiles(string $folderId, int $limit = 100): array
    {
        try {
            $response = $this->driveService->files->listFiles([
                'q' => "'{$folderId}' in parents and trashed=false",
                'fields' => 'files(id,name,mimeType,size,webViewLink,thumbnailLink,createdTime,modifiedTime)',
                'pageSize' => $limit,
                'orderBy' => 'modifiedTime desc',
            ]);

            $files = [];
            foreach ($response->getFiles() as $file) {
                $files[] = [
                    'id' => $file->getId(),
                    'name' => $file->getName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'url' => $file->getWebViewLink(),
                    'thumbnail' => $file->getThumbnailLink(),
                    'created_at' => $file->getCreatedTime(),
                    'updated_at' => $file->getModifiedTime(),
                    'is_folder' => $file->getMimeType() === 'application/vnd.google-apps.folder',
                ];
            }

            return $files;
        } catch (Exception $e) {
            Log::error('Failed to list files', [
                'folder_id' => $folderId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Delete a folder (move to trash)
     */
    public function deleteFolder(string $folderId): bool
    {
        try {
            $this->driveService->files->delete($folderId);
            return true;
        } catch (Exception $e) {
            Log::error('Failed to delete folder', [
                'folder_id' => $folderId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
