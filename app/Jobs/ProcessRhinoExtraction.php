<?php

namespace App\Jobs;

use App\Models\RhinoExtractionJob;
use App\Models\RhinoExtractionReview;
use App\Services\AIDimensionInterpreter;
use App\Services\ExtractionConfidenceScorer;
use App\Services\RhinoDataExtractor;
use App\Services\RhinoToCabinetMapper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ProcessRhinoExtraction - Async job for cabinet extraction from Rhino
 *
 * Features:
 * - 3 retries with exponential backoff (30s, 60s, 120s)
 * - AI interpretation for low-confidence items
 * - Automatic review item creation
 * - Webhook dispatch on completion
 *
 * @author TCS Woodwork
 * @since January 2026
 */
class ProcessRhinoExtraction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum attempts
     */
    public int $tries = 3;

    /**
     * Backoff intervals (seconds)
     */
    public array $backoff = [30, 60, 120];

    /**
     * Timeout (5 minutes for complex documents)
     */
    public int $timeout = 300;

    /**
     * The extraction job record
     */
    protected RhinoExtractionJob $extractionJob;

    /**
     * Create a new job instance
     */
    public function __construct(RhinoExtractionJob $extractionJob)
    {
        $this->extractionJob = $extractionJob;
    }

    /**
     * Execute the job
     */
    public function handle(
        RhinoDataExtractor $extractor,
        RhinoToCabinetMapper $mapper,
        ExtractionConfidenceScorer $scorer,
        AIDimensionInterpreter $interpreter
    ): void {
        Log::info('ProcessRhinoExtraction: Starting job', [
            'job_id' => $this->extractionJob->id,
            'project_id' => $this->extractionJob->project_id,
        ]);

        // Mark as processing
        $this->extractionJob->markAsProcessing();

        try {
            // Get job options
            $options = $this->extractionJob->options ?? [];
            $autoApproveHighConfidence = $options['auto_approve_high_confidence'] ?? true;
            $includeFixtures = $options['include_fixtures'] ?? true;

            // Step 1: Extract cabinets from Rhino
            $extractedData = $extractor->extractCabinets();
            $cabinets = $extractedData['cabinets'] ?? [];

            Log::info('ProcessRhinoExtraction: Extracted cabinets', [
                'count' => count($cabinets),
            ]);

            // Update job with Rhino document info
            $this->extractionJob->update([
                'rhino_metadata' => [
                    'views' => $extractedData['views'] ?? [],
                    'fixtures' => $includeFixtures ? ($extractedData['fixtures'] ?? []) : [],
                    'raw_data' => $extractedData['raw_data'] ?? [],
                ],
            ]);

            if (empty($cabinets)) {
                $this->extractionJob->markAsCompleted([
                    'cabinets' => [],
                    'message' => 'No cabinets found in Rhino document',
                ]);

                $this->dispatchWebhook('rhino.extraction_completed', [
                    'job_id' => $this->extractionJob->id,
                    'cabinets_extracted' => 0,
                ]);

                return;
            }

            // Step 2: Score and process each cabinet
            $processedCabinets = [];
            $reviewsCreated = 0;
            $autoApproved = 0;

            foreach ($cabinets as $index => $cabinet) {
                // Calculate confidence score
                $score = $scorer->calculateScore($cabinet);

                // AI interpretation for medium/low confidence
                $aiInterpretation = null;
                if ($score['level'] !== 'high') {
                    try {
                        $aiInterpretation = $interpreter->interpret(
                            $cabinet,
                            $score,
                            $this->extractionJob->project?->construction_template_id
                        );
                    } catch (\Exception $e) {
                        Log::warning('ProcessRhinoExtraction: AI interpretation failed', [
                            'cabinet' => $cabinet['name'] ?? $index,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                // Map to cabinet data
                $mapped = $mapper->mapToCabinetData($cabinet, [
                    'project_id' => $this->extractionJob->project_id,
                ]);

                // Determine action based on confidence
                if ($score['level'] === 'high' && $autoApproveHighConfidence) {
                    // Auto-approve high confidence
                    $review = $this->createReviewItem($cabinet, $score, $aiInterpretation, $mapped);
                    $review->autoApprove();
                    $autoApproved++;
                } else {
                    // Create review item for manual review
                    $this->createReviewItem($cabinet, $score, $aiInterpretation, $mapped);
                    $reviewsCreated++;
                }

                $processedCabinets[] = [
                    'name' => $cabinet['name'] ?? "Cabinet {$index}",
                    'confidence' => $score['total'],
                    'level' => $score['level'],
                    'has_ai_interpretation' => $aiInterpretation !== null,
                ];
            }

            // Step 3: Mark job as completed
            $this->extractionJob->markAsCompleted([
                'cabinets' => $processedCabinets,
                'fixtures' => $includeFixtures ? ($extractedData['fixtures'] ?? []) : [],
            ]);

            $this->extractionJob->update([
                'cabinets_extracted' => count($cabinets),
                'cabinets_imported' => $autoApproved,
                'cabinets_pending_review' => $reviewsCreated,
            ]);

            Log::info('ProcessRhinoExtraction: Job completed', [
                'job_id' => $this->extractionJob->id,
                'extracted' => count($cabinets),
                'auto_approved' => $autoApproved,
                'pending_review' => $reviewsCreated,
            ]);

            // Dispatch completion webhook
            $this->dispatchWebhook('rhino.extraction_completed', [
                'job_id' => $this->extractionJob->id,
                'uuid' => $this->extractionJob->uuid,
                'project_id' => $this->extractionJob->project_id,
                'cabinets_extracted' => count($cabinets),
                'cabinets_imported' => $autoApproved,
                'cabinets_pending_review' => $reviewsCreated,
            ]);

            // If there are items needing review, dispatch review webhook
            if ($reviewsCreated > 0) {
                $this->dispatchWebhook('rhino.review_required', [
                    'job_id' => $this->extractionJob->id,
                    'pending_count' => $reviewsCreated,
                    'project_id' => $this->extractionJob->project_id,
                    'review_url' => url("/admin/rhino-reviews?job_id={$this->extractionJob->id}"),
                ]);
            }

        } catch (\Exception $e) {
            Log::error('ProcessRhinoExtraction: Job failed', [
                'job_id' => $this->extractionJob->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->extractionJob->markAsFailed($e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            // Dispatch failure webhook
            $this->dispatchWebhook('rhino.extraction_failed', [
                'job_id' => $this->extractionJob->id,
                'error' => $e->getMessage(),
                'project_id' => $this->extractionJob->project_id,
            ]);

            throw $e; // Re-throw for retry logic
        }
    }

    /**
     * Create a review item for a cabinet
     */
    protected function createReviewItem(
        array $cabinet,
        array $score,
        ?array $aiInterpretation,
        array $mapped
    ): RhinoExtractionReview {
        return RhinoExtractionReview::create([
            'extraction_job_id' => $this->extractionJob->id,
            'project_id' => $this->extractionJob->project_id,
            'rhino_group_name' => $cabinet['name'] ?? null,
            'cabinet_number' => $mapped['cabinet_number'] ?? null,
            'extraction_data' => $cabinet,
            'ai_interpretation' => $aiInterpretation,
            'confidence_score' => $score['total'],
            'status' => RhinoExtractionReview::STATUS_PENDING,
            'review_type' => $score['level'] === 'low'
                ? RhinoExtractionReview::TYPE_LOW_CONFIDENCE
                : ($score['level'] === 'medium'
                    ? RhinoExtractionReview::TYPE_DIMENSION_MISMATCH
                    : RhinoExtractionReview::TYPE_LOW_CONFIDENCE),
        ]);
    }

    /**
     * Dispatch webhook event
     */
    protected function dispatchWebhook(string $event, array $data): void
    {
        try {
            DispatchWebhook::dispatch($event, $data);
        } catch (\Exception $e) {
            Log::warning('ProcessRhinoExtraction: Failed to dispatch webhook', [
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessRhinoExtraction: Job permanently failed', [
            'job_id' => $this->extractionJob->id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        $this->extractionJob->markAsFailed(
            "Permanently failed after {$this->attempts()} attempts: {$exception->getMessage()}",
            [
                'attempts' => $this->attempts(),
                'final_exception' => get_class($exception),
            ]
        );

        // Dispatch failure webhook
        $this->dispatchWebhook('rhino.extraction_failed', [
            'job_id' => $this->extractionJob->id,
            'error' => $exception->getMessage(),
            'permanent' => true,
        ]);
    }

    /**
     * Get the tags for the job
     */
    public function tags(): array
    {
        return [
            'rhino-extraction',
            'project:' . ($this->extractionJob->project_id ?? 'none'),
            'job:' . $this->extractionJob->id,
        ];
    }
}
