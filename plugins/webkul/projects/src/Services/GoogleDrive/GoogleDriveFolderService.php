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
     * Complete folder structure for TCS projects
     * Based on Google Drive template: docs/sample/Folders Template/
     * Nested arrays represent subfolders
     */
    public const FOLDER_STRUCTURE = [
        '01_Discovery' => [
            'Architecturals',
            'Inspo Pics',
            'Proposal',
        ],
        '02_Design' => [
            'Change Orders',
            'DWG_Imports',
            'Submittals',
        ],
        '03_Sourcing' => [
            'BOM',
            'Invoices',
        ],
        '04_Production' => [
            'CNC' => [
                'Reference Photos',
                'ToolPaths',
                'VCarve Files',
            ],
            'Job Cards' => [
                'Build_PDFs',
            ],
            'Sticker Template',
        ],
        '05_Delivery' => [
            'BOL',
        ],
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

            // Create the complete nested folder structure
            $folders = [];
            $totalFoldersCreated = $this->createNestedFolders(self::FOLDER_STRUCTURE, $projectFolderId, $folders);

            Log::info('Google Drive project folders created in Active', [
                'project_id' => $project->id,
                'project_number' => $project->project_number,
                'root_folder_id' => $projectFolderId,
                'parent_folder' => 'Active',
                'folders_created' => $totalFoldersCreated,
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
     * Recursively create nested folder structure
     *
     * @param array $structure The folder structure (can be nested)
     * @param string $parentId The parent folder ID
     * @param array &$folders Reference to store created folder info
     * @param string $prefix Path prefix for nested folders
     * @return int Total number of folders created
     */
    protected function createNestedFolders(array $structure, string $parentId, array &$folders, string $prefix = ''): int
    {
        $count = 0;

        foreach ($structure as $key => $value) {
            // Determine folder name and subfolders
            if (is_array($value)) {
                // Key is folder name, value is array of subfolders
                $folderName = $key;
                $subfolders = $value;
            } else {
                // Value is just a folder name (no subfolders)
                $folderName = $value;
                $subfolders = [];
            }

            // Create this folder
            $folderId = $this->createFolder($folderName, $parentId);
            $folderPath = $prefix ? "{$prefix}/{$folderName}" : $folderName;

            $folders[$folderPath] = [
                'id' => $folderId,
                'name' => $folderName,
                'url' => $this->getFolderUrl($folderId),
            ];
            $count++;

            // Recursively create subfolders if any
            if (!empty($subfolders)) {
                // Check if subfolders is associative (has nested structure) or sequential
                $isAssociative = array_keys($subfolders) !== range(0, count($subfolders) - 1);

                if ($isAssociative) {
                    // Nested structure with more subfolders
                    $count += $this->createNestedFolders($subfolders, $folderId, $folders, $folderPath);
                } else {
                    // Simple array of subfolder names
                    foreach ($subfolders as $subfolderName) {
                        if (is_array($subfolderName)) {
                            // It's actually a nested structure
                            $count += $this->createNestedFolders([$subfolderName], $folderId, $folders, $folderPath);
                        } else {
                            $subFolderId = $this->createFolder($subfolderName, $folderId);
                            $subFolderPath = "{$folderPath}/{$subfolderName}";

                            $folders[$subFolderPath] = [
                                'id' => $subFolderId,
                                'name' => $subfolderName,
                                'url' => $this->getFolderUrl($subFolderId),
                            ];
                            $count++;
                        }
                    }
                }
            }
        }

        return $count;
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

    /**
     * Rename a folder
     *
     * @param string $folderId The folder ID to rename
     * @param string $newName The new folder name
     * @return bool Success status
     */
    public function renameFolder(string $folderId, string $newName): bool
    {
        if (!$this->isReady()) {
            return false;
        }

        try {
            $fileMetadata = new DriveFile([
                'name' => $newName,
            ]);

            $this->driveService->files->update($folderId, $fileMetadata, [
                'fields' => 'id,name',
            ]);

            Log::info('Google Drive folder renamed', [
                'folder_id' => $folderId,
                'new_name' => $newName,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to rename folder', [
                'folder_id' => $folderId,
                'new_name' => $newName,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Trash a folder (soft delete - moves to Google Drive trash)
     *
     * @param string $folderId The folder ID to trash
     * @return bool Success status
     */
    public function trashFolder(string $folderId): bool
    {
        if (!$this->isReady()) {
            return false;
        }

        try {
            $fileMetadata = new DriveFile([
                'trashed' => true,
            ]);

            $this->driveService->files->update($folderId, $fileMetadata, [
                'fields' => 'id,trashed',
            ]);

            Log::info('Google Drive folder moved to trash', [
                'folder_id' => $folderId,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to trash folder', [
                'folder_id' => $folderId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Restore a folder from trash
     *
     * @param string $folderId The folder ID to restore
     * @return bool Success status
     */
    public function restoreFolder(string $folderId): bool
    {
        if (!$this->isReady()) {
            return false;
        }

        try {
            $fileMetadata = new DriveFile([
                'trashed' => false,
            ]);

            $this->driveService->files->update($folderId, $fileMetadata, [
                'fields' => 'id,trashed',
            ]);

            Log::info('Google Drive folder restored from trash', [
                'folder_id' => $folderId,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to restore folder from trash', [
                'folder_id' => $folderId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Navigate to a subfolder path from a root folder.
     *
     * @param string $rootFolderId The root folder to start from
     * @param string $path Path like "02_Design/DWG_Imports"
     * @return string|null The subfolder ID or null if not found
     */
    public function navigateToSubfolder(string $rootFolderId, string $path): ?string
    {
        if (empty($path)) {
            return $rootFolderId;
        }

        if (!$this->isReady()) {
            return null;
        }

        $parts = array_filter(explode('/', $path), fn($p) => !empty(trim($p)));
        $currentFolderId = $rootFolderId;

        foreach ($parts as $folderName) {
            $folderName = trim($folderName);
            $nextFolderId = $this->findFolderByName($folderName, $currentFolderId);

            if (!$nextFolderId) {
                Log::debug('Subfolder not found in path', [
                    'path' => $path,
                    'missing_folder' => $folderName,
                    'parent_id' => $currentFolderId,
                ]);
                return null;
            }

            $currentFolderId = $nextFolderId;
        }

        return $currentFolderId;
    }

    /**
     * Find files by extension in a folder.
     *
     * @param string $folderId The folder to search in
     * @param string|array $extensions File extension(s) to find (without dot)
     * @param bool $recursive Whether to search subfolders
     * @return array Array of file info arrays
     */
    public function findFilesByExtension(string $folderId, string|array $extensions, bool $recursive = false): array
    {
        if (!$this->isReady()) {
            return [];
        }

        $extensions = is_array($extensions) ? $extensions : [$extensions];
        $files = [];

        try {
            // Build query to get all files in folder
            $query = "'{$folderId}' in parents and trashed = false";

            $response = $this->driveService->files->listFiles([
                'q' => $query,
                'fields' => 'files(id, name, mimeType, size, webViewLink, thumbnailLink, createdTime, modifiedTime)',
                'pageSize' => 1000,
            ]);

            foreach ($response->getFiles() as $file) {
                $fileName = $file->getName();
                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                // Check if extension matches
                if (in_array($fileExt, array_map('strtolower', $extensions))) {
                    $files[] = [
                        'id' => $file->getId(),
                        'name' => $fileName,
                        'mimeType' => $file->getMimeType(),
                        'size' => $file->getSize(),
                        'webViewLink' => $file->getWebViewLink(),
                        'thumbnailLink' => $file->getThumbnailLink(),
                        'createdTime' => $file->getCreatedTime(),
                        'modifiedTime' => $file->getModifiedTime(),
                    ];
                }

                // Recursively search subfolders if needed
                if ($recursive && $file->getMimeType() === 'application/vnd.google-apps.folder') {
                    $subFiles = $this->findFilesByExtension($file->getId(), $extensions, true);
                    $files = array_merge($files, $subFiles);
                }
            }
        } catch (Exception $e) {
            Log::error('Failed to find files by extension', [
                'folder_id' => $folderId,
                'extensions' => $extensions,
                'error' => $e->getMessage(),
            ]);
        }

        return $files;
    }

    /**
     * Check if a project has design files in a specific folder.
     *
     * @param string $projectRootFolderId The project's root folder ID
     * @param string $folderPath Path to check (e.g., "02_Design/DWG_Imports")
     * @param string|array $extensions Extensions to look for
     * @return array Result with 'exists', 'count', 'files', and 'folder_found' keys
     */
    public function checkProjectHasDesignFiles(
        string $projectRootFolderId,
        string $folderPath,
        string|array $extensions
    ): array {
        $result = [
            'exists' => false,
            'count' => 0,
            'files' => [],
            'folder_found' => false,
        ];

        // Navigate to the target folder
        $targetFolderId = $this->navigateToSubfolder($projectRootFolderId, $folderPath);

        if (!$targetFolderId) {
            return $result;
        }

        $result['folder_found'] = true;

        // Find files with matching extensions
        $files = $this->findFilesByExtension($targetFolderId, $extensions);

        $result['files'] = $files;
        $result['count'] = count($files);
        $result['exists'] = $result['count'] > 0;

        return $result;
    }
}
