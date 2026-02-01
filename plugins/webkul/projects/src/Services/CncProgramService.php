<?php

namespace Webkul\Project\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Webkul\Project\Models\CncProgram;
use Webkul\Project\Models\CncProgramPart;
use Webkul\Project\Models\Project;

/**
 * CNC Program Service
 *
 * Business logic for CNC program management
 */
class CncProgramService
{
    /**
     * Create CNC program from BOM data
     *
     * @param Project $project
     * @param array $bomData
     * @return CncProgram
     */
    public function createFromBom(Project $project, array $bomData): CncProgram
    {
        return DB::transaction(function () use ($project, $bomData) {
            $program = CncProgram::create([
                'project_id' => $project->id,
                'name' => $bomData['name'] ?? "{$project->name} - {$bomData['material_code']}",
                'material_code' => $bomData['material_code'],
                'material_type' => $bomData['material_type'] ?? null,
                'sheet_size' => $bomData['sheet_size'] ?? '48x96',
                'sheet_count' => $bomData['sheet_count'] ?? 1,
                'sheets_estimated' => $bomData['sheets_estimated'] ?? null,
                'sqft_estimated' => $bomData['sqft_estimated'] ?? null,
                'status' => CncProgram::STATUS_PENDING,
                'creator_id' => auth()->id(),
                'created_date' => now(),
            ]);

            // Create parts if provided
            if (!empty($bomData['parts'])) {
                foreach ($bomData['parts'] as $partData) {
                    $program->parts()->create([
                        'file_name' => $partData['file_name'],
                        'sheet_number' => $partData['sheet_number'] ?? null,
                        'operation_type' => $partData['operation_type'] ?? null,
                        'status' => CncProgramPart::STATUS_PENDING,
                        'material_status' => CncProgramPart::MATERIAL_READY,
                        'quantity' => $partData['quantity'] ?? 1,
                    ]);
                }
            }

            return $program;
        });
    }

    /**
     * Import NC files from a directory
     *
     * @param CncProgram $program
     * @param string $directory
     * @return Collection
     */
    public function importNcFiles(CncProgram $program, string $directory): Collection
    {
        $files = glob($directory . '/*.NC') ?: [];
        $parts = collect();

        foreach ($files as $filePath) {
            $fileName = basename($filePath);
            $parsed = $this->parseNcFileName($fileName);

            $part = $program->parts()->create([
                'file_name' => $fileName,
                'file_path' => $filePath,
                'sheet_number' => $parsed['sheet_number'] ?? null,
                'operation_type' => $parsed['operation_type'] ?? null,
                'tool' => $parsed['tool'] ?? null,
                'file_size' => filesize($filePath),
                'status' => CncProgramPart::STATUS_PENDING,
                'material_status' => CncProgramPart::MATERIAL_READY,
                'quantity' => 1,
            ]);

            $parts->push($part);
        }

        return $parts;
    }

    /**
     * Parse NC file name according to naming convention
     *
     * Format: [DATE]_[MATERIAL]_[SEQUENCE]_[RANGE]-[OPERATION].NC
     * Example: 2026-01-15_RiftWO_01_1-5-Profile.NC
     *
     * @param string $fileName
     * @return array
     */
    public function parseNcFileName(string $fileName): array
    {
        $result = [
            'date' => null,
            'material' => null,
            'sheet_number' => null,
            'operation_type' => null,
        ];

        // Remove .NC extension
        $name = preg_replace('/\.NC$/i', '', $fileName);

        // Pattern: DATE_MATERIAL_SHEET_RANGE-OPERATION
        if (preg_match('/^(\d{4}-\d{2}-\d{2})_([^_]+)_(\d+)_([^-]+)-(.+)$/', $name, $matches)) {
            $result['date'] = $matches[1];
            $result['material'] = $matches[2];
            $result['sheet_number'] = (int) $matches[3];
            $result['operation_type'] = strtolower($matches[5]);
        }

        return $result;
    }

