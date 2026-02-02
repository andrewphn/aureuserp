<?php

namespace Webkul\Project\Services;

use Illuminate\Support\Facades\Log;
use Webkul\Project\Models\CncMaterialProduct;
use Webkul\Project\Models\CncProgram;
use Webkul\Project\Models\CncProgramPart;
use Webkul\Project\Models\Project;

/**
 * CNC File Ingestion Service
 *
 * Automatically creates CncProgram records when VCarve files
 * are detected in Google Drive project folders.
 *
 * Supported files:
 * - .crv, .crv3d (VCarve project files)
 * - .nc, .gcode, .tap (G-code files)
 * - .html (VCarve reference sheets)
 */
class CncFileIngestionService
{
    /**
     * VCarve file extensions to detect
     */
    public const VCARVE_EXTENSIONS = ['crv', 'crv3d'];

    /**
     * G-code file extensions to detect as parts
     */
    public const GCODE_EXTENSIONS = ['nc', 'gcode', 'tap', 'ngc'];

    /**
     * Reference sheet extensions
     */
    public const REFERENCE_EXTENSIONS = ['html', 'htm', 'pdf'];

    /**
     * Image extensions for reference photos
     */
    public const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    /**
     * Path patterns to look for CNC files
     */
    public const CNC_FOLDER_PATTERNS = [
        '04_Production/CNC/VCarve Files',
        '04_Production/CNC/ToolPaths',
        '04_Production/CNC/Reference Photos',
        '04_Production/CNC',
    ];

