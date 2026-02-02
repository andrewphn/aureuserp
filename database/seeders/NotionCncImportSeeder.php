<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Project\Models\CncProgram;
use Webkul\Project\Models\CncProgramPart;
use Webkul\Project\Models\Project;

/**
 * Import CNC data from Notion export
 *
 * Maps Notion "Cut File Log" entries to CNC Programs and Parts
 */
class NotionCncImportSeeder extends Seeder
{
    /**
     * Mapping of Notion job names to project name patterns
     */
    protected array $jobToProjectMapping = [
        'Warrens Landing' => 'Warrens Landing',
        'Sankaty' => 'Sankaty',
        'Hummock' => 'Hummock',
        'Polpis' => 'Polpis',
        'Gardner' => 'Gardner',
        'Fields' => 'Fields Way',
        'NCB' => 'NCB',
        'Moors' => 'Moors End',
        'Frienship' => 'Friendship', // Note: Notion has typo "Frienship"
        'Bethel' => 'Bethel',
        'Patton' => 'Patton',
        'ABF' => 'ABF',
        'MedCenter' => 'MedCenter',
        'Bob' => 'Bob Job',
        'Dickman' => 'Dickman',
        'Codfish' => 'Codfish',
        'Austin farm' => 'Austin Farm',
        'SHOP' => 'SHOP',
        '67 Surfside' => '67 Surfside',
    ];

    /**
     * Material code detection patterns
     *
     * Maps VCarve filename patterns to canonical material codes.
     * Thickness prefixes (1Medex, 3_4Medex, etc.) map to base material.
     */
    protected array $materialPatterns = [
        'MDF_RiftWO' => 'MDF_RiftWO',
        'MDF_RIFWO' => 'MDF_RiftWO',
        'RiftWOPly' => 'RiftWOPly',
        'RiftWO' => 'RiftWOPly',
        'PreFin' => 'PreFin',
        'Medex' => 'Medex',
        '1Medex' => 'Medex',
        '3_4Medex' => 'Medex',
        '3_8Medex' => 'Medex',
        '5_8Medex' => 'Medex',
        'Mel' => 'Melamine',
        '3_4Mel' => 'Melamine',
        '5_8Mel' => 'Melamine',
        'Lam' => 'Laminate',
        'WO' => 'RiftWOPly',
        'BW' => 'BW',
    ];

    /**
     * Operation type detection patterns
     */
    protected array $operationPatterns = [
        'Grooves' => 'groove',
        'Groove' => 'groove',
        'Shelf Pins' => 'shelf_pins',
        'Slide Holes' => 'slide_holes',
        'Slide Pattern' => 'slide_holes',
        'Pin Pocket' => 'pocket',
        'Pocket' => 'pocket',
        'Profile' => 'profile',
        'Drill' => 'drilling',
        'DF Holes' => 'drilling',
        'Hinge' => 'drilling',
        'Rabet' => 'profile',
    ];

