<?php

namespace Webkul\Project\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Webkul\Project\Models\CabinetMaterialsBom;
use Webkul\Project\Models\Milestone;
use Webkul\Project\Models\MilestoneRequirement;
use Webkul\Project\Models\Project;
use Webkul\Project\Services\GoogleDrive\GoogleDriveService;

/**
 * Milestone Requirement Verifier
 *
 * Checks if milestone requirements are satisfied based on their type.
 * Supports automatic verification for field_check, relation_exists, relation_complete.
 * Manual verification needed for checklist_item, document_upload, approval_required.
 */
class MilestoneRequirementVerifier
{
    protected ?GoogleDriveService $driveService = null;

    public function __construct(?GoogleDriveService $driveService = null)
    {
        $this->driveService = $driveService;
    }

    /**
     * Verify all requirements for a milestone and return status.
     *
     * @return array{can_complete: bool, total: int, verified: int, pending: array}
     */
    public function verifyMilestone(Milestone $milestone): array
    {
        $project = $milestone->project;
        $requirements = $milestone->requirements;

        $results = [
            'can_complete' => true,
            'total' => $requirements->count(),
            'verified' => 0,
            'auto_verified' => 0,
            'pending' => [],
            'requirements' => [],
        ];

        foreach ($requirements as $req) {
            $checkResult = $this->checkRequirement($req, $project);

            $results['requirements'][] = [
                'id' => $req->id,
                'name' => $req->name,
                'type' => $req->requirement_type,
                'is_required' => $req->is_required,
                'is_verified' => $req->is_verified,
                'auto_check_passed' => $checkResult['passed'],
                'message' => $checkResult['message'],
            ];

            // Auto-verify if the check passes and not already verified
            if ($checkResult['passed'] && !$req->is_verified && $checkResult['auto_verify']) {
                $req->update([
                    'is_verified' => true,
                    'verified_at' => now(),
                    'verification_notes' => 'Auto-verified: ' . $checkResult['message'],
                ]);
                $results['auto_verified']++;
            }

            if ($req->is_verified || ($checkResult['passed'] && $checkResult['auto_verify'])) {
                $results['verified']++;
            } else {
                if ($req->is_required) {
                    $results['can_complete'] = false;
                }
                $results['pending'][] = [
                    'id' => $req->id,
                    'name' => $req->name,
                    'type' => $req->requirement_type,
                    'is_required' => $req->is_required,
                    'message' => $checkResult['message'],
                ];
            }
        }

        return $results;
    }

    /**
     * Check a single requirement against project data.
     *
     * @return array{passed: bool, message: string, auto_verify: bool}
     */
    public function checkRequirement(MilestoneRequirement $req, Project $project): array
    {
        $config = $req->config ?? [];

        return match ($req->requirement_type) {
            'field_check' => $this->checkFieldCheck($project, $config),
            'relation_exists' => $this->checkRelationExists($project, $config),
            'relation_complete' => $this->checkRelationComplete($project, $config),
            'document_upload' => $this->checkDocumentUpload($project, $config),
            'checklist_item' => $this->checkChecklistItem($req),
            'approval_required' => $this->checkApprovalRequired($req),
            default => ['passed' => false, 'message' => 'Unknown requirement type', 'auto_verify' => false],
        };
    }

    /**
     * Check if a field has a value.
     */
    protected function checkFieldCheck(Project $project, array $config): array
    {
        $field = $config['field'] ?? null;
        $relation = $config['relation'] ?? null;

        if (!$field) {
            return ['passed' => false, 'message' => 'No field configured', 'auto_verify' => false];
        }

        $model = $project;

        // If checking a related model's field
        if ($relation) {
            $related = $project->{$relation};
            if ($related instanceof \Illuminate\Database\Eloquent\Collection) {
                $related = $related->first();
            }
            if (!$related) {
                return ['passed' => false, 'message' => "No {$relation} found", 'auto_verify' => true];
            }
            $model = $related;
        }

        $value = $model->{$field} ?? null;

        if ($value !== null && $value !== '' && $value !== false) {
            return ['passed' => true, 'message' => "{$field} has value", 'auto_verify' => true];
        }

        return ['passed' => false, 'message' => "{$field} is not set", 'auto_verify' => true];
    }