    /**
     * Process files from a sync and ingest CNC-related files
     *
     * @param Project $project The project
     * @param array $changes Changes detected from sync (added, modified, deleted)
     * @return array Summary of ingestion results
     */
    public function processChanges(Project $project, array $changes): array
    {
        $results = [
            'programs_created' => 0,
            'programs_updated' => 0,
            'parts_created' => 0,
            'parts_updated' => 0,
            'reference_photos_linked' => 0,
            'errors' => [],
        ];

        // Process added files
        foreach ($changes['added'] ?? [] as $file) {
            try {
                $this->processFile($project, $file, 'added', $results);
            } catch (\Exception $e) {
                $results['errors'][] = "Error processing {$file['name']}: {$e->getMessage()}";
                Log::error('CNC file ingestion error', [
                    'project_id' => $project->id,
                    'file' => $file['name'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Process modified files
        foreach ($changes['modified'] ?? [] as $file) {
            try {
                $this->processFile($project, $file, 'modified', $results);
            } catch (\Exception $e) {
                $results['errors'][] = "Error processing {$file['name']}: {$e->getMessage()}";
            }
        }

        // Log deleted files (mark programs as needing review)
        foreach ($changes['deleted'] ?? [] as $file) {
            $this->handleDeletedFile($project, $file, $results);
        }

        // Log summary to chatter if there were any CNC-related changes
        if ($results['programs_created'] > 0 || $results['parts_created'] > 0) {
            $this->logIngestionToChatter($project, $results);
        }

        return $results;
    }

    /**
     * Process a single file and determine if it's CNC-related
     */
    protected function processFile(Project $project, array $file, string $changeType, array &$results): void
    {
        // Skip folders
        if ($file['isFolder'] ?? false) {
            return;
        }

        // Check if file is in a CNC folder
        if (!$this->isInCncFolder($file['path'] ?? '')) {
            return;
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $fileName = pathinfo($file['name'], PATHINFO_FILENAME);

        // Handle VCarve project files
        if (in_array($extension, self::VCARVE_EXTENSIONS)) {
            $this->processVCarveFile($project, $file, $changeType, $results);
            return;
        }

        // Handle G-code files
        if (in_array($extension, self::GCODE_EXTENSIONS)) {
            $this->processGCodeFile($project, $file, $changeType, $results);
            return;
        }

        // Handle reference sheets (HTML from VCarve)
        if (in_array($extension, self::REFERENCE_EXTENSIONS)) {
            $this->processReferenceFile($project, $file, $changeType, $results);
            return;
        }

        // Handle reference photos
        if (in_array($extension, self::IMAGE_EXTENSIONS)) {
            $this->processReferencePhoto($project, $file, $changeType, $results);
            return;
        }
    }

    /**
     * Check if file path is in a CNC folder
     */
    protected function isInCncFolder(string $path): bool
    {
        foreach (self::CNC_FOLDER_PATTERNS as $pattern) {
            if (str_starts_with($path, $pattern)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Process a VCarve project file (.crv, .crv3d)
     */
    protected function processVCarveFile(Project $project, array $file, string $changeType, array &$results): void
    {
        $fileName = pathinfo($file['name'], PATHINFO_FILENAME);

        // Try to extract material code from filename
        // Common patterns: "ProjectName_FL_Sheet1", "Material_Parts", etc.
        $materialCode = $this->extractMaterialCode($fileName);

        // Check if program already exists
        $existingProgram = CncProgram::where('project_id', $project->id)
            ->where(function ($query) use ($file, $fileName) {
                $query->where('vcarve_file', $file['id'])
                    ->orWhere('name', $fileName);
            })
            ->first();

        // Get product ID from material mapping if available
        $productId = $this->getProductIdForMaterialCode($materialCode);

        if ($existingProgram) {
            // Update existing program
            $updateData = [
                'vcarve_file' => $file['id'],
                'status' => $changeType === 'modified' ? CncProgram::STATUS_IN_PROGRESS : $existingProgram->status,
            ];

            // Update material info if not already set
            if (!$existingProgram->material_code && $materialCode) {
                $updateData['material_code'] = $materialCode;
                $updateData['material_type'] = $this->getMaterialType($materialCode);
            }

            $existingProgram->update($updateData);
            $results['programs_updated']++;

            Log::info('Updated CNC program from VCarve file', [
                'project_id' => $project->id,
                'program_id' => $existingProgram->id,
                'file' => $file['name'],
                'material_code' => $materialCode,
            ]);
        } else {
            // Create new program with product linkage
            $program = CncProgram::create([
                'project_id' => $project->id,
                'name' => $fileName,
                'vcarve_file' => $file['id'],
                'material_code' => $materialCode,
                'material_type' => $this->getMaterialType($materialCode),
                'status' => CncProgram::STATUS_PENDING,
                'creator_id' => $project->creator_id ?? 1,
                'created_date' => now(),
                'description' => "Auto-imported from Google Drive: {$file['path']}",
            ]);

            // Create material usage record if product is mapped
            if ($productId) {
                try {
                    $program->createMaterialUsage();
                } catch (\Exception $e) {
                    Log::warning('Could not create material usage for CNC program', [
                        'program_id' => $program->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $results['programs_created']++;

            Log::info('Created CNC program from VCarve file', [
                'project_id' => $project->id,
                'program_id' => $program->id,
                'file' => $file['name'],
                'material_code' => $materialCode,
                'product_id' => $productId,
            ]);
        }
    }

    /**
     * Process a G-code file (.nc, .gcode, .tap)
     */
    protected function processGCodeFile(Project $project, array $file, string $changeType, array &$results): void
    {
        $fileName = pathinfo($file['name'], PATHINFO_FILENAME);

        // Try to find parent program based on filename pattern
        // G-code files are often named like "ProgramName_Sheet1.nc"
        $programName = $this->extractProgramName($fileName);

        $program = CncProgram::where('project_id', $project->id)
            ->where('name', 'like', $programName . '%')
            ->first();

        // If no program found, create one
        if (!$program) {
            $program = CncProgram::create([
                'project_id' => $project->id,
                'name' => $programName,
                'material_code' => $this->extractMaterialCode($fileName),
                'status' => CncProgram::STATUS_PENDING,
                'creator_id' => $project->creator_id ?? 1,
                'created_date' => now(),
            ]);
            $results['programs_created']++;
        }

        // Check if part already exists
        $existingPart = CncProgramPart::where('cnc_program_id', $program->id)
            ->where(function ($query) use ($file, $fileName) {
                $query->where('nc_drive_id', $file['id'])
                    ->orWhere('file_name', $fileName);
            })
            ->first();

        if ($existingPart) {
            $existingPart->update([
                'nc_drive_id' => $file['id'],
                'nc_drive_url' => $file['webViewLink'] ?? null,
                'file_size' => $file['size'] ?? null,
            ]);
            $results['parts_updated']++;
        } else {
            // Extract sheet number from filename if present
            $sheetNumber = $this->extractSheetNumber($fileName);

            CncProgramPart::create([
                'cnc_program_id' => $program->id,
                'file_name' => $fileName,
                'file_path' => $file['path'] ?? null,
                'nc_drive_id' => $file['id'],
                'nc_drive_url' => $file['webViewLink'] ?? null,
                'sheet_number' => $sheetNumber,
                'file_size' => $file['size'] ?? null,
                'status' => CncProgramPart::STATUS_PENDING,
            ]);
            $results['parts_created']++;

            Log::info('Created CNC program part from G-code file', [
                'project_id' => $project->id,
                'program_id' => $program->id,
                'file' => $file['name'],
            ]);
        }
    }

    /**
     * Process a reference file (HTML setup sheet)
     */
    protected function processReferenceFile(Project $project, array $file, string $changeType, array &$results): void
    {
        $fileName = pathinfo($file['name'], PATHINFO_FILENAME);
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        // Only process HTML files (VCarve reference sheets)
        if (!in_array($extension, ['html', 'htm'])) {
            return;
        }

        // Try to match to existing part
        $part = CncProgramPart::whereHas('cncProgram', function ($query) use ($project) {
            $query->where('project_id', $project->id);
        })
            ->where('file_name', 'like', $fileName . '%')
            ->first();

        if ($part) {
            $part->update([
                'vcarve_html_drive_id' => $file['id'],
                'vcarve_html_drive_url' => $file['webViewLink'] ?? null,
            ]);
            $results['parts_updated']++;
        }
    }

    /**
     * Process a reference photo
     */
    protected function processReferencePhoto(Project $project, array $file, string $changeType, array &$results): void
    {
        $fileName = pathinfo($file['name'], PATHINFO_FILENAME);

        // Try to match to existing part based on filename
        $part = CncProgramPart::whereHas('cncProgram', function ($query) use ($project) {
            $query->where('project_id', $project->id);
        })
            ->where(function ($query) use ($fileName) {
                $query->where('file_name', 'like', '%' . $fileName . '%')
                    ->orWhere('part_label', 'like', '%' . $fileName . '%');
            })
            ->first();

        if ($part) {
            $part->update([
                'reference_photo_drive_id' => $file['id'],
                'reference_photo_url' => $file['webViewLink'] ?? null,
            ]);
            $results['reference_photos_linked']++;
        }
    }

    /**
     * Handle deleted files
     */
    protected function handleDeletedFile(Project $project, array $file, array &$results): void
    {
        // Check if this was a VCarve file
        $extension = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));

        if (in_array($extension, self::VCARVE_EXTENSIONS)) {
            $program = CncProgram::where('project_id', $project->id)
                ->where('vcarve_file', $file['id'])
                ->first();

            if ($program) {
                Log::warning('VCarve file deleted from Google Drive', [
                    'project_id' => $project->id,
                    'program_id' => $program->id,
                    'file' => $file['name'] ?? 'unknown',
                ]);
            }
        }

        // Check if this was a G-code file
        if (in_array($extension, self::GCODE_EXTENSIONS)) {
            $part = CncProgramPart::where('nc_drive_id', $file['id'])->first();
            if ($part) {
                Log::warning('G-code file deleted from Google Drive', [
                    'project_id' => $project->id,
                    'part_id' => $part->id,
                    'file' => $file['name'] ?? 'unknown',
                ]);
            }
        }
    }

    /**
     * Get all available material codes (from DB first, then hardcoded fallback)
     */
    protected function getAvailableMaterialCodes(): array
    {
        // Try to get from database first
        $dbMaterials = CncMaterialProduct::active()
            ->pluck('material_code')
            ->unique()
            ->toArray();

        if (!empty($dbMaterials)) {
            return $dbMaterials;
        }

        // Fallback to hardcoded codes
        return array_keys(CncProgram::getMaterialCodes());
    }

    /**
     * Extract material code from filename
     * Common patterns: "ProjectName_FL_Sheet1", "RiftWOPly_Parts", etc.
     */
    protected function extractMaterialCode(string $fileName): ?string
    {
        $materialCodes = $this->getAvailableMaterialCodes();

        // First, try exact match with database material codes
        foreach ($materialCodes as $code) {
            // Use word boundary matching to avoid partial matches
            if (preg_match('/[_\-\s]' . preg_quote($code, '/') . '[_\-\s\.]/i', "_$fileName.")) {
                return $code;
            }
        }

        // Check for common abbreviations (case-insensitive)
        // Maps shorthand in filenames to canonical material codes
        $abbreviations = [
            'PreFin' => 'PreFin',
            'Rift' => 'RiftWOPly',
            'WO' => 'RiftWOPly',
            'RiftWO' => 'RiftWOPly',
            'MDF' => 'MDF_RiftWO',
            'Medex' => 'Medex',
            'Mel' => 'Melamine',
            'Lam' => 'Laminate',
            'BW' => 'BW',
        ];

        foreach ($abbreviations as $abbr => $code) {
            if (preg_match('/[_\-\s]' . preg_quote($abbr, '/') . '[_\-\s\.]/i', "_$fileName.")) {
                return $code;
            }
        }

        return null;
    }

    /**
     * Get material type from code
     */
    protected function getMaterialType(?string $code): ?string
    {
        if (!$code) {
            return null;
        }

        // Try to get from database mapping first
        $dbMaterial = CncMaterialProduct::active()
            ->forMaterialCode($code)
            ->first();

        if ($dbMaterial) {
            return $dbMaterial->material_type;
        }

        // Fallback to hardcoded types
        $types = CncProgram::getMaterialCodes();
        return $types[$code] ?? null;
    }

    /**
     * Get the product ID for a material code (from CncMaterialProduct mapping)
     */
    protected function getProductIdForMaterialCode(?string $code): ?int
    {
        if (!$code) {
            return null;
        }

        $mapping = CncMaterialProduct::getDefaultForCode($code);
        return $mapping?->product_id;
    }

    /**
     * Extract program name from G-code filename
     */
    protected function extractProgramName(string $fileName): string
    {
        // Remove sheet/part number suffixes
        $name = preg_replace('/[_-]?(Sheet|Part|S|P)?[_-]?\d+$/i', '', $fileName);
        // Remove tool suffixes
        $name = preg_replace('/[_-]?(Tool|T)\d+$/i', '', $name);
        return $name ?: $fileName;
    }

    /**
     * Extract sheet number from filename
     */
    protected function extractSheetNumber(string $fileName): ?int
    {
        // Look for patterns like "Sheet1", "S1", "_1", "-1"
        if (preg_match('/(?:Sheet|S)[_-]?(\d+)/i', $fileName, $matches)) {
            return (int) $matches[1];
        }
        if (preg_match('/[_-](\d+)$/', $fileName, $matches)) {
            return (int) $matches[1];
        }
        return null;
    }

    /**
     * Log ingestion results to project chatter
     */
    protected function logIngestionToChatter(Project $project, array $results): void
    {
        $messages = [];

        if ($results['programs_created'] > 0) {
            $messages[] = "**{$results['programs_created']} CNC program(s) created** from VCarve files";
        }

        if ($results['parts_created'] > 0) {
            $messages[] = "**{$results['parts_created']} CNC part(s) added** from G-code files";
        }

        if ($results['reference_photos_linked'] > 0) {
            $messages[] = "**{$results['reference_photos_linked']} reference photo(s)** linked to parts";
        }

        if (empty($messages)) {
            return;
        }

        $body = "### CNC Files Ingested\n" . implode("\n", array_map(fn($m) => "- {$m}", $messages));

        try {
            $project->addMessage([
                'type' => 'activity',
                'subject' => 'CNC File Ingestion',
                'body' => $body,
                'is_internal' => true,
                'creator_id' => $project->creator_id ?? 1,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log CNC ingestion to chatter', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Manually trigger full ingestion scan for a project
     *
     * @param Project $project The project to scan
     * @param array $files All current files from Google Drive
     * @return array Ingestion results
     */
    public function fullScan(Project $project, array $files): array
    {
        $cncFiles = array_filter($files, function ($file) {
            return $this->isInCncFolder($file['path'] ?? '');
        });

        return $this->processChanges($project, [
            'added' => array_values($cncFiles),
            'modified' => [],
            'deleted' => [],
        ]);
    }
}
