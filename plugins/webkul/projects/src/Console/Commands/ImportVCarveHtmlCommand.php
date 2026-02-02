<?php

namespace Webkul\Project\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Webkul\Project\Models\CncProgram;
use Webkul\Project\Models\CncProgramPart;
use Webkul\Project\Models\Project;
use Webkul\Project\Services\GoogleDrive\GoogleDriveAuthService;
use Webkul\Project\Services\GoogleDrive\GoogleDriveFolderService;
use Webkul\Project\Services\VCarveParserService;

/**
 * Import VCarve HTML Reference Sheets from Google Drive
 *
 * Scans project CNC folders for HTML files, downloads and parses them
 * using VCarveParserService, and stores the extracted metadata for
 * capacity analysis and board feet calculations.
 *
 * Usage:
 *   php artisan cnc:import-vcarve-html --project=123
 *   php artisan cnc:import-vcarve-html --all
 *   php artisan cnc:import-vcarve-html --dry-run
 */
class ImportVCarveHtmlCommand extends Command
{
    protected $signature = 'cnc:import-vcarve-html
        {--project= : Import for a specific project ID}
        {--all : Import for all projects with Google Drive folders}
        {--dry-run : Show what would be imported without making changes}
        {--force : Re-import even if metadata already exists}
        {--limit=100 : Limit number of files to process per project}';

    protected $description = 'Import and parse VCarve HTML reference sheets from Google Drive for CNC capacity analysis';

    protected GoogleDriveAuthService $authService;
    protected GoogleDriveFolderService $folderService;
    protected VCarveParserService $parserService;

    protected int $filesProcessed = 0;
    protected int $partsUpdated = 0;
    protected int $partsCreated = 0;
    protected int $errors = 0;

    public function __construct(
        GoogleDriveAuthService $authService,
        GoogleDriveFolderService $folderService,
        VCarveParserService $parserService
    ) {
        parent::__construct();
        $this->authService = $authService;
        $this->folderService = $folderService;
        $this->parserService = $parserService;
    }

