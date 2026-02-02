<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Webkul\Project\Models\CncProgram;
use Webkul\Project\Models\Project;
use Webkul\Project\Services\CncFileIngestionService;
use Webkul\Project\Services\GoogleDrive\GoogleDriveService;

/**
 * CNC File Ingestion Controller
 *
 * Handles automatic and manual ingestion of CNC files from Google Drive.
 * Creates CncProgram records from VCarve files and G-code parts.
 *
 * @group CNC Ingestion
 */
class CncIngestionController extends Controller
{
    protected CncFileIngestionService $ingestionService;

    protected GoogleDriveService $driveService;

    public function __construct(
        CncFileIngestionService $ingestionService,
        GoogleDriveService $driveService
    ) {
        $this->ingestionService = $ingestionService;
        $this->driveService = $driveService;
    }

    /**
     * Scan a project's Google Drive for CNC files and ingest them
     *
     * Scans the 04_Production/CNC folder for VCarve files (.crv, .crv3d),
     * G-code files (.nc, .gcode, .tap), and reference photos. Creates
     * CncProgram and CncProgramPart records automatically.
     */
    public function scanProject(Request $request): JsonResponse
    {
        $request->validate([
            'project_id' => 'required|integer|exists:projects_projects,id',
        ]);

        $project = Project::findOrFail($request->project_id);

        if (!$project->google_drive_root_folder_id) {
            return response()->json([
                'success' => false,
                'error' => 'Project does not have a Google Drive folder',
            ], 400);
        }

        if (!$this->driveService->isConfigured()) {
            return response()->json([
                'success' => false,
                'error' => 'Google Drive is not configured',
            ], 503);
        }

        try {
            Log::info('CNC Ingestion: Starting scan', [
                'project_id' => $project->id,
                'project_number' => $project->project_number,
            ]);

            // Get all files from Google Drive
            $syncService = $this->driveService->sync();
            $allFiles = $syncService->getAllProjectFiles($project);

            // Run full scan ingestion
            $results = $this->ingestionService->fullScan($project, $allFiles);

            Log::info('CNC Ingestion: Scan complete', [
                'project_id' => $project->id,
                'programs_created' => $results['programs_created'],
                'parts_created' => $results['parts_created'],
            ]);

            return response()->json([
                'success' => true,
                'project_id' => $project->id,
                'project_number' => $project->project_number,
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            Log::error('CNC Ingestion: Scan failed', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get CNC programs for a project
     *
     * Returns all CNC programs associated with a project including
     * their parts and status information.
     */
    public function getPrograms(int $projectId): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        $programs = CncProgram::where('project_id', $project->id)
            ->with(['parts', 'creator'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'project_id' => $project->id,
            'project_number' => $project->project_number,
            'programs' => $programs->map(function ($program) {
                return [
                    'id' => $program->id,
                    'name' => $program->name,
                    'status' => $program->status,
                    'material_code' => $program->material_code,
                    'material_type' => $program->material_type,
                    'sheet_count' => $program->sheet_count,
                    'sheets_estimated' => $program->sheets_estimated,
                    'sheets_actual' => $program->sheets_actual,
                    'utilization_percentage' => $program->utilization_percentage,
                    'completion_percentage' => $program->completion_percentage,
                    'progress_summary' => $program->progress_summary,
                    'nesting_summary' => $program->nesting_summary,
                    'vcarve_file' => $program->vcarve_file,
                    'created_at' => $program->created_at?->toIso8601String(),
                    'nested_at' => $program->nested_at?->toIso8601String(),
                    'parts_count' => $program->parts->count(),
                    'parts' => $program->parts->map(function ($part) {
                        return [
                            'id' => $part->id,
                            'file_name' => $part->file_name,
                            'status' => $part->status,
                            'material_status' => $part->material_status,
                            'sheet_number' => $part->sheet_number,
                            'operation_type' => $part->operation_type,
                            'nc_drive_url' => $part->nc_drive_url,
                            'reference_photo_url' => $part->reference_photo_url,
                        ];
                    }),
                ];
            }),
        ]);
    }

    /**
     * Get CNC ingestion status for a project
     *
     * Returns summary statistics about CNC programs and parts
     * for production tracking.
     */
    public function getStatus(int $projectId): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        $programs = CncProgram::where('project_id', $project->id)->get();

        $stats = [
            'total_programs' => $programs->count(),
            'pending_programs' => $programs->where('status', CncProgram::STATUS_PENDING)->count(),
            'in_progress_programs' => $programs->where('status', CncProgram::STATUS_IN_PROGRESS)->count(),
            'complete_programs' => $programs->where('status', CncProgram::STATUS_COMPLETE)->count(),
            'total_sheets_estimated' => $programs->sum('sheets_estimated'),
            'total_sheets_actual' => $programs->sum('sheets_actual'),
            'avg_utilization' => $programs->whereNotNull('utilization_percentage')->avg('utilization_percentage'),
        ];

        // Get part statistics
        $partStats = \DB::table('projects_cnc_program_parts')
            ->join('projects_cnc_programs', 'projects_cnc_program_parts.cnc_program_id', '=', 'projects_cnc_programs.id')
            ->where('projects_cnc_programs.project_id', $project->id)
            ->selectRaw('
                COUNT(*) as total_parts,
                SUM(CASE WHEN projects_cnc_program_parts.status = ? THEN 1 ELSE 0 END) as pending_parts,
                SUM(CASE WHEN projects_cnc_program_parts.status = ? THEN 1 ELSE 0 END) as running_parts,
                SUM(CASE WHEN projects_cnc_program_parts.status = ? THEN 1 ELSE 0 END) as complete_parts
            ', [
                \Webkul\Project\Models\CncProgramPart::STATUS_PENDING,
                \Webkul\Project\Models\CncProgramPart::STATUS_RUNNING,
                \Webkul\Project\Models\CncProgramPart::STATUS_COMPLETE,
            ])
            ->first();

        $stats['total_parts'] = $partStats->total_parts ?? 0;
        $stats['pending_parts'] = $partStats->pending_parts ?? 0;
        $stats['running_parts'] = $partStats->running_parts ?? 0;
        $stats['complete_parts'] = $partStats->complete_parts ?? 0;

        return response()->json([
            'success' => true,
            'project_id' => $project->id,
            'project_number' => $project->project_number,
            'has_google_drive' => !empty($project->google_drive_root_folder_id),
            'google_drive_synced_at' => $project->google_drive_synced_at?->toIso8601String(),
            'stats' => $stats,
        ]);
    }

    /**
     * Manually create a CNC program for a project
     *
     * Creates a new CNC program record without Google Drive integration.
     * Useful for manually added programs.
     */
    public function createProgram(Request $request): JsonResponse
    {
        $request->validate([
            'project_id' => 'required|integer|exists:projects_projects,id',
            'name' => 'required|string|max:255',
            'material_code' => 'nullable|string|in:' . implode(',', array_keys(CncProgram::getMaterialCodes())),
            'sheet_size' => 'nullable|string|in:' . implode(',', array_keys(CncProgram::getSheetSizes())),
            'description' => 'nullable|string',
        ]);

        $project = Project::findOrFail($request->project_id);

        $program = CncProgram::create([
            'project_id' => $project->id,
            'name' => $request->name,
            'material_code' => $request->material_code,
            'material_type' => $request->material_code
                ? CncProgram::getMaterialCodes()[$request->material_code] ?? null
                : null,
            'sheet_size' => $request->sheet_size ?? '48x96',
            'description' => $request->description,
            'status' => CncProgram::STATUS_PENDING,
            'creator_id' => auth()->id() ?? 1,
            'created_date' => now(),
        ]);

        Log::info('CNC Program created manually', [
            'project_id' => $project->id,
            'program_id' => $program->id,
            'name' => $program->name,
        ]);

        return response()->json([
            'success' => true,
            'program' => [
                'id' => $program->id,
                'name' => $program->name,
                'status' => $program->status,
                'material_code' => $program->material_code,
                'material_type' => $program->material_type,
                'sheet_size' => $program->sheet_size,
            ],
        ], 201);
    }

    /**
     * Update CNC program nesting results
     *
     * Records actual sheet usage after VCarve nesting is complete.
     */
    public function updateNesting(Request $request, int $programId): JsonResponse
    {
        $request->validate([
            'sheets_actual' => 'required|integer|min:1',
            'utilization_percentage' => 'required|numeric|min:0|max:100',
            'nesting_details' => 'nullable|array',
        ]);

        $program = CncProgram::findOrFail($programId);

        $program->recordNestingResults(
            sheetsUsed: $request->sheets_actual,
            utilizationPct: $request->utilization_percentage,
            details: $request->nesting_details
        );

        return response()->json([
            'success' => true,
            'program' => [
                'id' => $program->id,
                'name' => $program->name,
                'sheets_estimated' => $program->sheets_estimated,
                'sheets_actual' => $program->sheets_actual,
                'sheets_variance' => $program->sheets_variance,
                'utilization_percentage' => $program->utilization_percentage,
                'waste_sqft' => $program->waste_sqft,
                'efficiency_rating' => $program->getEfficiencyRating(),
                'nesting_summary' => $program->nesting_summary,
            ],
        ]);
    }

    /**
     * Get available material codes
     */
    public function getMaterialCodes(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'material_codes' => CncProgram::getMaterialCodes(),
            'sheet_sizes' => CncProgram::getSheetSizes(),
        ]);
    }
}