    /**
     * Get CNC statistics for a project
     *
     * @param Project $project
     * @return array
     */
    public function getProjectCncStats(Project $project): array
    {
        $programs = $project->cncPrograms()->with('parts')->get();

        $totalPrograms = $programs->count();
        $completePrograms = $programs->where('status', CncProgram::STATUS_COMPLETE)->count();
        $inProgressPrograms = $programs->where('status', CncProgram::STATUS_IN_PROGRESS)->count();

        $totalParts = 0;
        $completeParts = 0;
        $runningParts = 0;
        $pendingMaterial = 0;

        foreach ($programs as $program) {
            $totalParts += $program->parts->count();
            $completeParts += $program->parts->where('status', CncProgramPart::STATUS_COMPLETE)->count();
            $runningParts += $program->parts->where('status', CncProgramPart::STATUS_RUNNING)->count();
            $pendingMaterial += $program->parts->where('material_status', CncProgramPart::MATERIAL_PENDING)->count();
        }

        return [
            'total_programs' => $totalPrograms,
            'complete_programs' => $completePrograms,
            'in_progress_programs' => $inProgressPrograms,
            'pending_programs' => $totalPrograms - $completePrograms - $inProgressPrograms,
            'total_parts' => $totalParts,
            'complete_parts' => $completeParts,
            'running_parts' => $runningParts,
            'pending_parts' => $totalParts - $completeParts - $runningParts,
            'pending_material' => $pendingMaterial,
            'completion_percentage' => $totalParts > 0 ? round(($completeParts / $totalParts) * 100, 1) : 0,
        ];
    }

    /**
     * Get pending CNC queue across all projects
     *
     * @param int|null $limit
     * @return Collection
     */
    public function getCncQueue(?int $limit = null): Collection
    {
        $query = CncProgramPart::with(['cncProgram.project', 'operator'])
            ->where('status', CncProgramPart::STATUS_PENDING)
            ->orderBy('created_at');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Get currently running CNC parts
     *
     * @return Collection
     */
    public function getRunningParts(): Collection
    {
        return CncProgramPart::with(['cncProgram.project', 'operator'])
            ->where('status', CncProgramPart::STATUS_RUNNING)
            ->orderBy('run_at')
            ->get();
    }

    /**
     * Get operator workload
     *
     * @return Collection
     */
    public function getOperatorWorkload(): Collection
    {
        return CncProgramPart::select('operator_id', DB::raw('count(*) as parts_count'))
            ->whereIn('status', [CncProgramPart::STATUS_RUNNING, CncProgramPart::STATUS_PENDING])
            ->whereNotNull('operator_id')
            ->groupBy('operator_id')
            ->with('operator')
            ->get();
    }

    /**
     * Get parts pending material
     *
     * @return Collection
     */
    public function getPartsPendingMaterial(): Collection
    {
        return CncProgramPart::with(['cncProgram.project'])
            ->where('material_status', CncProgramPart::MATERIAL_PENDING)
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Bulk assign operator to parts
     *
     * @param array $partIds
     * @param int $operatorId
     * @return int Number of updated records
     */
    public function bulkAssignOperator(array $partIds, int $operatorId): int
    {
        return CncProgramPart::whereIn('id', $partIds)
            ->update(['operator_id' => $operatorId]);
    }

    /**
     * Calculate average utilization for a project
     *
     * @param Project $project
     * @return float|null
     */
    public function getAverageUtilization(Project $project): ?float
    {
        $avg = $project->cncPrograms()
            ->whereNotNull('utilization_percentage')
            ->avg('utilization_percentage');

        return $avg ? round($avg, 1) : null;
    }

    /**
     * Get CNC programs by material code
     *
     * @param string $materialCode
     * @param string|null $status
     * @return Collection
     */
    public function getProgramsByMaterial(string $materialCode, ?string $status = null): Collection
    {
        $query = CncProgram::with('project')
            ->where('material_code', $materialCode);

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }
}