    public function run(): void
    {
        // PRODUCTION GUARD - This seeder is for development/staging only
        if (app()->environment('production')) {
            $this->command->error('⛔ This seeder cannot run in production!');
            $this->command->error('   NotionCncImportSeeder is for development data only.');
            return;
        }

        $csvPath = base_path('notion_import/cnc/extracted/Private & Shared/Cut File Log 270a8c394fe480c3a826c54e90a7ba3e_all.csv');

        if (!file_exists($csvPath)) {
            $this->command->error("CNC CSV file not found: {$csvPath}");
            return;
        }

        $this->command->info('Importing CNC data from Notion...');

        // Parse CSV
        $rows = $this->parseCsv($csvPath);
        $this->command->info("Found " . count($rows) . " CNC entries");

        // Group by Job (project)
        $byJob = collect($rows)->groupBy('Job');
        $this->command->info("Found " . $byJob->count() . " unique jobs");

        $stats = [
            'programs_created' => 0,
            'parts_created' => 0,
            'parts_skipped' => 0,
            'jobs_matched' => 0,
            'jobs_unmatched' => [],
        ];

        DB::transaction(function () use ($byJob, &$stats) {
            foreach ($byJob as $jobName => $entries) {
                if (empty($jobName)) {
                    continue;
                }

                // Find matching project
                $project = $this->findProject($jobName);

                if (!$project) {
                    $stats['jobs_unmatched'][] = $jobName;
                    continue;
                }

                $stats['jobs_matched']++;
                $this->command->info("Processing: {$jobName} -> Project #{$project->id}: {$project->name}");

                // Group entries by program (material + date)
                $programs = $this->groupByProgram($entries);

                foreach ($programs as $programKey => $parts) {
                    $program = $this->createProgram($project, $programKey, $parts);
                    if ($program) {
                        $stats['programs_created']++;

                        foreach ($parts as $part) {
                            if ($this->createPart($program, $part)) {
                                $stats['parts_created']++;
                            } else {
                                $stats['parts_skipped']++;
                            }
                        }
                    }
                }
            }
        });

        $this->command->info("\nImport Complete:");
        $this->command->info("  Jobs matched: {$stats['jobs_matched']}");
        $this->command->info("  Programs created: {$stats['programs_created']}");
        $this->command->info("  Parts created: {$stats['parts_created']}");
        $this->command->info("  Parts skipped: {$stats['parts_skipped']}");

        if (!empty($stats['jobs_unmatched'])) {
            $this->command->warn("\nUnmatched jobs (no project found):");
            foreach ($stats['jobs_unmatched'] as $job) {
                $this->command->warn("  - {$job}");
            }
        }
    }

    protected function parseCsv(string $path): array
    {
        $rows = [];
        $handle = fopen($path, 'r');

        // Skip BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xef\xbb\xbf") {
            rewind($handle);
        }

