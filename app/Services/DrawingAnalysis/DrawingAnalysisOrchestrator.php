<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

/**
 * DrawingAnalysisOrchestrator
 *
 * Chains all 10 drawing analysis steps together in sequence.
 * Implements gate enforcement at Step 4 and Step 9.
 * Passes context between steps and accumulates results.
 *
 * FEATURES:
 * - Human intervention support between steps (pause, review, edit, approve)
 * - Debug mode with detailed logging and step-by-step viewing
 * - Callback hooks for each step (before, after, on_error)
 * - Interactive mode for manual step execution
 */
class DrawingAnalysisOrchestrator
{
    // Pipeline status constants
    public const STATUS_NOT_STARTED = 'not_started';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_BLOCKED = 'blocked';
    public const STATUS_PAUSED = 'paused';              // Waiting for human intervention
    public const STATUS_AWAITING_APPROVAL = 'awaiting_approval';  // Step completed, needs approval
    public const STATUS_AWAITING_EDIT = 'awaiting_edit';          // Human editing step data

    // Gate names
    public const GATE_INTENT_VALIDATION = 'intent_validation';
    public const GATE_COMPONENT_EXTRACTION = 'component_extraction';

    // Intervention modes
    public const INTERVENTION_NONE = 'none';           // Automatic - no pausing
    public const INTERVENTION_GATES_ONLY = 'gates';    // Pause only at gates (steps 4, 9)
    public const INTERVENTION_ALL_STEPS = 'all';       // Pause after every step
    public const INTERVENTION_ON_WARNING = 'warnings'; // Pause when warnings occur
    public const INTERVENTION_ON_ERROR = 'errors';     // Pause on errors (allow retry/edit)

    protected DrawingContextAnalyzerService $contextAnalyzer;
    protected DimensionReferenceAnalyzerService $dimensionAnalyzer;
    protected DrawingNotesExtractorService $notesExtractor;
    protected DrawingIntentValidationService $intentValidator;
    protected HierarchicalEntityExtractorService $entityExtractor;
    protected DimensionConsistencyVerifierService $consistencyVerifier;
    protected StandardPracticeAlignmentService $alignmentChecker;
    protected ProductionConstraintDerivationService $constraintDeriver;
    protected ComponentExtractionService $componentExtractor;
    protected VerificationAuditService $auditService;

    protected array $pipelineState = [];
    protected array $stepResults = [];
    protected string $currentStatus = self::STATUS_NOT_STARTED;
    protected ?string $sessionId = null;

    // Human intervention settings
    protected string $interventionMode = self::INTERVENTION_NONE;
    protected ?int $pausedAtStep = null;
    protected array $pendingEdits = [];
    protected array $approvalHistory = [];

    // Debug settings
    protected bool $debugMode = false;
    protected array $debugLog = [];
    protected ?callable $debugCallback = null;

    // Step callbacks (hooks)
    protected array $stepCallbacks = [
        'before' => [],   // Called before each step
        'after' => [],    // Called after each step
        'on_error' => [], // Called on step error
        'on_pause' => [], // Called when pausing for intervention
    ];

    public function __construct(
        DrawingContextAnalyzerService $contextAnalyzer,
        DimensionReferenceAnalyzerService $dimensionAnalyzer,
        DrawingNotesExtractorService $notesExtractor,
        DrawingIntentValidationService $intentValidator,
        HierarchicalEntityExtractorService $entityExtractor,
        DimensionConsistencyVerifierService $consistencyVerifier,
        StandardPracticeAlignmentService $alignmentChecker,
        ProductionConstraintDerivationService $constraintDeriver,
        ComponentExtractionService $componentExtractor,
        VerificationAuditService $auditService
    ) {
        $this->contextAnalyzer = $contextAnalyzer;
        $this->dimensionAnalyzer = $dimensionAnalyzer;
        $this->notesExtractor = $notesExtractor;
        $this->intentValidator = $intentValidator;
        $this->entityExtractor = $entityExtractor;
        $this->consistencyVerifier = $consistencyVerifier;
        $this->alignmentChecker = $alignmentChecker;
        $this->constraintDeriver = $constraintDeriver;
        $this->componentExtractor = $componentExtractor;
        $this->auditService = $auditService;
    }