    /**
     * Check if related records exist.
     */
    protected function checkRelationExists(Project $project, array $config): array
    {
        $relation = $config['relation'] ?? null;
        $minCount = $config['min_count'] ?? 1;

        if (!$relation) {
            return ['passed' => false, 'message' => 'No relation configured', 'auto_verify' => false];
        }

        // Special handling for BOM
        if ($relation === 'bomLines' || $relation === 'bom') {
            $cabinetIds = $project->cabinets()->pluck('id');
            $count = CabinetMaterialsBom::whereIn('cabinet_id', $cabinetIds)->count();
        } else {
            try {
                $count = $project->{$relation}()->count();
            } catch (\Exception $e) {
                return ['passed' => false, 'message' => "Relation {$relation} not found", 'auto_verify' => false];
            }
        }

        if ($count >= $minCount) {
            return ['passed' => true, 'message' => "{$count} {$relation} found", 'auto_verify' => true];
        }

        return ['passed' => false, 'message' => "Need at least {$minCount} {$relation}, found {$count}", 'auto_verify' => true];
    }

    /**
     * Check if all related records have required fields.
     */
    protected function checkRelationComplete(Project $project, array $config): array
    {
        $relation = $config['relation'] ?? null;
        $fields = $config['fields'] ?? [];
        $check = $config['check'] ?? null;

        if (!$relation) {
            return ['passed' => false, 'message' => 'No relation configured', 'auto_verify' => false];
        }

        try {
            $records = $project->{$relation}()->get();
        } catch (\Exception $e) {
            return ['passed' => false, 'message' => "Relation {$relation} not found", 'auto_verify' => false];
        }

        if ($records->isEmpty()) {
            return ['passed' => false, 'message' => "No {$relation} to check", 'auto_verify' => true];
        }

        $total = $records->count();
        $complete = 0;

        foreach ($records as $record) {
            $isComplete = true;

            // Check specific fields
            if (!empty($fields)) {
                foreach ($fields as $field) {
                    if (empty($record->{$field})) {
                        $isComplete = false;
                        break;
                    }
                }
            }

            // Custom check
            if ($check === 'has_hardware_selections') {
                // Check if cabinet has hardware configured
                $hasHardware = !empty($record->specialty_hardware_json) ||
                    !empty($record->hardware_notes);
                if (!$hasHardware) {
                    $isComplete = false;
                }
            }

            if ($isComplete) {
                $complete++;
            }
        }

        if ($complete === $total) {
            return ['passed' => true, 'message' => "All {$total} {$relation} complete", 'auto_verify' => true];
        }

        $incomplete = $total - $complete;
        return ['passed' => false, 'message' => "{$incomplete} of {$total} {$relation} incomplete", 'auto_verify' => true];
    }