        $headers = fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = array_combine($headers, $row);
        }

        fclose($handle);
        return $rows;
    }

    protected function findProject(string $jobName): ?Project
    {
        // Handle multi-job entries like "Dickman, Fields"
        $primaryJob = explode(',', $jobName)[0];
        $primaryJob = trim($primaryJob);

        // Try exact match first
        $project = Project::where('name', 'like', "%{$primaryJob}%")->first();

        if (!$project && isset($this->jobToProjectMapping[$primaryJob])) {
            $mappedName = $this->jobToProjectMapping[$primaryJob];
            $project = Project::where('name', 'like', "%{$mappedName}%")->first();
        }

        return $project;
    }

    protected function groupByProgram(iterable $entries): array
    {
        $programs = [];

        foreach ($entries as $entry) {
            $name = $entry['Name'] ?? '';
            $programKey = $this->extractProgramKey($name);

            if (!isset($programs[$programKey])) {
                $programs[$programKey] = [];
            }
            $programs[$programKey][] = $entry;
        }

        return $programs;
    }

    protected function extractProgramKey(string $name): string
    {
        // Parse names like "9.26.25_MDF_RiftWO_01_1-2-Grooves"
        // Extract date and material as program key

        // Try to extract date pattern (M.D.YY or MM.DD.YY)
        if (preg_match('/^(\d{1,2}\.\d{1,2}\.\d{2})/', $name, $matches)) {
            $datePart = $matches[1];
        } else {
            $datePart = 'unknown';
        }

        // Extract material code
        $material = $this->detectMaterial($name);

        return "{$datePart}_{$material}";
    }

    protected function detectMaterial(string $name): string
    {
        foreach ($this->materialPatterns as $pattern => $code) {
            if (stripos($name, $pattern) !== false) {
                return $code;
            }
        }
        return 'Unknown';
    }

    protected function detectOperation(string $name): ?string
    {
        foreach ($this->operationPatterns as $pattern => $type) {
            if (stripos($name, $pattern) !== false) {
                return $type;
            }
        }
        return null;
    }

    protected function extractSheetNumber(string $name): ?int
    {
        // Parse names like "9.26.25_MDF_RiftWO_01_1-2-Grooves"
        // The "01" after material is the sheet number

        if (preg_match('/_(\d{2})_/', $name, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    protected function createProgram(Project $project, string $programKey, array $parts): ?CncProgram
    {
        // Extract date and material from program key
        [$datePart, $material] = explode('_', $programKey, 2) + ['unknown', 'Unknown'];

        // Parse date
        $createdDate = null;
        if ($datePart !== 'unknown' && preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{2})$/', $datePart, $matches)) {
            $year = 2000 + (int) $matches[3];
            $month = (int) $matches[1];
            $day = (int) $matches[2];
            $createdDate = Carbon::create($year, $month, $day);
        }

        // Count unique sheets
        $sheetNumbers = collect($parts)
            ->map(fn ($p) => $this->extractSheetNumber($p['Name'] ?? ''))
            ->filter()
            ->unique();

        $programName = "{$project->name} - {$material}";
        if ($createdDate) {
            $programName .= " ({$createdDate->format('m/d/y')})";
        }

        // Check if program already exists
        $existing = CncProgram::where('project_id', $project->id)
            ->where('name', $programName)
            ->first();

        if ($existing) {
            return $existing;
        }

        // Determine status based on completion
        $allComplete = collect($parts)->every(fn ($p) => ($p['Completed'] ?? '') === 'Yes');
        $anyStarted = collect($parts)->contains(fn ($p) => !empty($p['Start Time']));

        $status = match (true) {
            $allComplete => CncProgram::STATUS_COMPLETE,
            $anyStarted => CncProgram::STATUS_IN_PROGRESS,
            default => CncProgram::STATUS_PENDING,
        };

        return CncProgram::create([
            'project_id' => $project->id,
            'name' => $programName,
            'material_code' => $this->normalizeMaterialCode($material),
            'material_type' => $material,
            'sheet_size' => '48x96',
            'sheet_count' => max(1, $sheetNumbers->count()),
            'status' => $status,
            'created_date' => $createdDate ?? now(),
            'creator_id' => 1, // System/import user
        ]);
    }

    protected function normalizeMaterialCode(string $material): string
    {
        return match (true) {
            str_contains($material, 'MDF') => 'MDF_RiftWO',
            str_contains($material, 'RiftWO') => 'RiftWOPly',
            str_contains($material, 'PreFin') => 'PreFin',
            str_contains($material, 'Medex') => 'Medex',
            str_contains($material, 'Mel') => 'Melamine',
            str_contains($material, 'Lam') => 'Laminate',
            str_contains($material, 'BW') => 'BW',
            default => 'Other',
        };
    }

    protected function createPart(CncProgram $program, array $entry): bool
    {
        $name = $entry['Name'] ?? '';

        if (empty($name)) {
            return false;
        }

        // Check if part already exists
        $existing = CncProgramPart::where('cnc_program_id', $program->id)
            ->where('file_name', $name)
            ->exists();

        if ($existing) {
            return false;
        }

        // Determine status
        $completed = ($entry['Completed'] ?? '') === 'Yes';
        $hasMultiSelect = !empty($entry['Multi-select']);

        $status = match (true) {
            $completed => CncProgramPart::STATUS_COMPLETE,
            !empty($entry['Start Time']) => CncProgramPart::STATUS_RUNNING,
            default => CncProgramPart::STATUS_PENDING,
        };

        // Determine material status
        $materialStatus = match (true) {
            str_contains($entry['Multi-select'] ?? '', 'Pending Material') => CncProgramPart::MATERIAL_PENDING,
            default => CncProgramPart::MATERIAL_READY,
        };

        // Parse times
        $startTime = $this->parseNotionTime($entry['Start Time'] ?? '');
        $finishTime = $this->parseNotionTime($entry['Finish Time'] ?? '');

        CncProgramPart::create([
            'cnc_program_id' => $program->id,
            'file_name' => $name,
            'file_path' => $entry['Reference Photo'] ?? null,
            'sheet_number' => $this->extractSheetNumber($name),
            'operation_type' => $this->detectOperation($name),
            'status' => $status,
            'material_status' => $materialStatus,
            'run_at' => $startTime,
            'completed_at' => $completed ? ($finishTime ?? $startTime) : null,
            'notes' => $entry['Multi-select'] ?? null,
        ]);

        return true;
    }

    protected function parseNotionTime(?string $time): ?Carbon
    {
        if (empty($time)) {
            return null;
        }

        // Notion format: "Oct 6 8:22 AM (EDT)" or "September 30, 2025 10:23 AM"
        try {
            // Remove timezone indicator
            $time = preg_replace('/\s*\([A-Z]+\)$/', '', $time);

            return Carbon::parse($time);
        } catch (\Exception $e) {
            return null;
        }
    }
}
