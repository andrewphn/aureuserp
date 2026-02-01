<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PdfDocument;
use App\Services\AiPdfParsingService;
use App\Services\PdfParsingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Webkul\Project\Models\Project;

/**
 * PDF Ingestion Controller for n8n Integration
 *
 * Provides AI-powered PDF analysis endpoints for external workflow automation.
 * Uses Gemini's native PDF vision for architectural drawing analysis.
 *
 * @group PDF Ingestion
 */
class PdfIngestionController extends Controller
{
    protected AiPdfParsingService $aiPdfParsingService;

    protected PdfParsingService $pdfParsingService;

    public function __construct(
        AiPdfParsingService $aiPdfParsingService,
        PdfParsingService $pdfParsingService
    ) {
        $this->aiPdfParsingService = $aiPdfParsingService;
        $this->pdfParsingService = $pdfParsingService;
    }

    /**
     * Analyze a PDF document using AI vision
     *
     * Performs comprehensive analysis of an architectural PDF:
     * - Page classification (cover, floor plan, elevations, etc.)
     * - Customer & project info extraction from cover page
     * - Room layouts and cabinet specifications
     * - Line items for quoting (LF, SF quantities)
     */
    public function analyze(Request $request): JsonResponse
    {
        $request->validate([
            'pdf_document_id' => 'required|integer|exists:pdf_documents,id',
        ]);

        $pdfDocument = PdfDocument::with('pages')->findOrFail($request->pdf_document_id);

        Log::info('PDF Ingestion: Starting analysis', [
            'pdf_document_id' => $pdfDocument->id,
            'file_name'       => $pdfDocument->file_name,
            'page_count'      => $pdfDocument->page_count,
        ]);

        try {
            // Mark as processing
            $pdfDocument->markAsProcessing();

            // Step 1: Classify all pages using AI vision
            $pageClassifications = $this->aiPdfParsingService->classifyDocumentPages($pdfDocument);

            if (isset($pageClassifications['error'])) {
                $pdfDocument->markAsFailed($pageClassifications['error']);

                return response()->json([
                    'success' => false,
                    'error'   => $pageClassifications['error'],
                ], 500);
            }

            // Apply classifications to pages
            $this->aiPdfParsingService->applyBulkClassification($pdfDocument, $pageClassifications);

            // Step 2: Extract detailed data based on page types
            $extractedData = [
                'page_classifications' => $pageClassifications,
                'cover_page_data'      => null,
                'floor_plans'          => [],
                'elevations'           => [],
                'line_items'           => [],
            ];

            foreach ($pdfDocument->pages as $page) {
                $pageClassification = collect($pageClassifications)->firstWhere('page_number', $page->page_number);
                $purpose = $pageClassification['primary_purpose'] ?? null;

                if ($purpose === 'cover') {
                    $coverData = $this->aiPdfParsingService->parseCoverPage($page);
                    $extractedData['cover_page_data'] = $coverData;

                    // Also try legacy text extraction for line items
                    try {
                        $legacyData = $this->pdfParsingService->parseCoverPage($pdfDocument);
                        $extractedData['cover_page_data'] = array_merge(
                            $extractedData['cover_page_data'] ?? [],
                            $legacyData
                        );
                    } catch (\Exception $e) {
                        Log::warning('Legacy cover page parsing failed', ['error' => $e->getMessage()]);
                    }
                } elseif ($purpose === 'floor_plan') {
                    $floorPlanData = $this->aiPdfParsingService->parseFloorPlan($page);
                    $extractedData['floor_plans'][] = [
                        'page_number' => $page->page_number,
                        'data'        => $floorPlanData,
                    ];
                } elseif ($purpose === 'elevations') {
                    $elevationData = $this->aiPdfParsingService->parseElevation($page);
                    $extractedData['elevations'][] = [
                        'page_number' => $page->page_number,
                        'data'        => $elevationData,
                    ];

                    // Extract line items from elevation data
                    if (! empty($elevationData['linear_feet']) && ! empty($elevationData['pricing_tier'])) {
                        $extractedData['line_items'][] = [
                            'location_name' => $elevationData['location_name'] ?? "Page {$page->page_number}",
                            'room_name'     => $elevationData['room_name'] ?? null,
                            'linear_feet'   => $elevationData['linear_feet'],
                            'pricing_tier'  => $elevationData['pricing_tier'],
                            'page_number'   => $page->page_number,
                        ];
                    }
                }
            }

            // Step 3: Try to extract additional line items from text
            try {
                $architecturalData = $this->pdfParsingService->parseArchitecturalDrawing($pdfDocument);
                if (! empty($architecturalData['line_items'])) {
                    $extractedData['text_extracted_items'] = $architecturalData['line_items'];
                }
            } catch (\Exception $e) {
                Log::warning('Architectural text extraction failed', ['error' => $e->getMessage()]);
            }

            // Store extracted metadata on the document
            $pdfDocument->update([
                'extracted_metadata' => $extractedData,
                'extracted_at'       => now(),
                'metadata_reviewed'  => false,
            ]);

            // Mark as completed
            $pdfDocument->markAsCompleted();

            Log::info('PDF Ingestion: Analysis complete', [
                'pdf_document_id'  => $pdfDocument->id,
                'pages_classified' => count($pageClassifications),
                'elevations_found' => count($extractedData['elevations']),
                'line_items_found' => count($extractedData['line_items']),
            ]);

            return response()->json([
                'success'         => true,
                'pdf_document_id' => $pdfDocument->id,
                'data'            => $extractedData,
            ]);

        } catch (\Exception $e) {
            Log::error('PDF Ingestion: Analysis failed', [
                'pdf_document_id' => $pdfDocument->id,
                'error'           => $e->getMessage(),
                'trace'           => $e->getTraceAsString(),
            ]);

            $pdfDocument->markAsFailed($e->getMessage());

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get the status of a PDF processing job
     *
     * @param  int  $id  PDF Document ID
     */
    public function status(int $id): JsonResponse
    {
        $pdfDocument = PdfDocument::findOrFail($id);

        return response()->json([
            'pdf_document_id'    => $pdfDocument->id,
            'file_name'          => $pdfDocument->file_name,
            'processing_status'  => $pdfDocument->processing_status,
            'processing_error'   => $pdfDocument->processing_error,
            'processed_at'       => $pdfDocument->processed_at?->toIso8601String(),
            'extracted_at'       => $pdfDocument->extracted_at?->toIso8601String(),
            'metadata_reviewed'  => $pdfDocument->metadata_reviewed,
            'has_extracted_data' => ! empty($pdfDocument->extracted_metadata),
        ]);
    }

    /**
     * Get extracted metadata from a processed PDF
     *
     * @param  int  $id  PDF Document ID
     */
    public function getExtractedData(int $id): JsonResponse
    {
        $pdfDocument = PdfDocument::with(['pages', 'module'])->findOrFail($id);

        if (! $pdfDocument->isProcessed()) {
            return response()->json([
                'success'           => false,
                'error'             => 'PDF has not been processed yet',
                'processing_status' => $pdfDocument->processing_status,
            ], 400);
        }

        return response()->json([
            'success'         => true,
            'pdf_document_id' => $pdfDocument->id,
            'file_name'       => $pdfDocument->file_name,
            'project'         => $pdfDocument->module ? [
                'id'             => $pdfDocument->module->id,
                'project_number' => $pdfDocument->module->project_number ?? null,
                'name'           => $pdfDocument->module->name ?? null,
            ] : null,
            'extracted_metadata' => $pdfDocument->extracted_metadata,
            'extracted_at'       => $pdfDocument->extracted_at?->toIso8601String(),
            'pages'              => $pdfDocument->pages->map(fn ($page) => [
                'page_number'           => $page->page_number,
                'page_type'             => $page->page_type,
                'page_label'            => $page->page_label,
                'classification_status' => $page->classification_status,
            ]),
        ]);
    }

    /**
     * Create project entities from extracted PDF data
     *
     * Creates rooms, locations, and cabinet runs in the project
     * based on the extracted PDF data.
     */
    public function createEntities(Request $request): JsonResponse
    {
        $request->validate([
            'pdf_document_id'       => 'required|integer|exists:pdf_documents,id',
            'auto_create_rooms'     => 'boolean',
            'auto_create_locations' => 'boolean',
        ]);

        $pdfDocument = PdfDocument::with('module')->findOrFail($request->pdf_document_id);

        if (! $pdfDocument->isProcessed() || empty($pdfDocument->extracted_metadata)) {
            return response()->json([
                'success' => false,
                'error'   => 'PDF must be processed first. Call /analyze endpoint.',
            ], 400);
        }

        $project = $pdfDocument->module;
        if (! $project instanceof Project) {
            return response()->json([
                'success' => false,
                'error'   => 'PDF is not associated with a project',
            ], 400);
        }

        $extractedData = $pdfDocument->extracted_metadata;
        $createdEntities = [
            'rooms'        => [],
            'locations'    => [],
            'cabinet_runs' => [],
        ];

        // Extract unique rooms from floor plans and elevations
        $roomsFromFloorPlans = collect($extractedData['floor_plans'] ?? [])
            ->flatMap(fn ($fp) => $fp['data']['rooms'] ?? [])
            ->pluck('name')
            ->filter()
            ->unique();

        $roomsFromElevations = collect($extractedData['elevations'] ?? [])
            ->pluck('data.room_name')
            ->filter()
            ->unique();

        $allRooms = $roomsFromFloorPlans->merge($roomsFromElevations)->unique();

        if ($request->input('auto_create_rooms', true)) {
            foreach ($allRooms as $roomName) {
                // Check if room already exists
                $existingRoom = $project->rooms()->where('name', $roomName)->first();
                if (! $existingRoom) {
                    $room = $project->rooms()->create([
                        'name'       => $roomName,
                        'company_id' => $project->company_id,
                    ]);
                    $createdEntities['rooms'][] = [
                        'id'   => $room->id,
                        'name' => $room->name,
                    ];
                }
            }
        }

        // Extract and create locations from elevations
        if ($request->input('auto_create_locations', true)) {
            foreach ($extractedData['elevations'] ?? [] as $elevation) {
                $locationName = $elevation['data']['location_name'] ?? null;
                $roomName = $elevation['data']['room_name'] ?? null;

                if ($locationName && $roomName) {
                    $room = $project->rooms()->where('name', $roomName)->first();
                    if ($room) {
                        $existingLocation = $room->locations()->where('name', $locationName)->first();
                        if (! $existingLocation) {
                            $location = $room->locations()->create([
                                'name'       => $locationName,
                                'company_id' => $project->company_id,
                            ]);
                            $createdEntities['locations'][] = [
                                'id'   => $location->id,
                                'name' => $location->name,
                                'room' => $roomName,
                            ];

                            // Create cabinet run if we have linear feet data
                            if (! empty($elevation['data']['linear_feet'])) {
                                $cabinetRun = $location->cabinetRuns()->create([
                                    'name'         => $locationName,
                                    'linear_feet'  => $elevation['data']['linear_feet'],
                                    'pricing_tier' => $elevation['data']['pricing_tier'] ?? 2,
                                    'company_id'   => $project->company_id,
                                ]);
                                $createdEntities['cabinet_runs'][] = [
                                    'id'          => $cabinetRun->id,
                                    'name'        => $cabinetRun->name,
                                    'linear_feet' => $cabinetRun->linear_feet,
                                    'location'    => $locationName,
                                ];
                            }
                        }
                    }
                }
            }
        }

        Log::info('PDF Ingestion: Entities created', [
            'pdf_document_id'      => $pdfDocument->id,
            'project_id'           => $project->id,
            'rooms_created'        => count($createdEntities['rooms']),
            'locations_created'    => count($createdEntities['locations']),
            'cabinet_runs_created' => count($createdEntities['cabinet_runs']),
        ]);

        return response()->json([
            'success'         => true,
            'pdf_document_id' => $pdfDocument->id,
            'project_id'      => $project->id,
            'created'         => $createdEntities,
        ]);
    }

    /**
     * Generate a sales order from extracted line items
     */
    public function generateSalesOrder(Request $request): JsonResponse
    {
        $request->validate([
            'pdf_document_id' => 'required|integer|exists:pdf_documents,id',
            'partner_id'      => 'required|integer|exists:partners_partners,id',
        ]);

        $pdfDocument = PdfDocument::with('module')->findOrFail($request->pdf_document_id);

        if (! $pdfDocument->isProcessed() || empty($pdfDocument->extracted_metadata)) {
            return response()->json([
                'success' => false,
                'error'   => 'PDF must be processed first',
            ], 400);
        }

        $project = $pdfDocument->module;
        if (! $project instanceof Project) {
            return response()->json([
                'success' => false,
                'error'   => 'PDF is not associated with a project',
            ], 400);
        }

        try {
            // Use existing PdfParsingService to create sales order
            $parsedData = [
                'line_items' => $pdfDocument->extracted_metadata['text_extracted_items'] ?? [],
            ];

            if (empty($parsedData['line_items'])) {
                return response()->json([
                    'success' => false,
                    'error'   => 'No line items found in extracted data',
                ], 400);
            }

            $salesOrderId = $this->pdfParsingService->createSalesOrderFromParsedData(
                $parsedData,
                $project->id,
                $request->partner_id
            );

            return response()->json([
                'success'          => true,
                'sales_order_id'   => $salesOrderId,
                'line_items_count' => count($parsedData['line_items']),
            ]);

        } catch (\Exception $e) {
            Log::error('PDF Ingestion: Sales order creation failed', [
                'pdf_document_id' => $pdfDocument->id,
                'error'           => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
