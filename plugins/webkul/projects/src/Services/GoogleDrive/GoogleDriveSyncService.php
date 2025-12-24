<?php

namespace Webkul\Project\Services\GoogleDrive;

use Google\Service\Drive;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Webkul\Project\Models\Project;
use Carbon\Carbon;

/**
 * Google Drive Sync Service
 *
 * Handles bidirectional sync between ERP and Google Drive,
 * detecting changes and logging them to project chatter.
 */
class GoogleDriveSyncService
{
    protected ?Drive $driveService;
    protected GoogleDriveAuthService $authService;
    protected GoogleDriveFolderService $folderService;

    private const CACHE_PREFIX = 'google_drive_files_';
    private const CACHE_TTL = 3600; // 1 hour

    public function __construct(
        GoogleDriveAuthService $authService,
        GoogleDriveFolderService $folderService
    ) {
        $this->authService = $authService;
        $this->folderService = $folderService;
        $this->driveService = $authService->getDriveService();
    }

    /**
     * Sync a project with Google Drive and log changes to chatter
     */
    public function syncProject(Project $project): array
    {
        if (!$this->authService->isConfigured()) {
            return ['success' => false, 'message' => 'Google Drive not configured'];
        }

        if (!$project->google_drive_root_folder_id) {
            return ['success' => false, 'message' => 'Project has no Google Drive folder'];
        }

        try {
            // Get current files from Drive
            $currentFiles = $this->getAllProjectFiles($project);

            // Get cached files from last sync
            $cachedFiles = $this->getCachedFiles($project);

            // Detect changes
            $changes = $this->detectChanges($cachedFiles, $currentFiles);

            // Log changes to chatter
            if (!empty($changes['added']) || !empty($changes['deleted']) || !empty($changes['modified'])) {
                $this->logChangesToChatter($project, $changes);
            }

            // Update cache with current files
            $this->cacheFiles($project, $currentFiles);

            // Update project sync timestamp
            $project->update(['google_drive_synced_at' => now()]);

            return [
                'success' => true,
                'changes' => $changes,
                'total_files' => count($currentFiles),
                'synced_at' => now()->toDateTimeString(),
            ];
        } catch (\Exception $e) {
            Log::error('Google Drive sync failed', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get all files from project's Drive folders recursively
     */
    public function getAllProjectFiles(Project $project): array
    {
        $files = [];
        $rootFolderId = $project->google_drive_root_folder_id;

        // Get files from root and all subfolders
        $this->collectFilesRecursively($rootFolderId, '', $files);

        return $files;
    }

    /**
     * Recursively collect files from a folder
     */
    protected function collectFilesRecursively(string $folderId, string $path, array &$files): void
    {
        if (!$this->driveService) {
            return;
        }

        try {
            $query = "'{$folderId}' in parents and trashed = false";
            $results = $this->driveService->files->listFiles([
                'q' => $query,
                'fields' => 'files(id, name, mimeType, modifiedTime, size, webViewLink, createdTime)',
                'pageSize' => 100,
            ]);

            foreach ($results->getFiles() as $file) {
                $filePath = $path ? "{$path}/{$file->getName()}" : $file->getName();

                $files[$file->getId()] = [
                    'id' => $file->getId(),
                    'name' => $file->getName(),
                    'path' => $filePath,
                    'mimeType' => $file->getMimeType(),
                    'modifiedTime' => $file->getModifiedTime(),
                    'createdTime' => $file->getCreatedTime(),
                    'size' => $file->getSize(),
                    'webViewLink' => $file->getWebViewLink(),
                    'isFolder' => $file->getMimeType() === 'application/vnd.google-apps.folder',
                ];

                // If it's a folder, recurse into it
                if ($file->getMimeType() === 'application/vnd.google-apps.folder') {
                    $this->collectFilesRecursively($file->getId(), $filePath, $files);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to list files in folder', [
                'folder_id' => $folderId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Detect changes between cached and current files
     */
    public function detectChanges(array $cachedFiles, array $currentFiles): array
    {
        $added = [];
        $deleted = [];
        $modified = [];

        // Find added and modified files
        foreach ($currentFiles as $fileId => $file) {
            if (!isset($cachedFiles[$fileId])) {
                // New file
                $added[] = $file;
            } elseif ($file['modifiedTime'] !== $cachedFiles[$fileId]['modifiedTime']) {
                // Modified file
                $modified[] = $file;
            }
        }

        // Find deleted files
        foreach ($cachedFiles as $fileId => $file) {
            if (!isset($currentFiles[$fileId])) {
                $deleted[] = $file;
            }
        }

        return [
            'added' => $added,
            'deleted' => $deleted,
            'modified' => $modified,
        ];
    }

    /**
     * Log changes to project chatter
     */
    public function logChangesToChatter(Project $project, array $changes): void
    {
        $messages = [];

        // Build message for added files
        if (!empty($changes['added'])) {
            $fileList = $this->formatFileList($changes['added']);
            $count = count($changes['added']);
            $messages[] = "**{$count} new file(s) added to Google Drive:**\n{$fileList}";
        }

        // Build message for deleted files
        if (!empty($changes['deleted'])) {
            $fileList = $this->formatFileList($changes['deleted']);
            $count = count($changes['deleted']);
            $messages[] = "**{$count} file(s) removed from Google Drive:**\n{$fileList}";
        }

        // Build message for modified files
        if (!empty($changes['modified'])) {
            $fileList = $this->formatFileList($changes['modified']);
            $count = count($changes['modified']);
            $messages[] = "**{$count} file(s) modified in Google Drive:**\n{$fileList}";
        }

        // Combine and post to chatter
        if (!empty($messages)) {
            $body = implode("\n\n", $messages);

            try {
                // Get current user or use project creator as fallback
                $user = null;
                try {
                    $user = filament()->auth()->user();
                } catch (\Exception $e) {
                    // Filament may not be available (e.g., in queue jobs)
                }

                // Use project creator as fallback if no authenticated user
                $creatorId = $user?->id ?? $project->creator_id ?? 1;

                $project->addMessage([
                    'type' => 'activity',
                    'subject' => 'Google Drive Sync',
                    'body' => $body,
                    'is_internal' => true,
                    'creator_id' => $creatorId,
                ]);

                Log::info('Posted Drive changes to project chatter', [
                    'project_id' => $project->id,
                    'added' => count($changes['added']),
                    'deleted' => count($changes['deleted']),
                    'modified' => count($changes['modified']),
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to post Drive changes to chatter', [
                    'project_id' => $project->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Format file list for chatter message
     */
    protected function formatFileList(array $files): string
    {
        $items = [];
        foreach ($files as $file) {
            $icon = $file['isFolder'] ? '📁' : '📄';
            $link = $file['webViewLink'] ?? '';
            $name = $file['path'] ?? $file['name'];

            if ($link) {
                $items[] = "- {$icon} [{$name}]({$link})";
            } else {
                $items[] = "- {$icon} {$name}";
            }
        }

        // Limit to 10 items to avoid huge messages
        if (count($items) > 10) {
            $shown = array_slice($items, 0, 10);
            $remaining = count($items) - 10;
            $shown[] = "- ... and {$remaining} more";
            return implode("\n", $shown);
        }

        return implode("\n", $items);
    }

    /**
     * Get cached files for a project
     */
    public function getCachedFiles(Project $project): array
    {
        $cacheKey = self::CACHE_PREFIX . $project->id;
        return Cache::get($cacheKey, []);
    }

    /**
     * Cache files for a project
     */
    public function cacheFiles(Project $project, array $files): void
    {
        $cacheKey = self::CACHE_PREFIX . $project->id;
        Cache::put($cacheKey, $files, self::CACHE_TTL);
    }

    /**
     * Clear cached files for a project
     */
    public function clearCache(Project $project): void
    {
        $cacheKey = self::CACHE_PREFIX . $project->id;
        Cache::forget($cacheKey);
    }

    /**
     * Check if sync is needed (based on last sync time)
     */
    public function needsSync(Project $project, int $thresholdMinutes = 5): bool
    {
        if (!$project->google_drive_synced_at) {
            return true;
        }

        return $project->google_drive_synced_at->diffInMinutes(now()) >= $thresholdMinutes;
    }

    /**
     * Get file statistics for a project
     */
    public function getFileStats(Project $project): array
    {
        $files = $this->getAllProjectFiles($project);

        $totalFiles = 0;
        $totalFolders = 0;
        $totalSize = 0;
        $byFolder = [];

        foreach ($files as $file) {
            if ($file['isFolder']) {
                $totalFolders++;
            } else {
                $totalFiles++;
                $totalSize += (int) ($file['size'] ?? 0);
            }

            // Count by top-level folder
            $parts = explode('/', $file['path']);
            $topFolder = $parts[0] ?? 'Root';
            if (!isset($byFolder[$topFolder])) {
                $byFolder[$topFolder] = ['files' => 0, 'folders' => 0];
            }
            if ($file['isFolder']) {
                $byFolder[$topFolder]['folders']++;
            } else {
                $byFolder[$topFolder]['files']++;
            }
        }

        return [
            'total_files' => $totalFiles,
            'total_folders' => $totalFolders,
            'total_size' => $totalSize,
            'total_size_formatted' => $this->formatBytes($totalSize),
            'by_folder' => $byFolder,
            'last_synced' => $project->google_drive_synced_at?->toDateTimeString(),
        ];
    }

    /**
     * Format bytes to human-readable string
     */
    protected function formatBytes(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = floor(log($bytes, 1024));

        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }
}
