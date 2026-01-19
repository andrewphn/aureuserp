<?php

namespace App\Services;

use App\Models\RhinoExtractionJob;
use App\Models\RhinoExtractionReview;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Project\Models\Cabinet;
use Webkul\Project\Models\Project;

/**
 * RhinoSyncService - Bidirectional synchronization between Rhino and ERP
 *
 * Push to Rhino (ERP → Rhino):
 * - Generate cabinet calculations via CabinetCalculatorService
 * - Transform to Rhino coordinates via RhinoExportService
 * - Execute Python script to create/update Rhino geometry
 *
 * Pull from Rhino (Rhino → ERP):
 * - Extract fresh data via RhinoDataExtractor
 * - Detect conflicts (dimension differences > tolerance)
 * - Queue conflicts for review or auto-merge
 *
 * @author TCS Woodwork
 * @since January 2026
 */
class RhinoSyncService
{
    /**
     * Dimension tolerance for conflict detection (inches)
     */
    protected const CONFLICT_TOLERANCE = 0.5;

    protected RhinoMCPService $rhinoMcp;
    protected RhinoDataExtractor $extractor;
    protected RhinoToCabinetMapper $mapper;
    protected ExtractionConfidenceScorer $scorer;
    protected CabinetCalculatorService $calculator;
    protected RhinoExportService $exporter;

    public function __construct(
        RhinoMCPService $rhinoMcp,
        RhinoDataExtractor $extractor,
        RhinoToCabinetMapper $mapper,
        ExtractionConfidenceScorer $scorer,
        CabinetCalculatorService $calculator,
        RhinoExportService $exporter
    ) {
        $this->rhinoMcp = $rhinoMcp;
        $this->extractor = $extractor;
        $this->mapper = $mapper;
        $this->scorer = $scorer;
        $this->calculator = $calculator;
        $this->exporter = $exporter;
    }

