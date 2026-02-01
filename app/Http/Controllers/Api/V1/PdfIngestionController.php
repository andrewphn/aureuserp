<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PdfDocument;
use App\Services\AiPdfParsingService;
use App\Services\PdfParsingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\Stage;
use Webkul\Support\Models\Company;

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
     * Get list of companies and branches for form dropdown
     * Returns a flat list with parent companies and their branches
     */
    public function getCompanies(): JsonResponse
    {
        $result = [];

        // Get parent companies with their branches
        $parentCompanies = Company::where('is_active', true)
            ->whereNull('parent_id')
            ->with(['branches' => fn ($q) => $q->where('is_active', true)->orderBy('name')])
            ->orderBy('name')
            ->get();

        foreach ($parentCompanies as $company) {
            // Add parent company
            $result[] = [
                'id' => $company->id,
                'name' => $company->name,
                'acronym' => $company->acronym,
                'is_branch' => false,
            ];

            // Add branches indented
            foreach ($company->branches as $branch) {
                $result[] = [
                    'id' => $branch->id,
                    'name' => "↳ {$branch->name}",
                    'acronym' => $branch->acronym,
                    'is_branch' => true,
                    'parent_id' => $company->id,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'companies' => $result,
        ]);
    }

    /**
     * Get list of branches for a company
     */
    public function getBranches(int $companyId): JsonResponse
    {
        $company = Company::findOrFail($companyId);
        $branches = $company->branches()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'acronym']);

        return response()->json([
            'success' => true,
            'branches' => $branches->map(fn ($b) => [
                'id' => $b->id,
                'name' => $b->name,
                'acronym' => $b->acronym,
            ]),
        ]);
    }

    /**
     * Upload PDF and create/attach to project
     *
     * Accepts a PDF file upload, creates a draft project if needed,
     * attaches the PDF, and triggers AI analysis.
     */
    public function uploadAndAnalyze(Request $request): JsonResponse
    {
        $request->validate([
            'pdf_file' => 'required|file|mimes:pdf|max:51200', // 50MB max
            'customer_name' => 'nullable|string|max:255',
            'project_id' => 'nullable|integer|exists:projects_projects,id',
            'company_id' => 'nullable|integer|exists:companies,id',
            'branch_id' => 'nullable|integer|exists:companies,id',
        ]);

        try {
            $pdfFile = $request->file('pdf_file');
            $customerName = $request->input('customer_name');
            $projectId = $request->input('project_id');
            $companyId = $request->input('company_id') ?: 1; // Default to company 1
            $branchId = $request->input('branch_id');

            // If branch is selected, use branch as company for project
            $effectiveCompanyId = $branchId ?: $companyId;

            // Get or create project
            if ($projectId) {
                $project = Project::findOrFail($projectId);
            } else {
                // Create a new draft project
                $draftStage = Stage::where('code', 'draft')
                    ->orWhere('name', 'like', '%Draft%')
                    ->first();

                if (! $draftStage) {
                    $draftStage = Stage::first(); // Fallback to first stage
                }

                // Get company for acronym and number sequence
                $company = Company::find($effectiveCompanyId);
                $companyAcronym = $company?->acronym ?? strtoupper(substr($company?->name ?? 'TCS', 0, 3));
                $startNumber = $company?->project_number_start ?? 1;

                // Find last project number for this company
                $lastProject = Project::where('company_id', $effectiveCompanyId)
                    ->where('project_number', 'like', "{$companyAcronym}-%")
                    ->orderBy('id', 'desc')
                    ->first();

                $sequentialNumber = $startNumber;
                if ($lastProject && $lastProject->project_number) {
                    preg_match('/-(\d+)/', $lastProject->project_number, $matches);
                    if (! empty($matches[1])) {
                        $sequentialNumber = max(intval($matches[1]) + 1, $startNumber);
                    }
                }

                // Generate project number: ACRONYM-NNN
                $projectNumber = sprintf('%s-%03d', $companyAcronym, $sequentialNumber);

                // Ensure uniqueness
                $counter = 0;
                $originalNumber = $projectNumber;
                while (Project::where('project_number', $projectNumber)->exists()) {
                    $counter++;
                    $projectNumber = "{$originalNumber}-{$counter}";
                }

                // Use filename or customer name for project name
                $projectName = $customerName ?: pathinfo($pdfFile->getClientOriginalName(), PATHINFO_FILENAME);

                $project = Project::create([
                    'name' => $projectName,
                    'project_number' => $projectNumber,
                    'stage_id' => $draftStage?->id,
                    'company_id' => $effectiveCompanyId,
                    'creator_id' => auth()->id() ?? 1,
                ]);

                Log::info('PDF Ingestion: Created draft project', [
                    'project_id' => $project->id,
                    'project_number' => $projectNumber,
                    'company_id' => $effectiveCompanyId,
                    'company_acronym' => $companyAcronym,
                    'name' => $projectName,
                ]);
            }

            // Generate filename with project number
            $originalName = pathinfo($pdfFile->getClientOriginalName(), PATHINFO_FILENAME);
            $revision = $project->pdfDocuments()->count() + 1;
            $fileName = "{$project->project_number}-Rev{$revision}-{$originalName}.pdf";

            // Store the file
            $filePath = $pdfFile->storeAs('pdf-documents', $fileName, 'public');

            // Get page count
            $pageCount = 1;
            try {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile(Storage::disk('public')->path($filePath));
                $pageCount = count($pdf->getPages());
            } catch (\Exception $e) {
                Log::warning('Could not parse PDF for page count', ['error' => $e->getMessage()]);
            }

            // Create PdfDocument record
            $pdfDocument = PdfDocument::create([
                'module_type' => Project::class,
                'module_id' => $project->id,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'file_size' => $pdfFile->getSize(),
                'mime_type' => 'application/pdf',
                'page_count' => $pageCount,
                'document_type' => 'drawing',
                'version_number' => $revision,
                'is_latest_version' => true,
                'uploaded_by' => auth()->id() ?? 1,
                'processing_status' => 'pending',
            ]);

            // Create PdfPage records for each page
            for ($i = 1; $i <= $pageCount; $i++) {
                $pdfDocument->pages()->create([
                    'page_number' => $i,
                    'classification_status' => 'pending',
                ]);
            }

            Log::info('PDF Ingestion: PDF uploaded and attached', [
                'pdf_document_id' => $pdfDocument->id,
                'project_id' => $project->id,
                'file_name' => $fileName,
                'page_count' => $pageCount,
            ]);

            // Now run the analysis
            $pdfDocument->markAsProcessing();

            // Step 1: Classify all pages
            $pageClassifications = $this->aiPdfParsingService->classifyDocumentPages($pdfDocument);

            if (isset($pageClassifications['error'])) {
                $pdfDocument->markAsFailed($pageClassifications['error']);

                return response()->json([
                    'success' => false,
                    'error' => $pageClassifications['error'],
                    'project_id' => $project->id,
                    'pdf_document_id' => $pdfDocument->id,
                ], 500);
            }

            // Apply classifications
            $this->aiPdfParsingService->applyBulkClassification($pdfDocument, $pageClassifications);

            // Store extracted data
            $extractedData = [
                'page_classifications' => $pageClassifications,
            ];

            $pdfDocument->update([
                'extracted_metadata' => $extractedData,
                'extracted_at' => now(),
            ]);

            $pdfDocument->markAsCompleted();

            return response()->json([
                'success' => true,
                'message' => 'PDF uploaded and analyzed successfully',
                'project_id' => $project->id,
                'project_number' => $project->project_number,
                'pdf_document_id' => $pdfDocument->id,
                'file_name' => $fileName,
                'page_count' => $pageCount,
                'data' => $extractedData,
            ]);

        } catch (\Exception $e) {
            Log::error('PDF Ingestion: Upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
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