    public function handle(): int
    {
        $projectId = $this->option('project');
        $all = $this->option('all');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $limit = (int) $this->option('limit');

        if (!$projectId && !$all) {
            $this->error('Please specify --project=ID or --all');
            return self::FAILURE;
        }

        if (!$this->authService->isConfigured()) {
            $this->error('Google Drive integration is not configured.');
            return self::FAILURE;
        }

        if ($dryRun) {
            $this->warn('DRY RUN - No changes will be made');
        }

        $this->info('Starting VCarve HTML import...');
        $startTime = microtime(true);

        if ($projectId) {
            $this->importForProject((int) $projectId, $dryRun, $force, $limit);
        } else {
            $this->importForAllProjects($dryRun, $force, $limit);
        }

        $elapsed = round(microtime(true) - $startTime, 2);

        $this->newLine();
        $this->info('Import complete!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Files Processed', $this->filesProcessed],
                ['Parts Updated', $this->partsUpdated],
                ['Parts Created', $this->partsCreated],
                ['Errors', $this->errors],
            ]
        );
        $this->info("Time elapsed: {$elapsed}s");

        return self::SUCCESS;
    }

    protected function importForProject(int $projectId, bool $dryRun, bool $force, int $limit): void
    {
        $project = Project::find($projectId);

        if (!$project) {
            $this->error("Project #{$projectId} not found");
            return;
        }

        if (!$project->google_drive_root_folder_id) {
            $this->warn("Project #{$projectId} ({$project->name}) has no Google Drive folder");
            return;
        }

        $this->info("Processing project: {$project->name} (#{$projectId})");

        // Navigate to the CNC folder
        $cncFolderId = $this->folderService->navigateToSubfolder(
            $project->google_drive_root_folder_id,
            '04_Production/CNC'
        );

        if (!$cncFolderId) {
            $this->warn("  No CNC folder found in project");
            return;
        }

        // Find all HTML files recursively
        $htmlFiles = $this->folderService->findFilesByExtension($cncFolderId, ['html', 'htm'], true);

        if (empty($htmlFiles)) {
            $this->warn("  No HTML files found in CNC folder");
            return;
        }

        $this->info("  Found " . count($htmlFiles) . " HTML files");

        $processed = 0;
        foreach ($htmlFiles as $file) {
            if ($processed >= $limit) {
                $this->warn("  Reached limit of {$limit} files");
                break;
            }

            $this->processHtmlFile($project, $file, $dryRun, $force);
            $processed++;
        }
    }

    protected function importForAllProjects(bool $dryRun, bool $force, int $limit): void
    {
        $projects = Project::whereNotNull('google_drive_root_folder_id')
            ->select(['id', 'name', 'project_number', 'google_drive_root_folder_id'])
            ->orderBy('id', 'desc')
            ->get();

        $this->info("Found {$projects->count()} projects with Google Drive folders");

        $bar = $this->output->createProgressBar($projects->count());
        $bar->start();

        foreach ($projects as $project) {
            $this->importForProject($project->id, $dryRun, $force, $limit);
            $bar->advance();
        }

        $bar->finish();
    }

    protected function processHtmlFile(Project $project, array $file, bool $dryRun, bool $force): void
    {
        $fileName = pathinfo($file['name'], PATHINFO_FILENAME);
        $this->filesProcessed++;

        // Check if we already have metadata for this file
        $existingPart = CncProgramPart::whereHas('cncProgram', function ($q) use ($project) {
            $q->where('project_id', $project->id);
        })
            ->where('vcarve_html_drive_id', $file['id'])
            ->first();

        if ($existingPart && !empty($existingPart->vcarve_metadata) && !$force) {
            $this->line("    Skipping {$fileName} - already has metadata");
            return;
        }

        // Download the HTML content
        $htmlContent = $this->downloadFileContent($file['id']);

        if (!$htmlContent) {
            $this->error("    Failed to download {$fileName}");
            $this->errors++;
            return;
        }

        // Parse the VCarve HTML
        $parsedData = $this->parserService->parse($htmlContent);

        if (empty($parsedData['material']['width']) && empty($parsedData['material']['height'])) {
            $this->warn("    Could not extract material dimensions from {$fileName}");
        }

        if ($dryRun) {
            $this->displayParsedData($fileName, $parsedData);
            return;
        }

        // Find or create the CNC program part
        if ($existingPart) {
            $existingPart->update([
                'vcarve_metadata' => $parsedData,
            ]);
            $this->partsUpdated++;
            $this->line("    Updated: {$fileName}");
        } else {
            // Try to find matching program or create one
            $program = $this->findOrCreateProgram($project, $parsedData, $fileName);

            $part = CncProgramPart::create([
                'cnc_program_id' => $program->id,
                'file_name' => $fileName,
                'vcarve_html_drive_id' => $file['id'],
                'vcarve_html_drive_url' => $file['webViewLink'] ?? null,
                'vcarve_metadata' => $parsedData,
                'sheet_number' => $this->extractSheetNumber($fileName),
                'status' => CncProgramPart::STATUS_COMPLETE,
                'completed_at' => $file['modifiedTime'] ? now()->parse($file['modifiedTime']) : now(),
            ]);

            $this->partsCreated++;
            $this->line("    Created: {$fileName} (Part #{$part->id})");
        }
    }

    protected function downloadFileContent(string $fileId): ?string
    {
        try {
            $driveService = $this->authService->getDriveService();
            if (!$driveService) {
                return null;
            }

            $response = $driveService->files->get($fileId, [
                'alt' => 'media',
            ]);

            return $response->getBody()->getContents();
        } catch (\Exception $e) {
            Log::error('Failed to download VCarve HTML', [
                'file_id' => $fileId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    protected function findOrCreateProgram(Project $project, array $parsedData, string $fileName): CncProgram
    {
        // Extract material code from filename or job title
        $materialCode = $this->extractMaterialCode($fileName, $parsedData['job_title'] ?? null);

        // Try to find existing program by name pattern
        $programName = $this->extractProgramName($fileName, $parsedData);

        $program = CncProgram::where('project_id', $project->id)
            ->where('name', $programName)
            ->first();

        if (!$program) {
            $program = CncProgram::create([
                'project_id' => $project->id,
                'name' => $programName,
                'material_code' => $materialCode,
                'material_type' => $this->getMaterialType($materialCode),
                'sheet_size' => $this->getSheetSizeFromParsed($parsedData),
                'status' => CncProgram::STATUS_COMPLETE,
                'creator_id' => $project->creator_id ?? 1,
                'created_date' => now(),
                'description' => "Auto-imported from VCarve HTML",
            ]);
        }

        return $program;
    }

    protected function extractMaterialCode(?string $fileName, ?string $jobTitle): ?string
    {
        $text = ($fileName ?? '') . ' ' . ($jobTitle ?? '');

        $patterns = [
            'PreFin' => ['PreFin', 'Prefin', 'Pre-Fin'],
            'RiftWOPly' => ['RiftWO', 'Rift', 'WO', 'White Oak'],
            'MDF_RiftWO' => ['MDF', 'MDF_RiftWO'],
            'Medex' => ['Medex'],
            'Melamine' => ['Mel', 'Melamine'],
            'FL' => ['FL', 'Furniture'],
            'BW' => ['BW'],
        ];

        foreach ($patterns as $code => $keywords) {
            foreach ($keywords as $keyword) {
                if (stripos($text, $keyword) !== false) {
                    return $code;
                }
            }
        }

        return null;
    }

    protected function getMaterialType(?string $code): ?string
    {
        return match ($code) {
            'FL' => 'Furniture Lumber (solid wood)',
            'PreFin' => 'Pre-finished material',
            'RiftWOPly' => 'Rift-cut White Oak Plywood',
            'MDF_RiftWO' => 'MDF with Rift White Oak',
            'Medex' => 'Medex engineered wood',
            'Melamine' => 'Melamine',
            'BW' => 'BW',
            default => null,
        };
    }

    protected function extractProgramName(string $fileName, array $parsedData): string
    {
        // Use job title if available
        if (!empty($parsedData['job_title'])) {
            return $parsedData['job_title'];
        }

        // Remove sheet number suffixes
        $name = preg_replace('/[_-]?(Sheet|S|Part|P)?[_-]?\d+$/i', '', $fileName);
        return $name ?: $fileName;
    }

    protected function extractSheetNumber(string $fileName): ?int
    {
        if (preg_match('/(?:Sheet|S)[_-]?(\d+)/i', $fileName, $matches)) {
            return (int) $matches[1];
        }
        if (preg_match('/[_-](\d+)$/', $fileName, $matches)) {
            return (int) $matches[1];
        }
        return null;
    }

    protected function getSheetSizeFromParsed(array $parsedData): string
    {
        $width = (int) ($parsedData['material']['width'] ?? 48);
        $height = (int) ($parsedData['material']['height'] ?? 96);

        return "{$width}x{$height}";
    }

    protected function displayParsedData(string $fileName, array $parsedData): void
    {
        $material = $parsedData['material'] ?? [];
        $summary = $parsedData['toolpaths_summary'] ?? [];

        $this->line("    {$fileName}:");
        $this->line("      Material: {$material['width']}\" x {$material['height']}\" x {$material['thickness']}\"");

        if (!empty($summary['time_estimate'])) {
            $this->line("      Time Estimate: {$summary['time_estimate']}");
        }

        if (!empty($summary['toolpath_count'])) {
            $this->line("      Toolpaths: {$summary['toolpath_count']}");
        }
    }
}