    /**
     * Run the complete 10-step analysis pipeline
     *
     * @param string|array $drawingInput - Base64 image, file path, or array of images
     * @param array $options - Pipeline options (purposes, skip_steps, etc.)
     * @return array Complete pipeline results
     */
    public function runFullPipeline(string|array $drawingInput, array $options = []): array
    {
        $this->sessionId = $options['session_id'] ?? uniqid('pipeline_', true);
        $this->currentStatus = self::STATUS_IN_PROGRESS;
        $startTime = microtime(true);

        $purposes = $options['purposes'] ?? ['production_modeling', 'cnc_generation', 'material_takeoff'];
        $stopOnGateFailure = $options['stop_on_gate_failure'] ?? true;

        Log::info("Starting drawing analysis pipeline", [
            'session_id' => $this->sessionId,
            'purposes' => $purposes,
        ]);

        try {
            // Initialize pipeline state
            $this->pipelineState = [
                'session_id' => $this->sessionId,
                'started_at' => now()->toIso8601String(),
                'purposes' => $purposes,
                'drawing_input_type' => is_array($drawingInput) ? 'multi_image' : 'single_image',
            ];

            // ========================================
            // STEP 1: Drawing Context Analysis
            // ========================================
            $step1Result = $this->executeStep(1, function() use ($drawingInput) {
                return $this->contextAnalyzer->analyzeDrawingContext($drawingInput);
            });

            if (!$step1Result['success']) {
                return $this->buildFailureResponse(1, $step1Result['error']);
            }

            // ========================================
            // STEP 2: Dimension Reference Analysis
            // ========================================
            $step2Result = $this->executeStep(2, function() use ($drawingInput, $step1Result) {
                return $this->dimensionAnalyzer->analyzeDimensionReferences(
                    $drawingInput,
                    $step1Result['data']
                );
            });

            if (!$step2Result['success']) {
                return $this->buildFailureResponse(2, $step2Result['error']);
            }

            // ========================================
            // STEP 3: Notes & Callout Extraction
            // ========================================
            $step3Result = $this->executeStep(3, function() use ($drawingInput, $step1Result) {
                return $this->notesExtractor->extractNotes(
                    $drawingInput,
                    $step1Result['data']
                );
            });

            if (!$step3Result['success']) {
                return $this->buildFailureResponse(3, $step3Result['error']);
            }

            // ========================================
            // STEP 4: Drawing Intent Validation (GATE)
            // ========================================
            $step4Result = $this->executeStep(4, function() use ($step1Result, $step2Result, $step3Result, $purposes) {
                return $this->intentValidator->validateDrawingIntent(
                    $step1Result['data'],
                    $step2Result['data'],
                    $step3Result['data'],
                    $purposes
                );
            });

            if (!$step4Result['success']) {
                return $this->buildFailureResponse(4, $step4Result['error']);
            }

            // Check gate
            $canProceed = $step4Result['data']['can_proceed']['extraction_allowed'] ?? false;
            if (!$canProceed && $stopOnGateFailure) {
                $this->currentStatus = self::STATUS_BLOCKED;
                return $this->buildGateBlockedResponse(
                    self::GATE_INTENT_VALIDATION,
                    $step4Result['data']['blockers'] ?? []
                );
            }

            // ========================================
            // STEP 5: Hierarchical Entity Extraction
            // ========================================
            $step5Result = $this->executeStep(5, function() use ($drawingInput, $step1Result, $step2Result, $step3Result) {
                return $this->entityExtractor->extractEntities(
                    $drawingInput,
                    $step1Result['data'],
                    $step2Result['data'],
                    $step3Result['data']
                );
            });

            if (!$step5Result['success']) {
                return $this->buildFailureResponse(5, $step5Result['error']);
            }

            // ========================================
            // STEP 6: Dimension Consistency Verification
            // ========================================
            $step6Result = $this->executeStep(6, function() use ($step2Result, $step5Result) {
                return $this->consistencyVerifier->verifyConsistency(
                    $step2Result['data'],
                    $step5Result['data']
                );
            });

            if (!$step6Result['success']) {
                return $this->buildFailureResponse(6, $step6Result['error']);
            }

            // ========================================
            // STEP 7: Standard Practice Alignment
            // ========================================
            $step7Result = $this->executeStep(7, function() use ($step5Result, $step6Result) {
                return $this->alignmentChecker->checkAlignment(
                    $step5Result['data'],
                    $step6Result['data']
                );
            });

            if (!$step7Result['success']) {
                return $this->buildFailureResponse(7, $step7Result['error']);
            }

            // ========================================
            // STEP 8: Production Constraint Derivation
            // ========================================
            $step8Result = $this->executeStep(8, function() use ($step3Result, $step6Result, $step7Result) {
                return $this->constraintDeriver->deriveConstraints(
                    $step3Result['data'],
                    $step6Result['data'],
                    $step7Result['data']
                );
            });

            if (!$step8Result['success']) {
                return $this->buildFailureResponse(8, $step8Result['error']);
            }

            // ========================================
            // STEP 9: Component Extraction (GATE)
            // ========================================
            // Check if all prior steps passed before component extraction
            $priorStepsPassed = $this->verifyPriorSteps([1, 2, 3, 4, 5, 6, 7, 8]);
            if (!$priorStepsPassed && $stopOnGateFailure) {
                $this->currentStatus = self::STATUS_BLOCKED;
                return $this->buildGateBlockedResponse(
                    self::GATE_COMPONENT_EXTRACTION,
                    ['Prior steps must pass before component extraction']
                );
            }

            $step9Result = $this->executeStep(9, function() use ($drawingInput, $step5Result, $step6Result, $step8Result) {
                return $this->componentExtractor->extractComponents(
                    $drawingInput,
                    $step5Result['data'],
                    $step6Result['data'],
                    $step8Result['data']
                );
            });

            if (!$step9Result['success']) {
                return $this->buildFailureResponse(9, $step9Result['error']);
            }

            // ========================================
            // STEP 10: Verification & Audit
            // ========================================
            $step10Result = $this->executeStep(10, function() use ($purposes) {
                return $this->auditService->generateAuditReport(
                    $this->stepResults,
                    $purposes
                );
            });

            if (!$step10Result['success']) {
                return $this->buildFailureResponse(10, $step10Result['error']);
            }

            // ========================================
            // Build Complete Response
            // ========================================
            $this->currentStatus = self::STATUS_COMPLETED;
            $endTime = microtime(true);

            return $this->buildSuccessResponse($endTime - $startTime);

        } catch (\Exception $e) {
            Log::error("Pipeline failed with exception", [
                'session_id' => $this->sessionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->currentStatus = self::STATUS_FAILED;
            return [
                'success' => false,
                'session_id' => $this->sessionId,
                'status' => self::STATUS_FAILED,
                'error' => $e->getMessage(),
                'completed_steps' => array_keys($this->stepResults),
                'step_results' => $this->stepResults,
            ];
        }
    }

    /**
     * Run a single step with error handling and logging
     */
    protected function executeStep(int $stepNumber, callable $executor): array
    {
        $stepName = $this->getStepName($stepNumber);
        $startTime = microtime(true);

        Log::info("Executing pipeline step", [
            'session_id' => $this->sessionId,
            'step' => $stepNumber,
            'name' => $stepName,
        ]);

        try {
            $result = $executor();
            $endTime = microtime(true);

            $this->stepResults["step_{$stepNumber}"] = [
                'step_number' => $stepNumber,
                'step_name' => $stepName,
                'status' => 'passed',
                'duration_ms' => round(($endTime - $startTime) * 1000, 2),
                'data' => $result,
            ];

            // Cache step result for potential resume
            $this->cacheStepResult($stepNumber, $result);

            return [
                'success' => true,
                'data' => $result,
            ];

        } catch (\Exception $e) {
            $endTime = microtime(true);

            $this->stepResults["step_{$stepNumber}"] = [
                'step_number' => $stepNumber,
                'step_name' => $stepName,
                'status' => 'failed',
                'duration_ms' => round(($endTime - $startTime) * 1000, 2),
                'error' => $e->getMessage(),
            ];

            Log::error("Pipeline step failed", [
                'session_id' => $this->sessionId,
                'step' => $stepNumber,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Run pipeline starting from a specific step (resume capability)
     */
    public function resumeFromStep(int $startStep, string|array $drawingInput, array $options = []): array
    {
        $cachedSessionId = $options['session_id'] ?? null;

        if ($cachedSessionId && $startStep > 1) {
            // Load cached results from previous steps
            for ($i = 1; $i < $startStep; $i++) {
                $cached = $this->getCachedStepResult($cachedSessionId, $i);
                if ($cached) {
                    $this->stepResults["step_{$i}"] = [
                        'step_number' => $i,
                        'step_name' => $this->getStepName($i),
                        'status' => 'passed',
                        'data' => $cached,
                        'source' => 'cached',
                    ];
                }
            }
        }

        // Continue with remaining steps...
        // This would need to be expanded based on which step we're resuming from
        return $this->runFullPipeline($drawingInput, array_merge($options, [
            'skip_steps' => range(1, $startStep - 1),
        ]));
    }

    /**
     * Run only specific steps (for testing or partial analysis)
     */
    public function runSteps(array $stepNumbers, string|array $drawingInput, array $priorResults = []): array
    {
        $this->sessionId = uniqid('partial_', true);
        $results = [];

        // Load prior results
        foreach ($priorResults as $stepNum => $data) {
            $this->stepResults["step_{$stepNum}"] = [
                'step_number' => $stepNum,
                'step_name' => $this->getStepName($stepNum),
                'status' => 'passed',
                'data' => $data,
                'source' => 'provided',
            ];
        }

        foreach ($stepNumbers as $stepNum) {
            $result = $this->runSingleStep($stepNum, $drawingInput);
            $results["step_{$stepNum}"] = $result;
        }

        return [
            'session_id' => $this->sessionId,
            'requested_steps' => $stepNumbers,
            'results' => $results,
        ];
    }

    /**
     * Run a single step standalone
     */
    public function runSingleStep(int $stepNumber, string|array $drawingInput): array
    {
        $stepName = $this->getStepName($stepNumber);

        switch ($stepNumber) {
            case 1:
                return $this->contextAnalyzer->analyzeDrawingContext($drawingInput);
            case 2:
                $context = $this->getStepData(1);
                return $this->dimensionAnalyzer->analyzeDimensionReferences($drawingInput, $context);
            case 3:
                $context = $this->getStepData(1);
                return $this->notesExtractor->extractNotes($drawingInput, $context);
            case 4:
                return $this->intentValidator->validateDrawingIntent(
                    $this->getStepData(1),
                    $this->getStepData(2),
                    $this->getStepData(3),
                    ['production_modeling']
                );
            case 5:
                return $this->entityExtractor->extractEntities(
                    $drawingInput,
                    $this->getStepData(1),
                    $this->getStepData(2),
                    $this->getStepData(3)
                );
            case 6:
                return $this->consistencyVerifier->verifyConsistency(
                    $this->getStepData(2),
                    $this->getStepData(5)
                );
            case 7:
                return $this->alignmentChecker->checkAlignment(
                    $this->getStepData(5),
                    $this->getStepData(6)
                );
            case 8:
                return $this->constraintDeriver->deriveConstraints(
                    $this->getStepData(3),
                    $this->getStepData(6),
                    $this->getStepData(7)
                );
            case 9:
                return $this->componentExtractor->extractComponents(
                    $drawingInput,
                    $this->getStepData(5),
                    $this->getStepData(6),
                    $this->getStepData(8)
                );
            case 10:
                return $this->auditService->generateAuditReport(
                    $this->stepResults,
                    ['production_modeling']
                );
            default:
                throw new \InvalidArgumentException("Invalid step number: {$stepNumber}");
        }
    }

    /**
     * Get data from a completed step
     */
    protected function getStepData(int $stepNumber): ?array
    {
        return $this->stepResults["step_{$stepNumber}"]['data'] ?? null;
    }

    /**
     * Verify all prior steps passed
     */
    protected function verifyPriorSteps(array $requiredSteps): bool
    {
        foreach ($requiredSteps as $stepNum) {
            $stepKey = "step_{$stepNum}";
            if (!isset($this->stepResults[$stepKey]) ||
                $this->stepResults[$stepKey]['status'] !== 'passed') {
                return false;
            }
        }
        return true;
    }

    /**
     * Get step name by number
     */
    protected function getStepName(int $stepNumber): string
    {
        return match($stepNumber) {
            1 => 'Drawing Context Analysis',
            2 => 'Dimension Reference Analysis',
            3 => 'Notes & Callout Extraction',
            4 => 'Drawing Intent Validation',
            5 => 'Hierarchical Entity Extraction',
            6 => 'Dimension Consistency Verification',
            7 => 'Standard Practice Alignment',
            8 => 'Production Constraint Derivation',
            9 => 'Component Extraction',
            10 => 'Verification & Audit',
            default => "Unknown Step {$stepNumber}",
        };
    }

    /**
     * Cache step result for resume capability
     */
    protected function cacheStepResult(int $stepNumber, array $result): void
    {
        $cacheKey = "pipeline:{$this->sessionId}:step_{$stepNumber}";
        Cache::put($cacheKey, $result, now()->addHours(24));
    }

    /**
     * Get cached step result
     */
    protected function getCachedStepResult(string $sessionId, int $stepNumber): ?array
    {
        $cacheKey = "pipeline:{$sessionId}:step_{$stepNumber}";
        return Cache::get($cacheKey);
    }

    /**
     * Build success response
     */
    protected function buildSuccessResponse(float $totalDuration): array
    {
        $auditData = $this->getStepData(10);

        return [
            'success' => true,
            'session_id' => $this->sessionId,
            'status' => self::STATUS_COMPLETED,
            'total_duration_seconds' => round($totalDuration, 2),
            'verification_level' => $auditData['verification_level']['level'] ?? 'UNKNOWN',
            'readiness' => $auditData['readiness'] ?? [],
            'pipeline_state' => $this->pipelineState,
            'step_results' => $this->stepResults,
            'summary' => $auditData['summary'] ?? null,
            'assumptions' => $auditData['assumptions'] ?? [],
            'recommendations' => $auditData['recommendations'] ?? [],
        ];
    }

    /**
     * Build failure response
     */
    protected function buildFailureResponse(int $failedStep, string $error): array
    {
        return [
            'success' => false,
            'session_id' => $this->sessionId,
            'status' => self::STATUS_FAILED,
            'failed_at_step' => $failedStep,
            'failed_step_name' => $this->getStepName($failedStep),
            'error' => $error,
            'completed_steps' => array_keys(array_filter($this->stepResults, fn($r) => $r['status'] === 'passed')),
            'step_results' => $this->stepResults,
        ];
    }

    /**
     * Build gate blocked response
     */
    protected function buildGateBlockedResponse(string $gateName, array $blockers): array
    {
        return [
            'success' => false,
            'session_id' => $this->sessionId,
            'status' => self::STATUS_BLOCKED,
            'blocked_by_gate' => $gateName,
            'blockers' => $blockers,
            'completed_steps' => array_keys(array_filter($this->stepResults, fn($r) => $r['status'] === 'passed')),
            'step_results' => $this->stepResults,
            'message' => "Pipeline blocked at gate: {$gateName}. Resolve blockers before proceeding.",
        ];
    }

    /**
     * Get current pipeline status
     */
    public function getStatus(): string
    {
        return $this->currentStatus;
    }

    /**
     * Get session ID
     */
    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }

    /**
     * Get all step results
     */
    public function getStepResults(): array
    {
        return $this->stepResults;
    }

    /**
     * Export pipeline results to JSON for database persistence
     */
    public function exportForPersistence(): array
    {
        return [
            'session_id' => $this->sessionId,
            'status' => $this->currentStatus,
            'pipeline_state' => $this->pipelineState,
            'step_results' => $this->stepResults,
            'extracted_data' => [
                'context' => $this->getStepData(1),
                'dimensions' => $this->getStepData(2),
                'notes' => $this->getStepData(3),
                'validation' => $this->getStepData(4),
                'entities' => $this->getStepData(5),
                'verification' => $this->getStepData(6),
                'alignment' => $this->getStepData(7),
                'constraints' => $this->getStepData(8),
                'components' => $this->getStepData(9),
                'audit' => $this->getStepData(10),
            ],
        ];
    }

    // =========================================================================
    // HUMAN INTERVENTION METHODS
    // =========================================================================

    /**
     * Set the intervention mode for the pipeline
     *
     * @param string $mode One of INTERVENTION_* constants
     * @return self
     */
    public function setInterventionMode(string $mode): self
    {
        $validModes = [
            self::INTERVENTION_NONE,
            self::INTERVENTION_GATES_ONLY,
            self::INTERVENTION_ALL_STEPS,
            self::INTERVENTION_ON_WARNING,
            self::INTERVENTION_ON_ERROR,
        ];

        if (!in_array($mode, $validModes)) {
            throw new \InvalidArgumentException("Invalid intervention mode: {$mode}");
        }

        $this->interventionMode = $mode;
        $this->debug("Intervention mode set to: {$mode}");

        return $this;
    }

    /**
     * Run pipeline with interactive/intervention mode
     * Returns after each step that requires intervention
     *
     * @param string|array $drawingInput
     * @param array $options
     * @return array
     */
    public function runInteractive(string|array $drawingInput, array $options = []): array
    {
        $this->sessionId = $options['session_id'] ?? uniqid('interactive_', true);
        $this->interventionMode = $options['intervention_mode'] ?? self::INTERVENTION_ALL_STEPS;

        // Store drawing input for resume
        $this->cacheDrawingInput($drawingInput);

        return $this->executeNextStep($drawingInput, 1, $options);
    }

    /**
     * Execute the next step in interactive mode
     * Returns state after step completion for human review
     */
    public function executeNextStep(string|array $drawingInput, int $stepNumber, array $options = []): array
    {
        if ($stepNumber > 10) {
            $this->currentStatus = self::STATUS_COMPLETED;
            return $this->buildSuccessResponse(0);
        }

        $this->currentStatus = self::STATUS_IN_PROGRESS;
        $this->debug("Executing step {$stepNumber}: " . $this->getStepName($stepNumber));

        // Execute callbacks before step
        $this->executeCallbacks('before', $stepNumber);

        // Run the step
        $result = $this->executeStep($stepNumber, function() use ($drawingInput, $stepNumber) {
            return $this->runSingleStep($stepNumber, $drawingInput);
        });

        // Execute callbacks after step
        $this->executeCallbacks('after', $stepNumber, $result);

        // Check if we should pause for intervention
        $shouldPause = $this->shouldPauseAfterStep($stepNumber, $result);

        if ($shouldPause) {
            $this->currentStatus = self::STATUS_AWAITING_APPROVAL;
            $this->pausedAtStep = $stepNumber;

            // Execute pause callbacks
            $this->executeCallbacks('on_pause', $stepNumber, $result);

            return $this->buildPausedResponse($stepNumber, $result);
        }

        // Check for gate blocks
        if ($stepNumber === 4 && !($result['data']['can_proceed']['extraction_allowed'] ?? true)) {
            $this->currentStatus = self::STATUS_BLOCKED;
            return $this->buildGateBlockedResponse(self::GATE_INTENT_VALIDATION, $result['data']['blockers'] ?? []);
        }

        // Continue to next step if not pausing
        return $this->executeNextStep($drawingInput, $stepNumber + 1, $options);
    }

    /**
     * Resume pipeline after human intervention
     *
     * @param string $sessionId - Session to resume
     * @param string $action - 'approve', 'edit', 'retry', 'skip'
     * @param array $editedData - If action is 'edit', the modified data
     * @return array
     */
    public function resumeAfterIntervention(string $sessionId, string $action, array $editedData = []): array
    {
        // Load session state
        $this->sessionId = $sessionId;
        $this->loadSessionState();

        $currentStep = $this->pausedAtStep;

        if (!$currentStep) {
            return ['error' => 'No paused step found for this session'];
        }

        $this->debug("Resuming from step {$currentStep} with action: {$action}");

        // Record approval/action
        $this->approvalHistory[] = [
            'step' => $currentStep,
            'action' => $action,
            'timestamp' => now()->toIso8601String(),
            'had_edits' => !empty($editedData),
        ];

        switch ($action) {
            case 'approve':
                // Continue to next step
                $drawingInput = $this->getCachedDrawingInput();
                $this->pausedAtStep = null;
                return $this->executeNextStep($drawingInput, $currentStep + 1, []);

            case 'edit':
                // Apply edits and re-run step
                $this->applyStepEdits($currentStep, $editedData);
                $this->stepResults["step_{$currentStep}"]['data'] = $editedData;
                $this->stepResults["step_{$currentStep}"]['edited'] = true;
                $this->stepResults["step_{$currentStep}"]['edit_timestamp'] = now()->toIso8601String();

                // Cache edited result
                $this->cacheStepResult($currentStep, $editedData);

                // Continue to next step
                $drawingInput = $this->getCachedDrawingInput();
                $this->pausedAtStep = null;
                return $this->executeNextStep($drawingInput, $currentStep + 1, []);

            case 'retry':
                // Re-run the current step
                $drawingInput = $this->getCachedDrawingInput();
                $this->pausedAtStep = null;
                return $this->executeNextStep($drawingInput, $currentStep, []);

            case 'skip':
                // Skip to next step (mark current as skipped)
                $this->stepResults["step_{$currentStep}"]['status'] = 'skipped';
                $this->stepResults["step_{$currentStep}"]['skipped_at'] = now()->toIso8601String();

                $drawingInput = $this->getCachedDrawingInput();
                $this->pausedAtStep = null;
                return $this->executeNextStep($drawingInput, $currentStep + 1, []);

            case 'abort':
                $this->currentStatus = self::STATUS_FAILED;
                return [
                    'success' => false,
                    'session_id' => $this->sessionId,
                    'status' => 'aborted',
                    'aborted_at_step' => $currentStep,
                    'step_results' => $this->stepResults,
                ];

            default:
                return ['error' => "Unknown action: {$action}"];
        }
    }

    /**
     * Get current step data for human review/editing
     */
    public function getStepForReview(int $stepNumber): array
    {
        $stepKey = "step_{$stepNumber}";

        if (!isset($this->stepResults[$stepKey])) {
            return ['error' => "Step {$stepNumber} has not been executed yet"];
        }

        $stepData = $this->stepResults[$stepKey];

        return [
            'step_number' => $stepNumber,
            'step_name' => $this->getStepName($stepNumber),
            'status' => $stepData['status'] ?? 'unknown',
            'duration_ms' => $stepData['duration_ms'] ?? null,
            'data' => $stepData['data'] ?? null,
            'editable_fields' => $this->getEditableFields($stepNumber),
            'validation_rules' => $this->getValidationRules($stepNumber),
            'help_text' => $this->getStepHelpText($stepNumber),
        ];
    }

    /**
     * Update specific fields in a step's data
     */
    public function updateStepData(int $stepNumber, array $updates): array
    {
        $stepKey = "step_{$stepNumber}";

        if (!isset($this->stepResults[$stepKey])) {
            return ['error' => "Step {$stepNumber} has not been executed yet"];
        }

        // Deep merge updates into existing data
        $existingData = $this->stepResults[$stepKey]['data'] ?? [];
        $mergedData = array_replace_recursive($existingData, $updates);

        $this->stepResults[$stepKey]['data'] = $mergedData;
        $this->stepResults[$stepKey]['edited'] = true;
        $this->stepResults[$stepKey]['last_edit'] = now()->toIso8601String();

        // Cache updated result
        $this->cacheStepResult($stepNumber, $mergedData);

        $this->debug("Step {$stepNumber} data updated", $updates);

        return [
            'success' => true,
            'step_number' => $stepNumber,
            'updated_data' => $mergedData,
        ];
    }

    /**
     * Check if intervention should occur after this step
     */
    protected function shouldPauseAfterStep(int $stepNumber, array $result): bool
    {
        switch ($this->interventionMode) {
            case self::INTERVENTION_NONE:
                return false;

            case self::INTERVENTION_ALL_STEPS:
                return true;

            case self::INTERVENTION_GATES_ONLY:
                return in_array($stepNumber, [4, 9]);

            case self::INTERVENTION_ON_WARNING:
                // Check if result has warnings
                $hasWarnings = !empty($result['data']['warnings'] ?? [])
                    || !empty($result['data']['flags'] ?? [])
                    || !empty($result['data']['discrepancies'] ?? []);
                return $hasWarnings;

            case self::INTERVENTION_ON_ERROR:
                return !($result['success'] ?? true);

            default:
                return false;
        }
    }

    /**
     * Build response for paused state
     */
    protected function buildPausedResponse(int $stepNumber, array $result): array
    {
        return [
            'success' => true,
            'session_id' => $this->sessionId,
            'status' => self::STATUS_AWAITING_APPROVAL,
            'paused_at_step' => $stepNumber,
            'step_name' => $this->getStepName($stepNumber),
            'step_result' => $result,
            'available_actions' => ['approve', 'edit', 'retry', 'skip', 'abort'],
            'editable_fields' => $this->getEditableFields($stepNumber),
            'completed_steps' => array_keys(array_filter($this->stepResults, fn($r) => ($r['status'] ?? '') === 'passed')),
            'next_step' => $stepNumber < 10 ? [
                'number' => $stepNumber + 1,
                'name' => $this->getStepName($stepNumber + 1),
            ] : null,
            'message' => "Step {$stepNumber} ({$this->getStepName($stepNumber)}) completed. Review and approve to continue.",
        ];
    }

    /**
     * Get editable fields for a step
     */
    protected function getEditableFields(int $stepNumber): array
    {
        return match($stepNumber) {
            1 => ['view_type', 'orientation', 'drawing_intent', 'unit_system', 'scale', 'baselines'],
            2 => ['dimensions', 'potential_conflicts'],
            3 => ['notes', 'title_block'],
            4 => ['suitability', 'blockers', 'can_proceed'],
            5 => ['entities.project', 'entities.rooms', 'entities.locations', 'entities.cabinet_runs', 'entities.cabinets', 'entities.sections'],
            6 => ['cabinet_verifications', 'discrepancies'],
            7 => ['practice_evaluations', 'flags', 'custom_elements'],
            8 => ['constraints'],
            9 => ['components'],
            10 => ['verification_level', 'assumptions', 'recommendations'],
            default => [],
        };
    }

    /**
     * Get validation rules for step fields
     */
    protected function getValidationRules(int $stepNumber): array
    {
        return match($stepNumber) {
            5 => [
                'cabinets.*.bounding_geometry.width.numeric' => 'required|numeric|min:6|max:60',
                'cabinets.*.bounding_geometry.height.numeric' => 'required|numeric|min:12|max:108',
            ],
            9 => [
                'components.*.dimensions.width.value' => 'numeric|min:1',
                'components.*.dimensions.height.value' => 'numeric|min:1',
            ],
            default => [],
        };
    }

    /**
     * Get help text for reviewing a step
     */
    protected function getStepHelpText(int $stepNumber): string
    {
        return match($stepNumber) {
            1 => "Review the identified view type, orientation, and drawing intent. Ensure the baseline (reference point) is correct.",
            2 => "Check that all dimensions are correctly identified and their reference points are accurate.",
            3 => "Verify all notes and callouts were extracted. Check material specs, hardware notes, and special instructions.",
            4 => "GATE: This step determines if the drawing has enough information for production. Review any blockers.",
            5 => "Review the extracted entity hierarchy: Project → Room → Location → Run → Cabinet → Section. Add missing entities or correct relationships.",
            6 => "Verify dimension math: vertical and horizontal stack-ups should reconcile. Check for any discrepancies.",
            7 => "Review standard practice alignment. Flag any non-standard practices that need special attention.",
            8 => "Review derived production constraints. Verify gap standards, material thicknesses, and reference surfaces.",
            9 => "GATE: Review all extracted components (drawers, doors, shelves, stretchers). Verify dimensions and derivations.",
            10 => "Final audit report. Review assumptions and recommendations before proceeding to database persistence.",
            default => "Review step output and approve to continue.",
        };
    }

    // =========================================================================
    // DEBUG & LOGGING METHODS
    // =========================================================================

    /**
     * Enable debug mode
     */
    public function enableDebug(?callable $callback = null): self
    {
        $this->debugMode = true;
        $this->debugCallback = $callback;
        $this->debug("Debug mode enabled");

        return $this;
    }

    /**
     * Disable debug mode
     */
    public function disableDebug(): self
    {
        $this->debugMode = false;
        $this->debugCallback = null;

        return $this;
    }

    /**
     * Log debug message
     */
    protected function debug(string $message, array $context = []): void
    {
        if (!$this->debugMode) {
            return;
        }

        $entry = [
            'timestamp' => now()->toIso8601String(),
            'session_id' => $this->sessionId,
            'message' => $message,
            'context' => $context,
        ];

        $this->debugLog[] = $entry;

        // Call custom callback if set
        if ($this->debugCallback) {
            call_user_func($this->debugCallback, $entry);
        }

        // Also log to Laravel log
        Log::debug("[DrawingAnalysis] {$message}", $context);
    }

    /**
     * Get full debug log
     */
    public function getDebugLog(): array
    {
        return $this->debugLog;
    }

    /**
     * Get formatted debug output for display
     */
    public function getFormattedDebugLog(): string
    {
        $output = "=== Drawing Analysis Debug Log ===\n";
        $output .= "Session: {$this->sessionId}\n";
        $output .= "Status: {$this->currentStatus}\n";
        $output .= "Intervention Mode: {$this->interventionMode}\n\n";

        foreach ($this->debugLog as $entry) {
            $output .= "[{$entry['timestamp']}] {$entry['message']}\n";
            if (!empty($entry['context'])) {
                $output .= "  Context: " . json_encode($entry['context'], JSON_PRETTY_PRINT) . "\n";
            }
        }

        return $output;
    }

    // =========================================================================
    // CALLBACK/HOOK METHODS
    // =========================================================================

    /**
     * Register a callback for step events
     *
     * @param string $event - 'before', 'after', 'on_error', 'on_pause'
     * @param callable $callback - function(int $stepNumber, ?array $result)
     * @param int|null $stepNumber - Specific step, or null for all steps
     */
    public function onStep(string $event, callable $callback, ?int $stepNumber = null): self
    {
        $key = $stepNumber ?? 'all';
        $this->stepCallbacks[$event][$key] = $callback;

        return $this;
    }

    /**
     * Execute callbacks for an event
     */
    protected function executeCallbacks(string $event, int $stepNumber, ?array $result = null): void
    {
        // Execute step-specific callback
        if (isset($this->stepCallbacks[$event][$stepNumber])) {
            call_user_func($this->stepCallbacks[$event][$stepNumber], $stepNumber, $result);
        }

        // Execute global callback
        if (isset($this->stepCallbacks[$event]['all'])) {
            call_user_func($this->stepCallbacks[$event]['all'], $stepNumber, $result);
        }

        // Fire Laravel event
        Event::dispatch("drawing-analysis.{$event}", [$this->sessionId, $stepNumber, $result]);
    }

    // =========================================================================
    // SESSION STATE METHODS
    // =========================================================================

    /**
     * Save current session state to cache
     */
    protected function saveSessionState(): void
    {
        $state = [
            'session_id' => $this->sessionId,
            'status' => $this->currentStatus,
            'intervention_mode' => $this->interventionMode,
            'paused_at_step' => $this->pausedAtStep,
            'step_results' => $this->stepResults,
            'pipeline_state' => $this->pipelineState,
            'approval_history' => $this->approvalHistory,
            'debug_log' => $this->debugLog,
            'saved_at' => now()->toIso8601String(),
        ];

        Cache::put("pipeline_state:{$this->sessionId}", $state, now()->addHours(48));
    }

    /**
     * Load session state from cache
     */
    protected function loadSessionState(): void
    {
        $state = Cache::get("pipeline_state:{$this->sessionId}");

        if ($state) {
            $this->currentStatus = $state['status'] ?? self::STATUS_NOT_STARTED;
            $this->interventionMode = $state['intervention_mode'] ?? self::INTERVENTION_NONE;
            $this->pausedAtStep = $state['paused_at_step'] ?? null;
            $this->stepResults = $state['step_results'] ?? [];
            $this->pipelineState = $state['pipeline_state'] ?? [];
            $this->approvalHistory = $state['approval_history'] ?? [];
            $this->debugLog = $state['debug_log'] ?? [];

            $this->debug("Session state loaded", ['paused_at' => $this->pausedAtStep]);
        }
    }

    /**
     * Cache drawing input for session resume
     */
    protected function cacheDrawingInput(string|array $input): void
    {
        Cache::put("pipeline_input:{$this->sessionId}", $input, now()->addHours(48));
    }

    /**
     * Get cached drawing input
     */
    protected function getCachedDrawingInput(): string|array|null
    {
        return Cache::get("pipeline_input:{$this->sessionId}");
    }

    /**
     * Apply edits to step data
     */
    protected function applyStepEdits(int $stepNumber, array $edits): void
    {
        $this->pendingEdits[$stepNumber] = $edits;
        $this->debug("Applied edits to step {$stepNumber}", ['edit_keys' => array_keys($edits)]);
    }

    /**
     * Get approval history
     */
    public function getApprovalHistory(): array
    {
        return $this->approvalHistory;
    }

    /**
     * Get pipeline state summary for display
     */
    public function getStateSummary(): array
    {
        $completedSteps = [];
        $pendingSteps = [];

        for ($i = 1; $i <= 10; $i++) {
            $stepKey = "step_{$i}";
            if (isset($this->stepResults[$stepKey])) {
                $completedSteps[] = [
                    'number' => $i,
                    'name' => $this->getStepName($i),
                    'status' => $this->stepResults[$stepKey]['status'] ?? 'unknown',
                    'edited' => $this->stepResults[$stepKey]['edited'] ?? false,
                    'duration_ms' => $this->stepResults[$stepKey]['duration_ms'] ?? null,
                ];
            } else {
                $pendingSteps[] = [
                    'number' => $i,
                    'name' => $this->getStepName($i),
                ];
            }
        }

        return [
            'session_id' => $this->sessionId,
            'status' => $this->currentStatus,
            'intervention_mode' => $this->interventionMode,
            'paused_at_step' => $this->pausedAtStep,
            'completed_steps' => $completedSteps,
            'pending_steps' => $pendingSteps,
            'approval_count' => count($this->approvalHistory),
            'has_edits' => !empty(array_filter($this->stepResults, fn($r) => $r['edited'] ?? false)),
        ];
    }
}