    /**
     * Push ERP cabinet changes to Rhino
     *
     * @param Cabinet $cabinet Cabinet to push
     * @param array $options Sync options
     * @return array Sync result
     */
    public function pushToRhino(Cabinet $cabinet, array $options = []): array
    {
        Log::info('RhinoSyncService: Pushing cabinet to Rhino', [
            'cabinet_id' => $cabinet->id,
            'cabinet_number' => $cabinet->cabinet_number,
        ]);

        try {
            // Calculate full cabinet specifications
            $calculations = $this->calculator->calculateFull($cabinet);

            // Transform to Rhino export format
            $rhinoData = $this->exporter->transformCabinetForRhino($cabinet, $calculations);

            // Determine operation type
            $existingGroup = $this->findRhinoGroup($cabinet->cabinet_number);
            $operation = $existingGroup ? 'update' : 'create';

            // Execute Rhino script to create/update geometry
            $script = $this->buildRhinoUpdateScript($rhinoData, $operation, $existingGroup);
            $result = $this->rhinoMcp->executeRhinoScript($script);

            if ($result['success'] ?? false) {
                Log::info('RhinoSyncService: Successfully pushed cabinet to Rhino', [
                    'cabinet_id' => $cabinet->id,
                    'operation' => $operation,
                ]);

                return [
                    'success' => true,
                    'operation' => $operation,
                    'cabinet_id' => $cabinet->id,
                    'rhino_group' => $rhinoData['group_name'] ?? $cabinet->cabinet_number,
                    'message' => "Cabinet {$operation}d in Rhino successfully",
                ];
            } else {
                throw new \RuntimeException($result['error'] ?? 'Unknown Rhino error');
            }
        } catch (\Exception $e) {
            Log::error('RhinoSyncService: Failed to push cabinet to Rhino', [
                'cabinet_id' => $cabinet->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'operation' => 'failed',
                'cabinet_id' => $cabinet->id,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Pull Rhino changes to ERP
     *
     * @param int $projectId Project to sync
     * @param array $options Sync options
     * @return array Sync result
     */
    public function pullFromRhino(int $projectId, array $options = []): array
    {
        Log::info('RhinoSyncService: Pulling from Rhino', ['project_id' => $projectId]);

        $project = Project::find($projectId);
        if (!$project) {
            return ['success' => false, 'error' => 'Project not found'];
        }

        try {
            // Extract fresh data from Rhino
            $extractedData = $this->extractor->extractCabinets();
            $extractedCabinets = $extractedData['cabinets'] ?? [];

            if (empty($extractedCabinets)) {
                return [
                    'success' => true,
                    'synced' => 0,
                    'conflicts' => 0,
                    'message' => 'No cabinets found in Rhino document',
                ];
            }

            $synced = 0;
            $conflicts = 0;
            $created = 0;
            $conflictDetails = [];

            foreach ($extractedCabinets as $extracted) {
                // Find matching cabinet in ERP
                $existingCabinet = $this->mapper->findMatchingCabinet(
                    $extracted['name'] ?? '',
                    $projectId
                );

                if ($existingCabinet) {
                    // Check for conflicts
                    $conflict = $this->detectConflict($existingCabinet, $extracted);

                    if ($conflict['has_conflict']) {
                        $conflicts++;
                        $conflictDetails[] = $conflict;

                        // Create review item for conflict
                        $this->createConflictReview($existingCabinet, $extracted, $conflict, $projectId);
                    } else {
                        // Auto-merge if no conflict
                        if ($options['auto_merge'] ?? true) {
                            $this->mergeCabinetData($existingCabinet, $extracted);
                            $synced++;
                        }
                    }
                } else {
                    // New cabinet - optionally create
                    if ($options['create_new'] ?? false) {
                        $mapped = $this->mapper->mapToCabinetData($extracted, [
                            'project_id' => $projectId,
                        ]);

                        // Create review item for new cabinet
                        $this->createNewCabinetReview($mapped, $projectId);
                        $created++;
                    }
                }
            }

            return [
                'success' => true,
                'synced' => $synced,
                'conflicts' => $conflicts,
                'created' => $created,
                'extracted_count' => count($extractedCabinets),
                'conflict_details' => $conflictDetails,
                'message' => "Sync complete: {$synced} updated, {$conflicts} conflicts, {$created} new",
            ];
        } catch (\Exception $e) {
            Log::error('RhinoSyncService: Pull failed', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get sync status for a project
     *
     * @param int $projectId Project ID
     * @return array Status information
     */
    public function getSyncStatus(int $projectId): array
    {
        $project = Project::with('cabinets')->find($projectId);
        if (!$project) {
            return ['error' => 'Project not found'];
        }

        // Get pending reviews for this project
        $pendingReviews = RhinoExtractionReview::where('project_id', $projectId)
            ->where('status', RhinoExtractionReview::STATUS_PENDING)
            ->count();

        $syncConflicts = RhinoExtractionReview::where('project_id', $projectId)
            ->where('review_type', RhinoExtractionReview::TYPE_SYNC_CONFLICT)
            ->where('status', RhinoExtractionReview::STATUS_PENDING)
            ->count();

        // Get last extraction job
        $lastJob = RhinoExtractionJob::where('project_id', $projectId)
            ->latest()
            ->first();

        return [
            'project_id' => $projectId,
            'project_name' => $project->name,
            'cabinet_count' => $project->cabinets->count(),
            'pending_reviews' => $pendingReviews,
            'sync_conflicts' => $syncConflicts,
            'last_extraction' => $lastJob ? [
                'id' => $lastJob->id,
                'status' => $lastJob->status,
                'completed_at' => $lastJob->completed_at?->toIso8601String(),
                'cabinets_extracted' => $lastJob->cabinets_extracted,
            ] : null,
            'sync_available' => $pendingReviews === 0 && $syncConflicts === 0,
        ];
    }

    /**
     * Detect conflicts between ERP cabinet and Rhino extraction
     */
    protected function detectConflict(Cabinet $erpCabinet, array $rhinoCabinet): array
    {
        $conflicts = [];

        // Check width
        $erpWidth = $erpCabinet->length_inches;
        $rhinoWidth = $rhinoCabinet['width'] ?? null;
        if ($erpWidth && $rhinoWidth && abs($erpWidth - $rhinoWidth) > self::CONFLICT_TOLERANCE) {
            $conflicts[] = [
                'field' => 'width',
                'erp_value' => $erpWidth,
                'rhino_value' => $rhinoWidth,
                'difference' => abs($erpWidth - $rhinoWidth),
            ];
        }

        // Check height
        $erpHeight = $erpCabinet->height_inches;
        $rhinoHeight = $rhinoCabinet['height'] ?? null;
        if ($erpHeight && $rhinoHeight && abs($erpHeight - $rhinoHeight) > self::CONFLICT_TOLERANCE) {
            $conflicts[] = [
                'field' => 'height',
                'erp_value' => $erpHeight,
                'rhino_value' => $rhinoHeight,
                'difference' => abs($erpHeight - $rhinoHeight),
            ];
        }

        // Check depth
        $erpDepth = $erpCabinet->depth_inches;
        $rhinoDepth = $rhinoCabinet['depth'] ?? null;
        if ($erpDepth && $rhinoDepth && abs($erpDepth - $rhinoDepth) > self::CONFLICT_TOLERANCE) {
            $conflicts[] = [
                'field' => 'depth',
                'erp_value' => $erpDepth,
                'rhino_value' => $rhinoDepth,
                'difference' => abs($erpDepth - $rhinoDepth),
            ];
        }

        return [
            'has_conflict' => !empty($conflicts),
            'conflicts' => $conflicts,
            'cabinet_id' => $erpCabinet->id,
            'rhino_group' => $rhinoCabinet['name'] ?? null,
        ];
    }

    /**
     * Merge Rhino data into existing cabinet (no conflicts)
     */
    protected function mergeCabinetData(Cabinet $cabinet, array $rhinoData): void
    {
        $updates = [];

        // Only update if Rhino has data and ERP doesn't
        if (empty($cabinet->length_inches) && !empty($rhinoData['width'])) {
            $updates['length_inches'] = $rhinoData['width'];
        }
        if (empty($cabinet->height_inches) && !empty($rhinoData['height'])) {
            $updates['height_inches'] = $rhinoData['height'];
        }
        if (empty($cabinet->depth_inches) && !empty($rhinoData['depth'])) {
            $updates['depth_inches'] = $rhinoData['depth'];
        }

        // Update component counts if not set
        if (empty($cabinet->drawer_count) && ($rhinoData['components']['drawer_count'] ?? 0) > 0) {
            $updates['drawer_count'] = $rhinoData['components']['drawer_count'];
        }
        if (empty($cabinet->door_count) && ($rhinoData['components']['door_count'] ?? 0) > 0) {
            $updates['door_count'] = $rhinoData['components']['door_count'];
        }

        if (!empty($updates)) {
            $cabinet->update($updates);

            // Append sync note
            $cabinet->shop_notes = ($cabinet->shop_notes ?? '') .
                "\nSynced from Rhino: " . now()->format('Y-m-d H:i');
            $cabinet->save();

            Log::debug('RhinoSyncService: Merged cabinet data', [
                'cabinet_id' => $cabinet->id,
                'updates' => $updates,
            ]);
        }
    }

    /**
     * Create review item for sync conflict
     */
    protected function createConflictReview(
        Cabinet $erpCabinet,
        array $rhinoData,
        array $conflict,
        int $projectId
    ): RhinoExtractionReview {
        // Get or create extraction job for this sync
        $job = $this->getOrCreateSyncJob($projectId);

        $score = $this->scorer->calculateScore($rhinoData);

        return RhinoExtractionReview::create([
            'extraction_job_id' => $job->id,
            'project_id' => $projectId,
            'cabinet_id' => $erpCabinet->id,
            'rhino_group_name' => $rhinoData['name'] ?? null,
            'cabinet_number' => $erpCabinet->cabinet_number,
            'extraction_data' => $rhinoData,
            'confidence_score' => $score['total'],
            'status' => RhinoExtractionReview::STATUS_PENDING,
            'review_type' => RhinoExtractionReview::TYPE_SYNC_CONFLICT,
            'erp_data' => [
                'width' => $erpCabinet->length_inches,
                'height' => $erpCabinet->height_inches,
                'depth' => $erpCabinet->depth_inches,
                'drawer_count' => $erpCabinet->drawer_count,
                'door_count' => $erpCabinet->door_count,
            ],
            'rhino_data' => [
                'width' => $rhinoData['width'] ?? null,
                'height' => $rhinoData['height'] ?? null,
                'depth' => $rhinoData['depth'] ?? null,
                'drawer_count' => $rhinoData['components']['drawer_count'] ?? 0,
                'door_count' => $rhinoData['components']['door_count'] ?? 0,
            ],
            'sync_direction' => 'conflict',
        ]);
    }

    /**
     * Create review item for new cabinet from Rhino
     */
    protected function createNewCabinetReview(array $mappedData, int $projectId): RhinoExtractionReview
    {
        $job = $this->getOrCreateSyncJob($projectId);

        return RhinoExtractionReview::create([
            'extraction_job_id' => $job->id,
            'project_id' => $projectId,
            'rhino_group_name' => $mappedData['_rhino_source']['group_name'] ?? null,
            'cabinet_number' => $mappedData['cabinet_number'] ?? null,
            'extraction_data' => $mappedData,
            'confidence_score' => $mappedData['_rhino_source']['confidence'] === 'high' ? 85 : 50,
            'status' => RhinoExtractionReview::STATUS_PENDING,
            'review_type' => RhinoExtractionReview::TYPE_LOW_CONFIDENCE,
            'sync_direction' => 'pull',
        ]);
    }

    /**
     * Get or create a sync job for the project
     */
    protected function getOrCreateSyncJob(int $projectId): RhinoExtractionJob
    {
        // Look for existing pending job from today
        $existingJob = RhinoExtractionJob::where('project_id', $projectId)
            ->whereDate('created_at', today())
            ->where('status', RhinoExtractionJob::STATUS_PROCESSING)
            ->first();

        if ($existingJob) {
            return $existingJob;
        }

        // Create new job
        return RhinoExtractionJob::create([
            'project_id' => $projectId,
            'user_id' => auth()->id() ?? 1,
            'status' => RhinoExtractionJob::STATUS_PROCESSING,
            'options' => ['type' => 'sync'],
            'started_at' => now(),
        ]);
    }

    /**
     * Find Rhino group matching cabinet name
     */
    protected function findRhinoGroup(string $cabinetNumber): ?string
    {
        $groups = $this->rhinoMcp->getGroups();

        foreach ($groups as $group) {
            if (stripos($group, $cabinetNumber) !== false) {
                return $group;
            }
        }

        return null;
    }

    /**
     * Build RhinoScript to update cabinet geometry
     */
    protected function buildRhinoUpdateScript(array $rhinoData, string $operation, ?string $existingGroup): string
    {
        $groupName = $existingGroup ?? ($rhinoData['group_name'] ?? 'NewCabinet');
        $width = $rhinoData['dimensions']['width'] ?? 36;
        $height = $rhinoData['dimensions']['height'] ?? 34.5;
        $depth = $rhinoData['dimensions']['depth'] ?? 24;

        if ($operation === 'update' && $existingGroup) {
            // Update existing group
            return <<<PYTHON
import rhinoscriptsyntax as rs
import json

group_name = "{$groupName}"
new_width = {$width}
new_height = {$height}
new_depth = {$depth}

# Find objects in group
objects = rs.ObjectsByGroup(group_name)

if objects:
    # Get bounding box of current objects
    bbox = rs.BoundingBox(objects)
    if bbox:
        current_width = abs(bbox[1][0] - bbox[0][0])
        current_height = abs(bbox[3][2] - bbox[0][2])
        current_depth = abs(bbox[4][1] - bbox[0][1])

        # Calculate scale factors
        scale_x = new_width / current_width if current_width > 0 else 1
        scale_y = new_depth / current_depth if current_depth > 0 else 1
        scale_z = new_height / current_height if current_height > 0 else 1

        # Scale objects from base center
        base_center = [(bbox[0][0] + bbox[1][0]) / 2, (bbox[0][1] + bbox[4][1]) / 2, bbox[0][2]]

        for obj in objects:
            rs.ScaleObject(obj, base_center, [scale_x, scale_y, scale_z])

        print(json.dumps({
            "success": True,
            "operation": "update",
            "group": group_name,
            "scale_factors": [scale_x, scale_y, scale_z]
        }))
    else:
        print(json.dumps({"success": False, "error": "Could not get bounding box"}))
else:
    print(json.dumps({"success": False, "error": "Group not found"}))
PYTHON;
        } else {
            // Create new cabinet
            return <<<PYTHON
import rhinoscriptsyntax as rs
import json

group_name = "{$groupName}"
width = {$width}
height = {$height}
depth = {$depth}

# Create cabinet box at origin
corners = rs.AddBox([
    [0, 0, 0],
    [width, 0, 0],
    [width, depth, 0],
    [0, depth, 0],
    [0, 0, height],
    [width, 0, height],
    [width, depth, height],
    [0, depth, height]
])

if corners:
    # Create or add to group
    rs.AddGroup(group_name)
    rs.AddObjectToGroup(corners, group_name)

    print(json.dumps({
        "success": True,
        "operation": "create",
        "group": group_name,
        "dimensions": {"width": width, "height": height, "depth": depth}
    }))
else:
    print(json.dumps({"success": False, "error": "Failed to create box"}))
PYTHON;
        }
    }

    /**
     * Force sync in a specific direction
     *
     * @param int $reviewId Review item ID
     * @param string $direction 'erp' or 'rhino'
     * @param int $userId Reviewer user ID
     * @return array Result
     */
    public function forceSync(int $reviewId, string $direction, int $userId): array
    {
        $review = RhinoExtractionReview::find($reviewId);
        if (!$review) {
            return ['success' => false, 'error' => 'Review not found'];
        }

        if ($direction === 'erp') {
            // Use ERP values - push to Rhino
            if ($review->cabinet_id) {
                $cabinet = Cabinet::find($review->cabinet_id);
                if ($cabinet) {
                    $result = $this->pushToRhino($cabinet);

                    if ($result['success']) {
                        $review->approve($userId, ['forced_direction' => 'erp'], 'Forced ERP values to Rhino');
                    }

                    return $result;
                }
            }
        } elseif ($direction === 'rhino') {
            // Use Rhino values - update ERP
            if ($review->cabinet_id) {
                $cabinet = Cabinet::find($review->cabinet_id);
                if ($cabinet) {
                    $rhinoData = $review->rhino_data ?? [];

                    $cabinet->update([
                        'length_inches' => $rhinoData['width'] ?? $cabinet->length_inches,
                        'height_inches' => $rhinoData['height'] ?? $cabinet->height_inches,
                        'depth_inches' => $rhinoData['depth'] ?? $cabinet->depth_inches,
                    ]);

                    $review->approve($userId, ['forced_direction' => 'rhino'], 'Forced Rhino values to ERP');

                    return [
                        'success' => true,
                        'message' => 'ERP cabinet updated with Rhino values',
                        'cabinet_id' => $cabinet->id,
                    ];
                }
            }
        }

        return ['success' => false, 'error' => 'Invalid direction or missing cabinet'];
    }
}
