<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Jobs\ProcessRhinoExtraction;
use App\Models\RhinoExtractionJob;
use App\Models\RhinoExtractionReview;
use App\Services\AIDimensionInterpreter;
use App\Services\ExtractionConfidenceScorer;
use App\Services\RhinoDataExtractor;
use App\Services\RhinoMCPService;
use App\Services\RhinoSyncService;
use App\Services\RhinoToCabinetMapper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * RhinoExtractionController - API endpoints for Rhino cabinet extraction
 *
 * Endpoints:
 * - Document operations (scan, info, groups)
 * - Cabinet extraction (single, batch)
 * - Job management (list, status)
 * - Review queue (list, approve, reject)
 * - Bidirectional sync (push, pull, status)
 *
 * @author TCS Woodwork
 * @since January 2026
 */
class RhinoExtractionController extends BaseApiController
{
    public function __construct(
        protected RhinoMCPService $rhinoMcp,
        protected RhinoDataExtractor $extractor,
        protected RhinoToCabinetMapper $mapper,
        protected ExtractionConfidenceScorer $scorer,
        protected AIDimensionInterpreter $interpreter,
        protected RhinoSyncService $syncService
    ) {}

    // =========================================================================
    // Document Operations
    // =========================================================================

    /**
     * Trigger full document extraction (async)
     *
     * POST /api/v1/rhino/document/scan
     */
    public function scanDocument(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => 'nullable|integer|exists:projects_projects,id',
            'force' => 'boolean',
            'include_fixtures' => 'boolean',
            'auto_approve_high_confidence' => 'boolean',
        ]);

        // Create extraction job
        $job = RhinoExtractionJob::create([
            'project_id' => $validated['project_id'] ?? null,
            'user_id' => $request->user()->id,
            'status' => RhinoExtractionJob::STATUS_PENDING,
            'options' => [
                'force' => $validated['force'] ?? false,
                'include_fixtures' => $validated['include_fixtures'] ?? true,
                'auto_approve_high_confidence' => $validated['auto_approve_high_confidence'] ?? true,
            ],
        ]);

        // Dispatch async job
        ProcessRhinoExtraction::dispatch($job);

        $this->logActivity('rhino.extraction_started', [
            'job_id' => $job->id,
            'project_id' => $validated['project_id'] ?? null,
        ]);

        return $this->success([
            'job_id' => $job->id,
            'uuid' => $job->uuid,
            'status' => $job->status,
            'message' => 'Extraction job queued',
        ], 'Extraction started', 202);
    }

    /**
     * Get Rhino document metadata
     *
     * GET /api/v1/rhino/document/info
     */
    public function getDocumentInfo(): JsonResponse
    {
        try {
            $info = $this->rhinoMcp->getDocumentInfo();
            $layers = $this->rhinoMcp->getLayers();
            $groups = $this->rhinoMcp->getGroups();

            return $this->success([
                'document' => $info,
                'layers' => $layers,
                'layer_count' => count($layers),
                'groups' => $groups,
                'group_count' => count($groups),
            ], 'Document info retrieved');
        } catch (\Exception $e) {
            return $this->error('Failed to get document info: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * List cabinet groups in Rhino document
     *
     * GET /api/v1/rhino/document/groups
     */
    public function getDocumentGroups(): JsonResponse
    {
        try {
            $groups = $this->rhinoMcp->getGroups();
            $summary = $this->extractor->getDocumentSummary();

            return $this->success([
                'groups' => $groups,
                'cabinet_groups' => $summary['potential_cabinet_groups'],
                'total_groups' => count($groups),
                'cabinet_group_count' => count($summary['potential_cabinet_groups']),
            ], 'Groups retrieved');
        } catch (\Exception $e) {
            return $this->error('Failed to get groups: ' . $e->getMessage(), null, 500);
        }
    }

    // =========================================================================
    // Cabinet Extraction
    // =========================================================================

    /**
     * Extract single cabinet by group name
     *
     * POST /api/v1/rhino/cabinet/extract
     */
    public function extractCabinet(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'group_name' => 'required|string',
            'project_id' => 'nullable|integer|exists:projects_projects,id',
            'use_ai' => 'boolean',
        ]);

        try {
            // Extract all cabinets and find the matching one
            $extractedData = $this->extractor->extractCabinets();
            $matchingCabinet = null;

            foreach ($extractedData['cabinets'] as $cabinet) {
                if ($cabinet['name'] === $validated['group_name']) {
                    $matchingCabinet = $cabinet;
                    break;
                }
            }

            if (!$matchingCabinet) {
                return $this->notFound("Cabinet group '{$validated['group_name']}' not found");
            }

            // Calculate confidence score
            $score = $this->scorer->calculateScore($matchingCabinet);

            // Optional AI interpretation
            $aiInterpretation = null;
            if ($validated['use_ai'] ?? false) {
                $aiInterpretation = $this->interpreter->interpret($matchingCabinet, $score);
            }

            // Map to cabinet data
            $mapped = $this->mapper->mapToCabinetData($matchingCabinet, [
                'project_id' => $validated['project_id'] ?? null,
            ]);

            return $this->success([
                'extracted' => $matchingCabinet,
                'mapped' => $mapped,
                'confidence' => $score,
                'ai_interpretation' => $aiInterpretation,
            ], 'Cabinet extracted');
        } catch (\Exception $e) {
            return $this->error('Extraction failed: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Batch extract all cabinets
     *
     * POST /api/v1/rhino/cabinet/extract-all
     */
    public function extractAllCabinets(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => 'nullable|integer|exists:projects_projects,id',
            'use_ai' => 'boolean',
            'auto_import_high_confidence' => 'boolean',
        ]);

        try {
            // Extract cabinets
            $extractedData = $this->extractor->extractCabinets();
            $cabinets = $extractedData['cabinets'] ?? [];

            if (empty($cabinets)) {
                return $this->success([
                    'cabinets' => [],
                    'summary' => ['total' => 0],
                ], 'No cabinets found in document');
            }

            // Score all cabinets
            $scoredCabinets = $this->scorer->batchCalculate($cabinets);

            // Map all cabinets
            $mapped = $this->mapper->mapAllCabinets($extractedData, [
                'project_id' => $validated['project_id'] ?? null,
            ]);

            // Generate preview report
            $report = $this->mapper->generatePreviewReport($mapped);

            return $this->success([
                'cabinets' => $mapped['cabinets'],
                'scores' => $scoredCabinets,
                'report' => $report,
                'fixtures' => $extractedData['fixtures'] ?? [],
                'views' => $extractedData['views'] ?? [],
            ], 'All cabinets extracted');
        } catch (\Exception $e) {
            return $this->error('Batch extraction failed: ' . $e->getMessage(), null, 500);
        }
    }

    // =========================================================================
    // Job Management
    // =========================================================================

    /**
     * List extraction jobs
     *
     * GET /api/v1/extraction/jobs
     */
    public function listJobs(Request $request): JsonResponse
    {
        $query = RhinoExtractionJob::query()
            ->with(['project:id,name', 'user:id,name'])
            ->orderBy('created_at', 'desc');

        // Filters
        if ($request->has('project_id')) {
            $query->where('project_id', $request->project_id);
        }
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $perPage = min($request->get('per_page', 20), 100);
        $jobs = $query->paginate($perPage);

        return $this->paginated($jobs, 'Extraction jobs retrieved');
    }

    /**
     * Get job status and results
     *
     * GET /api/v1/extraction/jobs/{id}
     */
    public function getJob(int $id): JsonResponse
    {
        $job = RhinoExtractionJob::with(['project:id,name', 'user:id,name', 'reviews'])
            ->find($id);

        if (!$job) {
            return $this->notFound('Extraction job not found');
        }

        return $this->success([
            'job' => $job->getSummary(),
            'reviews' => $job->reviews->map(fn($r) => $r->getSummary()),
            'results' => $job->results,
        ], 'Job retrieved');
    }

    // =========================================================================
    // Review Queue
    // =========================================================================

    /**
     * Get items needing review
     *
     * GET /api/v1/extraction/review-queue
     */
    public function getReviewQueue(Request $request): JsonResponse
    {
        $query = RhinoExtractionReview::query()
            ->with(['project:id,name', 'extractionJob:id,uuid,status'])
            ->orderBy('created_at', 'desc');

        // Default to pending
        $status = $request->get('status', 'pending');
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // Filters
        if ($request->has('project_id')) {
            $query->where('project_id', $request->project_id);
        }
        if ($request->has('review_type')) {
            $query->where('review_type', $request->review_type);
        }
        if ($request->has('min_confidence')) {
            $query->where('confidence_score', '>=', $request->min_confidence);
        }
        if ($request->has('max_confidence')) {
            $query->where('confidence_score', '<=', $request->max_confidence);
        }

        $perPage = min($request->get('per_page', 20), 100);
        $reviews = $query->paginate($perPage);

        return $this->paginated($reviews, 'Review queue retrieved');
    }

    /**
     * Get single review item
     *
     * GET /api/v1/extraction/review/{id}
     */
    public function getReview(int $id): JsonResponse
    {
        $review = RhinoExtractionReview::with([
            'project:id,name',
            'extractionJob:id,uuid,status',
            'cabinet',
            'reviewer:id,name',
        ])->find($id);

        if (!$review) {
            return $this->notFound('Review item not found');
        }

        return $this->success($review->getSummary(), 'Review item retrieved');
    }

    /**
     * Get interpretation context for Claude Code MCP
     *
     * GET /api/v1/extraction/review/{id}/interpretation-context
     *
     * Returns the cabinet data, confidence scores, and prompt for Claude Code
     * to interpret ambiguous dimensions. The AI processing happens in Claude Code,
     * not via direct API calls.
     */
    public function getInterpretationContext(int $id): JsonResponse
    {
        $review = RhinoExtractionReview::with(['project.constructionTemplate'])->find($id);

        if (!$review) {
            return $this->notFound('Review item not found');
        }

        $extractedData = $review->extraction_data;
        $score = [
            'total' => $review->confidence_score,
            'details' => $review->ai_interpretation['original_score'] ?? [],
        ];
        $templateId = $review->project?->constructionTemplate?->id;

        $context = $this->interpreter->prepareContext($extractedData, $score, $templateId);

        return $this->success([
            'review_id' => $review->id,
            'cabinet_name' => $review->cabinet_number ?? $review->rhino_group_name,
            'confidence_score' => $review->confidence_score,
            'confidence_level' => $review->confidence_level,
            'context' => $context,
        ], 'Interpretation context prepared for Claude Code');
    }

    /**
     * Save AI interpretation results from Claude Code
     *
     * POST /api/v1/extraction/review/{id}/save-interpretation
     *
     * Called by Claude Code (via MCP) after processing the interpretation prompt.
     * Stores the AI analysis and may auto-approve if confidence improves.
     */
    public function saveInterpretation(Request $request, int $id): JsonResponse
    {
        $review = RhinoExtractionReview::find($id);

        if (!$review) {
            return $this->notFound('Review item not found');
        }

        $validated = $request->validate([
            'interpretation' => 'required|array',
            'interpretation.interpretation.cabinet_type' => 'nullable|string',
            'interpretation.interpretation.corrected_dimensions' => 'nullable|array',
            'interpretation.component_analysis' => 'nullable|array',
            'interpretation.improved_confidence' => 'nullable|array',
            'interpretation.improved_confidence.score' => 'nullable|numeric|min:0|max:100',
            'interpretation.warnings' => 'nullable|array',
            'interpretation.recommendation' => 'nullable|in:approve,review,reject',
            'auto_apply' => 'boolean',
        ]);

        $interpretation = $validated['interpretation'];

        // Store AI interpretation
        $review->ai_interpretation = $interpretation;

        // Update confidence if improved
        $newScore = $interpretation['improved_confidence']['score'] ?? null;
        if ($newScore && $newScore > $review->confidence_score) {
            $review->confidence_score = $newScore;
        }

        $review->save();

        // Auto-apply if requested and recommendation is approve
        $autoApplied = false;
        if (
            ($validated['auto_apply'] ?? false) &&
            ($interpretation['recommendation'] ?? 'review') === 'approve' &&
            $review->isPending()
        ) {
            $corrections = $interpretation['interpretation']['corrected_dimensions'] ?? [];
            $review->approve(
                $request->user()->id,
                $corrections,
                'Auto-approved via Claude Code interpretation'
            );
            $autoApplied = true;
        }

        $this->logActivity('rhino.interpretation_saved', [
            'review_id' => $review->id,
            'new_confidence' => $newScore,
            'recommendation' => $interpretation['recommendation'] ?? null,
            'auto_applied' => $autoApplied,
        ]);

        return $this->success([
            'review' => $review->fresh()->getSummary(),
            'interpretation_saved' => true,
            'auto_applied' => $autoApplied,
            'new_confidence' => $newScore,
        ], 'AI interpretation saved');
    }

    /**
     * Approve review with optional corrections
     *
     * POST /api/v1/extraction/review/{id}/approve
     */
    public function approveReview(Request $request, int $id): JsonResponse
    {
        $review = RhinoExtractionReview::find($id);

        if (!$review) {
            return $this->notFound('Review item not found');
        }

        if (!$review->isPending()) {
            return $this->error('Review has already been processed', null, 400);
        }

        $validated = $request->validate([
            'corrections' => 'nullable|array',
            'corrections.width' => 'nullable|numeric|min:6|max:96',
            'corrections.height' => 'nullable|numeric|min:12|max:108',
            'corrections.depth' => 'nullable|numeric|min:6|max:36',
            'corrections.drawer_count' => 'nullable|integer|min:0',
            'corrections.door_count' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:1000',
            'create_cabinet' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            $review->approve(
                $request->user()->id,
                $validated['corrections'] ?? [],
                $validated['notes'] ?? null
            );

            // Create cabinet if requested
            $cabinet = null;
            if ($validated['create_cabinet'] ?? false) {
                $mergedData = $review->getMergedData();
                $cabinet = $this->mapper->createCabinets(['cabinets' => [$mergedData]], false)->first();

                if ($cabinet) {
                    $review->update(['cabinet_id' => $cabinet->id]);
                }
            }

            DB::commit();

            $this->logActivity('rhino.review_approved', [
                'review_id' => $review->id,
                'cabinet_id' => $cabinet?->id,
            ]);

            return $this->success([
                'review' => $review->fresh()->getSummary(),
                'cabinet' => $cabinet?->toArray(),
            ], 'Review approved');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed to approve review: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Reject review item
     *
     * POST /api/v1/extraction/review/{id}/reject
     */
    public function rejectReview(Request $request, int $id): JsonResponse
    {
        $review = RhinoExtractionReview::find($id);

        if (!$review) {
            return $this->notFound('Review item not found');
        }

        if (!$review->isPending()) {
            return $this->error('Review has already been processed', null, 400);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $review->reject($request->user()->id, $validated['reason']);

        $this->logActivity('rhino.review_rejected', [
            'review_id' => $review->id,
            'reason' => $validated['reason'],
        ]);

        return $this->success($review->fresh()->getSummary(), 'Review rejected');
    }

    // =========================================================================
    // Bidirectional Sync
    // =========================================================================

    /**
     * Push ERP cabinet changes to Rhino
     *
     * POST /api/v1/rhino/sync/push
     */
    public function pushSync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cabinet_id' => 'required|integer|exists:projects_cabinets,id',
        ]);

        $cabinet = \Webkul\Project\Models\Cabinet::find($validated['cabinet_id']);
        if (!$cabinet) {
            return $this->notFound('Cabinet not found');
        }

        $result = $this->syncService->pushToRhino($cabinet);

        if ($result['success']) {
            $this->logActivity('rhino.sync_push', $result);
            return $this->success($result, 'Cabinet pushed to Rhino');
        }

        return $this->error($result['error'] ?? 'Push failed', $result, 500);
    }

    /**
     * Pull Rhino changes to ERP
     *
     * POST /api/v1/rhino/sync/pull
     */
    public function pullSync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => 'required|integer|exists:projects_projects,id',
            'auto_merge' => 'boolean',
            'create_new' => 'boolean',
        ]);

        $result = $this->syncService->pullFromRhino($validated['project_id'], [
            'auto_merge' => $validated['auto_merge'] ?? true,
            'create_new' => $validated['create_new'] ?? false,
        ]);

        if ($result['success']) {
            $this->logActivity('rhino.sync_pull', $result);
            return $this->success($result, 'Rhino data pulled');
        }

        return $this->error($result['error'] ?? 'Pull failed', $result, 500);
    }

    /**
     * Get sync status for a project
     *
     * GET /api/v1/rhino/sync/status
     */
    public function getSyncStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => 'required|integer|exists:projects_projects,id',
        ]);

        $status = $this->syncService->getSyncStatus($validated['project_id']);

        return $this->success($status, 'Sync status retrieved');
    }

    /**
     * Force sync in a specific direction (resolve conflict)
     *
     * POST /api/v1/rhino/sync/force
     */
    public function forceSync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'review_id' => 'required|integer|exists:rhino_extraction_reviews,id',
            'direction' => 'required|in:erp,rhino',
        ]);

        $result = $this->syncService->forceSync(
            $validated['review_id'],
            $validated['direction'],
            $request->user()->id
        );

        if ($result['success']) {
            $this->logActivity('rhino.sync_force', $result);
            return $this->success($result, 'Sync forced successfully');
        }

        return $this->error($result['error'] ?? 'Force sync failed', $result, 500);
    }

    /**
     * Execute RhinoScript Python code via MCP
     *
     * POST /api/v1/rhino/execute-script
     *
     * This proxies script execution to the Rhino MCP server.
     * Used for custom automation and direct Rhino manipulation.
     */
    public function executeScript(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'script' => 'required|string|max:50000',
            'timeout' => 'nullable|integer|min:1|max:300',
        ]);

        try {
            $result = $this->rhinoMcp->executeScript(
                $validated['script'],
                $validated['timeout'] ?? 30
            );

            $this->logActivity('rhino.script_executed', [
                'script_length' => strlen($validated['script']),
                'timeout' => $validated['timeout'] ?? 30,
            ]);

            return $this->success([
                'output' => $result['output'] ?? null,
                'success' => $result['success'] ?? true,
                'execution_time' => $result['execution_time'] ?? null,
            ], 'Script executed');
        } catch (\Exception $e) {
            return $this->error('Script execution failed: ' . $e->getMessage(), null, 500);
        }
    }
}