    /**
     * Check if document is uploaded.
     * Checks Google Drive for files matching the configured criteria.
     *
     * Config options:
     * - folder: Path to subfolder in project's Google Drive (e.g., "02_Design/DWG_Imports")
     * - extensions: File extension(s) to look for (e.g., "3dm" or ["crv", "crv3d"])
     * - min_count: Minimum number of files required (default: 1)
     * - document_type: Legacy field for categorization
     */
    protected function checkDocumentUpload(Project $project, array $config): array
    {
        $folder = $config['folder'] ?? null;
        $extensions = $config['extensions'] ?? null;
        $minCount = $config['min_count'] ?? 1;
        $documentType = $config['document_type'] ?? 'document';

        // If no folder or extensions configured, fall back to manual verification
        if (!$folder || !$extensions) {
            return [
                'passed' => false,
                'message' => 'Document upload requires manual verification',
                'auto_verify' => false,
            ];
        }

        // Check if project has Google Drive folder
        if (!$project->google_drive_root_folder_id) {
            return [
                'passed' => false,
                'message' => 'Project Google Drive folder not configured',
                'auto_verify' => false,
            ];
        }

        // Get Google Drive service
        $driveService = $this->driveService ?? app(GoogleDriveService::class);
        if (!$driveService->isConfigured()) {
            return [
                'passed' => false,
                'message' => 'Google Drive not configured',
                'auto_verify' => false,
            ];
        }

        try {
            $result = $driveService->folders()->checkProjectHasDesignFiles(
                $project->google_drive_root_folder_id,
                $folder,
                $extensions
            );

            if (!$result['folder_found']) {
                return [
                    'passed' => false,
                    'message' => "Folder '{$folder}' not found in project Drive",
                    'auto_verify' => false,
                ];
            }

            $extensionStr = is_array($extensions) ? implode('/', $extensions) : $extensions;

            if ($result['count'] >= $minCount) {
                $fileNames = collect($result['files'])->pluck('name')->take(3)->implode(', ');
                $suffix = $result['count'] > 3 ? ' (+' . ($result['count'] - 3) . ' more)' : '';
                return [
                    'passed' => true,
                    'message' => "{$result['count']} {$extensionStr} file(s) found: {$fileNames}{$suffix}",
                    'auto_verify' => true,
                ];
            }

            return [
                'passed' => false,
                'message' => "Need {$minCount}+ {$extensionStr} file(s) in {$folder}, found {$result['count']}",
                'auto_verify' => true,
            ];
        } catch (\Exception $e) {
            Log::warning('Failed to check Google Drive for documents', [
                'project_id' => $project->id,
                'folder' => $folder,
                'error' => $e->getMessage(),
            ]);

            return [
                'passed' => false,
                'message' => 'Failed to check Google Drive: ' . $e->getMessage(),
                'auto_verify' => false,
            ];
        }
    }

    /**
     * Checklist items require manual verification.
     */
    protected function checkChecklistItem(MilestoneRequirement $req): array
    {
        if ($req->is_verified) {
            return ['passed' => true, 'message' => 'Manually verified', 'auto_verify' => false];
        }

        return [
            'passed' => false,
            'message' => 'Requires manual verification',
            'auto_verify' => false,
        ];
    }

    /**
     * Approvals require manual verification.
     */
    protected function checkApprovalRequired(MilestoneRequirement $req): array
    {
        if ($req->is_verified) {
            return ['passed' => true, 'message' => 'Approval recorded', 'auto_verify' => false];
        }

        return [
            'passed' => false,
            'message' => 'Awaiting approval',
            'auto_verify' => false,
        ];
    }

    /**
     * Verify all milestones for a project and return summary.
     */
    public function verifyProjectMilestones(Project $project): array
    {
        $milestones = $project->milestones()->with('requirements')->get();

        $summary = [
            'total_milestones' => $milestones->count(),
            'completable' => 0,
            'blocked' => 0,
            'by_stage' => [],
            'milestones' => [],
        ];

        foreach ($milestones as $milestone) {
            $result = $this->verifyMilestone($milestone);

            $summary['milestones'][] = [
                'id' => $milestone->id,
                'name' => $milestone->name,
                'stage' => $milestone->production_stage,
                'is_completed' => $milestone->is_completed,
                'can_complete' => $result['can_complete'],
                'verified' => $result['verified'],
                'total' => $result['total'],
                'pending_required' => collect($result['pending'])->where('is_required', true)->count(),
            ];

            if ($result['can_complete']) {
                $summary['completable']++;
            } else {
                $summary['blocked']++;
            }

            // Group by stage
            if (!isset($summary['by_stage'][$milestone->production_stage])) {
                $summary['by_stage'][$milestone->production_stage] = [
                    'total' => 0,
                    'completable' => 0,
                    'completed' => 0,
                ];
            }
            $summary['by_stage'][$milestone->production_stage]['total']++;
            if ($result['can_complete']) {
                $summary['by_stage'][$milestone->production_stage]['completable']++;
            }
            if ($milestone->is_completed) {
                $summary['by_stage'][$milestone->production_stage]['completed']++;
            }
        }

        return $summary;
    }
}
