<?php

namespace Webkul\Project\Console\Commands;

use Illuminate\Console\Command;
use Webkul\Project\Models\Project;
use Webkul\Project\Services\GoogleDrive\GoogleDriveFolderService;
use Webkul\Project\Services\GoogleDrive\GoogleDriveService;

class SyncGoogleDriveFolderStructureCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'projects:sync-drive-folders
                            {--project= : Specific project ID to sync}
                            {--dry-run : Show what would be created without creating}';

    /**
     * The console command description.
     */
    protected $description = 'Sync Google Drive folder structure for projects (creates missing subfolders)';

    public function handle(GoogleDriveService $driveService): int
    {
        if (!$driveService->isConfigured()) {
            $this->error('Google Drive is not configured.');
            return 1;
        }

        $projectId = $this->option('project');
        $dryRun = $this->option('dry-run');

        // Get projects with Google Drive folders
        $query = Project::whereNotNull('google_drive_root_folder_id')
            ->where('google_drive_root_folder_id', '!=', '');

        if ($projectId) {
            $query->where('id', $projectId);
        }

        $projects = $query->get();

        if ($projects->isEmpty()) {
            $this->info('No projects with Google Drive folders found.');
            return 0;
        }

        $this->info(sprintf(
            'Found %d project(s) with Google Drive folders.%s',
            $projects->count(),
            $dryRun ? ' (DRY RUN)' : ''
        ));

        $folderService = $driveService->folders();

        foreach ($projects as $project) {
            $this->newLine();
            $this->info("Processing: {$project->project_number} - {$project->name}");

            $result = $this->syncProjectFolders(
                $folderService,
                $project->google_drive_root_folder_id,
                GoogleDriveFolderService::FOLDER_STRUCTURE,
                $dryRun
            );

            $this->info("  Created: {$result['created']} folders, Existing: {$result['existing']} folders");
        }

        return 0;
    }

    /**
     * Sync folder structure for a project
     */
    protected function syncProjectFolders(
        GoogleDriveFolderService $folderService,
        string $parentId,
        array $structure,
        bool $dryRun,
        string $prefix = ''
    ): array {
        $result = ['created' => 0, 'existing' => 0];

        foreach ($structure as $key => $value) {
            // Determine folder name and subfolders
            if (is_array($value)) {
                $folderName = $key;
                $subfolders = $value;
            } else {
                $folderName = $value;
                $subfolders = [];
            }

            $folderPath = $prefix ? "{$prefix}/{$folderName}" : $folderName;

            // Check if folder exists
            $existingFolderId = $folderService->findFolderByName($folderName, $parentId);

            if ($existingFolderId) {
                $this->line("    [EXISTS] {$folderPath}");
                $result['existing']++;
                $folderId = $existingFolderId;
            } else {
                if ($dryRun) {
                    $this->line("    [WOULD CREATE] {$folderPath}");
                    $result['created']++;
                    continue; // Can't process subfolders in dry run without creating
                } else {
                    $folderId = $folderService->createFolder($folderName, $parentId);
                    $this->line("    [CREATED] {$folderPath}");
                    $result['created']++;
                }
            }

            // Process subfolders
            if (!empty($subfolders) && !$dryRun) {
                // Check if subfolders is associative (nested) or sequential
                $isAssociative = array_keys($subfolders) !== range(0, count($subfolders) - 1);

                if ($isAssociative) {
                    $subResult = $this->syncProjectFolders(
                        $folderService,
                        $folderId,
                        $subfolders,
                        $dryRun,
                        $folderPath
                    );
                } else {
                    // Simple array of subfolder names
                    $subResult = ['created' => 0, 'existing' => 0];
                    foreach ($subfolders as $subfolderName) {
                        if (is_array($subfolderName)) {
                            $nestedResult = $this->syncProjectFolders(
                                $folderService,
                                $folderId,
                                [$subfolderName],
                                $dryRun,
                                $folderPath
                            );
                            $subResult['created'] += $nestedResult['created'];
                            $subResult['existing'] += $nestedResult['existing'];
                        } else {
                            $subFolderPath = "{$folderPath}/{$subfolderName}";
                            $existingSubFolderId = $folderService->findFolderByName($subfolderName, $folderId);

                            if ($existingSubFolderId) {
                                $this->line("    [EXISTS] {$subFolderPath}");
                                $subResult['existing']++;
                            } else {
                                $folderService->createFolder($subfolderName, $folderId);
                                $this->line("    [CREATED] {$subFolderPath}");
                                $subResult['created']++;
                            }
                        }
                    }
                }

                $result['created'] += $subResult['created'];
                $result['existing'] += $subResult['existing'];
            }
        }

        return $result;
    }
}
