<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AnnotationService;
use App\Models\PdfDocument;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PdfAnnotationController extends Controller
{
    protected AnnotationService $annotationService;

    public function __construct(AnnotationService $annotationService)
    {
        $this->annotationService = $annotationService;
    }

    /**
     * Get all annotations for a document
     * GET /api/pdf/{documentId}/annotations
     *
     * @param int $documentId Document ID
     * @param Request $request Query parameters (page_number)
     * @return JsonResponse
     */
    public function index(int $documentId, Request $request): JsonResponse
    {
        try {
            // Verify document exists and user has access
            $document = PdfDocument::findOrFail($documentId);
            $this->authorize('view', $document);

            $pageNumber = $request->query('page_number', null);

            $instantJSON = $this->annotationService->exportAnnotationsAsInstantJSON(
                $documentId,
                $pageNumber
            );

            return response()->json([
                'success' => true,
                'document_id' => $documentId,
                'page_number' => $pageNumber,
                'annotations' => $instantJSON['annotations'],
                'format' => $instantJSON['format'],
            ]);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized access to document'
            ], 403);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Document not found'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve annotations', [
                'document_id' => $documentId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve annotations'
            ], 500);
        }
    }

    /**
     * Save annotations to database (batch create/update)
     * POST /api/pdf/{documentId}/annotations
     *
     * @param int $documentId Document ID
     * @param Request $request Request with InstantJSON annotations
     * @return JsonResponse
     */
    public function store(int $documentId, Request $request): JsonResponse
    {
        try {
            // Verify document exists and user has access
            $document = PdfDocument::findOrFail($documentId);
            $this->authorize('update', $document);

            // Validate request
            $validated = $request->validate([
                'annotations' => 'required|array',
                'annotations.*' => 'required|array',
                'annotations.*.type' => 'required|string',
            ]);

            // Import annotations using InstantJSON format
            $instantJSON = [
                'format' => 'https://pspdfkit.com/instant-json/v1',
                'annotations' => $validated['annotations']
            ];

            $savedAnnotations = $this->annotationService->importInstantJSON($documentId, $instantJSON);

            // Extract annotation IDs for verification
            $annotationIds = $savedAnnotations->pluck('id')->toArray();

            // Ensure annotations are persisted
            $verified = $this->annotationService->ensureAnnotationsSaved($documentId, $annotationIds);

            return response()->json([
                'success' => true,
                'document_id' => $documentId,
                'saved_count' => $savedAnnotations->count(),
                'verified' => $verified,
                'annotations' => $savedAnnotations->map(function ($annotation) {
                    return [
                        'id' => $annotation->id,
                        'type' => $annotation->annotation_type,
                        'page_number' => $annotation->page_number,
                        'created_at' => $annotation->created_at->toIso8601String(),
                    ];
                }),
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'details' => $e->errors()
            ], 422);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized to modify document'
            ], 403);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);

        } catch (\Exception $e) {
            Log::error('Failed to save annotations', [
                'document_id' => $documentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to save annotations'
            ], 500);
        }
    }

    /**
     * Get a single annotation
     * GET /api/pdf/annotations/{annotationId}
     *
     * @param int $annotationId Annotation ID
     * @return JsonResponse
     */
    public function show(int $annotationId): JsonResponse
    {
        try {
            $annotation = $this->annotationService->getAnnotation($annotationId);

            if (!$annotation) {
                return response()->json([
                    'success' => false,
                    'error' => 'Annotation not found'
                ], 404);
            }

            // Verify user has access to the document
            $this->authorize('view', $annotation->document);

            return response()->json([
                'success' => true,
                'annotation' => [
                    'id' => $annotation->id,
                    'document_id' => $annotation->document_id,
                    'page_number' => $annotation->page_number,
                    'type' => $annotation->annotation_type,
                    'data' => $annotation->annotation_data,
                    'author' => [
                        'id' => $annotation->author_id,
                        'name' => $annotation->author_name,
                    ],
                    'created_at' => $annotation->created_at->toIso8601String(),
                    'updated_at' => $annotation->updated_at->toIso8601String(),
                ],
            ]);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized access to annotation'
            ], 403);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve annotation', [
                'annotation_id' => $annotationId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve annotation'
            ], 500);
        }
    }

    /**
     * Update a single annotation
     * PUT /api/pdf/annotations/{annotationId}
     *
     * @param int $annotationId Annotation ID
     * @param Request $request Request with updated annotation data
     * @return JsonResponse
     */
    public function update(int $annotationId, Request $request): JsonResponse
    {
        try {
            // Validate request
            $validated = $request->validate([
                'type' => 'required|string',
                'data' => 'required|array',
            ]);

            $annotation = $this->annotationService->updateAnnotation(
                $annotationId,
                $validated['data']
            );

            return response()->json([
                'success' => true,
                'annotation' => [
                    'id' => $annotation->id,
                    'document_id' => $annotation->document_id,
                    'page_number' => $annotation->page_number,
                    'type' => $annotation->annotation_type,
                    'updated_at' => $annotation->updated_at->toIso8601String(),
                ],
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'details' => $e->errors()
            ], 422);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Annotation not found'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Failed to update annotation', [
                'annotation_id' => $annotationId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an annotation (soft delete)
     * DELETE /api/pdf/annotations/{annotationId}
     *
     * @param int $annotationId Annotation ID
     * @return JsonResponse
     */
    public function destroy(int $annotationId): JsonResponse
    {
        try {
            $deleted = $this->annotationService->deleteAnnotation($annotationId);

            return response()->json([
                'success' => true,
                'message' => 'Annotation deleted successfully',
                'annotation_id' => $annotationId,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Annotation not found'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Failed to delete annotation', [
                'annotation_id' => $annotationId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 403);
        }
    }

    /**
     * Get annotation count for document
     * GET /api/pdf/{documentId}/annotations/count
     *
     * @param int $documentId Document ID
     * @param Request $request Query parameters (page_number)
     * @return JsonResponse
     */
    public function count(int $documentId, Request $request): JsonResponse
    {
        try {
            // Verify document exists and user has access
            $document = PdfDocument::findOrFail($documentId);
            $this->authorize('view', $document);

            $pageNumber = $request->query('page_number', null);

            $count = $this->annotationService->getAnnotationCount($documentId, $pageNumber);

            return response()->json([
                'success' => true,
                'document_id' => $documentId,
                'page_number' => $pageNumber,
                'count' => $count,
            ]);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized access to document'
            ], 403);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Document not found'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Failed to count annotations', [
                'document_id' => $documentId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to count annotations'
            ], 500);
        }
    }

    /**
     * Get project number for a PDF page
     * GET /api/pdf/page/{pdfPageId}/project-number
     *
     * @param int $pdfPageId PDF Page ID
     * @return JsonResponse
     */
    public function getProjectNumber(int $pdfPageId): JsonResponse
    {
        try {
            $pdfPage = \App\Models\PdfPage::findOrFail($pdfPageId);
            $pdfDocument = $pdfPage->pdfDocument;

            if (!$pdfDocument) {
                return response()->json([
                    'success' => false,
                    'error' => 'PDF document not found for this page'
                ], 404);
            }

            // Verify user has access
            $this->authorize('view', $pdfDocument);

            // Get the project through the polymorphic relationship
            $project = $pdfDocument->module;

            if (!$project || !method_exists($project, 'getAttribute')) {
                return response()->json([
                    'success' => false,
                    'error' => 'No project associated with this document'
                ], 404);
            }

            $projectNumber = $project->getAttribute('project_number');

            if (!$projectNumber) {
                return response()->json([
                    'success' => false,
                    'error' => 'Project has no project number'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'project_number' => $projectNumber,
                'project_id' => $project->id,
                'project_name' => $project->name ?? null,
            ]);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized access to document'
            ], 403);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'PDF page not found'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Failed to get project number', [
                'pdf_page_id' => $pdfPageId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve project number'
            ], 500);
        }
    }

    /**
     * Save page annotations to database
     * POST /api/pdf/page/{pdfPageId}/annotations
     *
     * @param int $pdfPageId PDF Page ID
     * @param Request $request Annotations data
     * @return JsonResponse
     */
    public function savePageAnnotations(int $pdfPageId, Request $request): JsonResponse
    {
        try {
            $pdfPage = \App\Models\PdfPage::findOrFail($pdfPageId);
            $pdfDocument = $pdfPage->pdfDocument;

            if (!$pdfDocument) {
                return response()->json([
                    'success' => false,
                    'error' => 'PDF document not found for this page'
                ], 404);
            }

            // Verify user has access
            $this->authorize('update', $pdfDocument);

            // Get project for entity creation
            $project = $pdfDocument->module;

            // Validate request
            $validated = $request->validate([
                'annotations' => 'required|array',
                'annotations.*.type' => 'nullable|string',
                'annotations.*.x' => 'required|numeric|min:0|max:1',
                'annotations.*.y' => 'required|numeric|min:0|max:1',
                'annotations.*.width' => 'required|numeric|min:0|max:1',
                'annotations.*.height' => 'required|numeric|min:0|max:1',
                'annotations.*.text' => 'nullable|string',
                'annotations.*.room_type' => 'nullable|string',
                'annotations.*.color' => 'nullable|string',
                'annotations.*.cabinet_run_id' => 'nullable|integer',
                'annotations.*.room_id' => 'nullable|integer',
                'annotations.*.notes' => 'nullable|string',
                'annotations.*.annotation_type' => 'nullable|string',
                'annotations.*.context' => 'nullable|array',
                'create_entities' => 'nullable|boolean',
            ]);

            $createEntities = $validated['create_entities'] ?? false;

            // Delete existing annotations for this page (replace strategy)
            \App\Models\PdfPageAnnotation::where('pdf_page_id', $pdfPageId)->delete();

            // Save new annotations
            $savedAnnotations = [];
            $createdEntities = [];
            $entityService = new \App\Services\AnnotationEntityService();

            foreach ($validated['annotations'] as $annotation) {
                $savedAnnotation = \App\Models\PdfPageAnnotation::create([
                    'pdf_page_id' => $pdfPageId,
                    'annotation_type' => $annotation['annotation_type'] ?? 'room',
                    'x' => $annotation['x'],
                    'y' => $annotation['y'],
                    'width' => $annotation['width'],
                    'height' => $annotation['height'],
                    'label' => $annotation['text'] ?? null,
                    'room_type' => $annotation['room_type'] ?? null,
                    'color' => $annotation['color'] ?? null,
                    'cabinet_run_id' => $annotation['cabinet_run_id'] ?? null,
                    'room_id' => $annotation['room_id'] ?? null,
                    'notes' => $annotation['notes'] ?? null,
                    'created_by' => Auth::id(),
                ]);

                // If create_entities flag is true and context provided, create linked entity
                if ($createEntities && isset($annotation['context']) && $project) {
                    $context = array_merge($annotation['context'], [
                        'project_id' => $project->id,
                        'page_number' => $pdfPage->page_number,
                    ]);

                    $result = $entityService->createOrLinkEntityFromAnnotation($savedAnnotation, $context);

                    if ($result['success']) {
                        $createdEntities[] = [
                            'annotation_id' => $savedAnnotation->id,
                            'entity_type' => $result['entity_type'],
                            'entity_id' => $result['entity_id'],
                            'entity' => $result['entity'],
                        ];

                        // Refresh annotation to get updated foreign keys
                        $savedAnnotation = $savedAnnotation->fresh();
                    }
                }

                $savedAnnotations[] = $savedAnnotation;
            }

            return response()->json([
                'success' => true,
                'message' => 'Annotations saved successfully',
                'count' => count($savedAnnotations),
                'annotations' => $savedAnnotations,
                'created_entities' => $createdEntities,
                'entities_created_count' => count($createdEntities),
            ], 201);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized access to document'
            ], 403);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'PDF page not found'
            ], 404);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'details' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Failed to save page annotations', [
                'pdf_page_id' => $pdfPageId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to save annotations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Load page annotations from database
     * GET /api/pdf/page/{pdfPageId}/annotations
     *
     * @param int $pdfPageId PDF Page ID
     * @return JsonResponse
     */
    public function loadPageAnnotations(int $pdfPageId): JsonResponse
    {
        try {
            $pdfPage = \App\Models\PdfPage::findOrFail($pdfPageId);
            $pdfDocument = $pdfPage->pdfDocument;

            if (!$pdfDocument) {
                return response()->json([
                    'success' => false,
                    'error' => 'PDF document not found for this page'
                ], 404);
            }

            // Verify user has access
            $this->authorize('view', $pdfDocument);

            // Load annotations for this page
            $annotations = \App\Models\PdfPageAnnotation::where('pdf_page_id', $pdfPageId)
                ->orderBy('created_at', 'asc')
                ->get();

            // Transform to frontend format
            $formattedAnnotations = $annotations->map(function ($annotation) {
                return [
                    'id' => $annotation->id,
                    'x' => (float) $annotation->x,
                    'y' => (float) $annotation->y,
                    'width' => (float) $annotation->width,
                    'height' => (float) $annotation->height,
                    'text' => $annotation->label,
                    'room_type' => $annotation->room_type,
                    'color' => $annotation->color,
                    'cabinet_run_id' => $annotation->cabinet_run_id,
                    'room_id' => $annotation->room_id,
                    'notes' => $annotation->notes,
                    'annotation_type' => $annotation->annotation_type,
                ];
            });

            return response()->json([
                'success' => true,
                'annotations' => $formattedAnnotations,
                'count' => $annotations->count(),
            ]);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized access to document'
            ], 403);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'PDF page not found'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Failed to load page annotations', [
                'pdf_page_id' => $pdfPageId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to load annotations'
            ], 500);
        }
    }

    /**
     * Get cabinet runs for a PDF page's project
     * GET /api/pdf/annotations/page/{pdfPageId}/cabinet-runs
     *
     * @param int $pdfPageId PDF Page ID
     * @return JsonResponse
     */
    public function getCabinetRuns(int $pdfPageId): JsonResponse
    {
        try {
            $pdfPage = \App\Models\PdfPage::findOrFail($pdfPageId);
            $pdfDocument = $pdfPage->pdfDocument;

            if (!$pdfDocument) {
                return response()->json([
                    'success' => false,
                    'error' => 'PDF document not found for this page'
                ], 404);
            }

            // Verify user has access
            $this->authorize('view', $pdfDocument);

            // Get the project through the polymorphic relationship
            $project = $pdfDocument->module;

            if (!$project) {
                return response()->json([
                    'success' => true,
                    'cabinet_runs' => [],
                    'message' => 'No project associated with this document'
                ]);
            }

            // Load cabinet runs for this project
            $cabinetRuns = $project->cabinetRuns()
                ->select('id', 'name', 'description', 'room_type')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'cabinet_runs' => $cabinetRuns,
                'count' => $cabinetRuns->count(),
            ]);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized access to document'
            ], 403);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'PDF page not found'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Failed to get cabinet runs', [
                'pdf_page_id' => $pdfPageId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve cabinet runs'
            ], 500);
        }
    }

    /**
     * Get context data for annotation modal (available entities for dropdowns)
     * GET /api/pdf/page/{pdfPageId}/context
     *
     * @param int $pdfPageId PDF Page ID
     * @return JsonResponse
     */
    public function getAnnotationContext(int $pdfPageId): JsonResponse
    {
        try {
            $pdfPage = \App\Models\PdfPage::findOrFail($pdfPageId);
            $pdfDocument = $pdfPage->pdfDocument;

            if (!$pdfDocument) {
                return response()->json([
                    'success' => false,
                    'error' => 'PDF document not found for this page'
                ], 404);
            }

            // Verify user has access
            $this->authorize('view', $pdfDocument);

            // Get the project through the polymorphic relationship
            $project = $pdfDocument->module;

            if (!$project) {
                return response()->json([
                    'success' => true,
                    'context' => [
                        'project_id' => null,
                        'rooms' => [],
                        'room_locations' => [],
                        'cabinet_runs' => [],
                        'cabinets' => [],
                    ],
                    'message' => 'No project associated with this document'
                ]);
            }

            // Load all available entities for dropdowns
            $rooms = \Webkul\Project\Models\Room::where('project_id', $project->id)
                ->orderBy('room_type')
                ->orderBy('name')
                ->get()
                ->map(fn($room) => [
                    'id' => $room->id,
                    'name' => $room->name,
                    'room_type' => $room->room_type,
                    'floor_number' => $room->floor_number,
                    'display_name' => $room->name . ($room->room_type ? ' (' . ucfirst($room->room_type) . ')' : ''),
                ]);

            $roomLocations = \Webkul\Project\Models\RoomLocation::whereHas('room', function($query) use ($project) {
                    $query->where('project_id', $project->id);
                })
                ->with('room')
                ->orderBy('room_id')
                ->orderBy('sequence')
                ->get()
                ->map(fn($location) => [
                    'id' => $location->id,
                    'name' => $location->name,
                    'room_id' => $location->room_id,
                    'room_name' => $location->room->name,
                    'location_type' => $location->location_type,
                    'display_name' => $location->room->name . ' - ' . $location->name,
                ]);

            $cabinetRuns = \Webkul\Project\Models\CabinetRun::whereHas('roomLocation.room', function($query) use ($project) {
                    $query->where('project_id', $project->id);
                })
                ->with(['roomLocation.room'])
                ->orderBy('room_location_id')
                ->get()
                ->map(fn($run) => [
                    'id' => $run->id,
                    'name' => $run->name,
                    'run_type' => $run->run_type,
                    'room_location_id' => $run->room_location_id,
                    'room_id' => $run->roomLocation->room_id,
                    'room_name' => $run->roomLocation->room->name,
                    'location_name' => $run->roomLocation->name,
                    'display_name' => $run->roomLocation->room->name . ' - ' . $run->roomLocation->name . ' - ' . $run->name,
                ]);

            $cabinets = \Webkul\Project\Models\CabinetSpecification::where('project_id', $project->id)
                ->with(['cabinetRun.roomLocation.room'])
                ->orderBy('cabinet_run_id')
                ->orderBy('position_in_run')
                ->get()
                ->map(fn($cabinet) => [
                    'id' => $cabinet->id,
                    'cabinet_number' => $cabinet->cabinet_number,
                    'position_in_run' => $cabinet->position_in_run,
                    'cabinet_run_id' => $cabinet->cabinet_run_id,
                    'room_name' => $cabinet->cabinetRun->roomLocation->room->name ?? null,
                    'run_name' => $cabinet->cabinetRun->name ?? null,
                    'display_name' => ($cabinet->cabinetRun->roomLocation->room->name ?? 'Unknown Room') . ' - ' .
                                     ($cabinet->cabinetRun->name ?? 'Unknown Run') . ' - ' .
                                     ($cabinet->cabinet_number ?? 'Cabinet #' . $cabinet->position_in_run),
                ]);

            return response()->json([
                'success' => true,
                'context' => [
                    'project_id' => $project->id,
                    'project_name' => $project->name,
                    'rooms' => $rooms,
                    'room_locations' => $roomLocations,
                    'cabinet_runs' => $cabinetRuns,
                    'cabinets' => $cabinets,
                ],
            ]);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized access to document'
            ], 403);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'PDF page not found'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Failed to get annotation context', [
                'pdf_page_id' => $pdfPageId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve context data'
            ], 500);
        }
    }

    /**
     * Save annotated PDF using Nutrient Processor API
     * POST /api/pdf/annotations/document/{pdfId}/save
     *
     * @param int $pdfId PDF Document ID
     * @param Request $request Contains Instant JSON annotations
     * @return JsonResponse
     */
    public function saveAnnotatedPdf(int $pdfId, Request $request): JsonResponse
    {
        try {
            // Verify PDF document exists
            $pdfDocument = PdfDocument::findOrFail($pdfId);
            $this->authorize('update', $pdfDocument);

            // Validate request
            $validated = $request->validate([
                'annotations' => 'required|string', // Instant JSON string
            ]);

            // Get original PDF path
            $originalPdfPath = storage_path('app/public/' . $pdfDocument->file_path);

            if (!file_exists($originalPdfPath)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Original PDF file not found'
                ], 404);
            }

            // Save annotations as temporary JSON file
            $annotationsJsonPath = storage_path('app/temp/annotations_' . $pdfId . '_' . time() . '.json');
            @mkdir(dirname($annotationsJsonPath), 0755, true);
            file_put_contents($annotationsJsonPath, $validated['annotations']);

            // Prepare output path for annotated PDF
            $outputPath = storage_path('app/temp/annotated_' . $pdfId . '_' . time() . '.pdf');

            // Call Nutrient Processor API
            $apiKey = config('services.nutrient.license_key');
            $apiUrl = 'https://api.nutrient.io/build';

            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => $apiUrl,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_POSTFIELDS => [
                    'instructions' => json_encode([
                        'parts' => [
                            ['file' => 'document']
                        ],
                        'actions' => [
                            [
                                'type' => 'applyInstantJson',
                                'file' => 'annotations.json'
                            ]
                        ]
                    ]),
                    'document' => new \CURLFile($originalPdfPath),
                    'annotations.json' => new \CURLFile($annotationsJsonPath)
                ],
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $apiKey
                ],
            ]);

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error = curl_error($curl);
            curl_close($curl);

            // Clean up temporary annotations file
            @unlink($annotationsJsonPath);

            if ($error) {
                Log::error('Nutrient API curl error', ['error' => $error]);
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to connect to Nutrient API: ' . $error
                ], 500);
            }

            if ($httpCode !== 200) {
                Log::error('Nutrient API error', [
                    'http_code' => $httpCode,
                    'response' => $response
                ]);
                return response()->json([
                    'success' => false,
                    'error' => 'Nutrient API returned error: ' . $httpCode
                ], 500);
            }

            // Save the annotated PDF
            file_put_contents($outputPath, $response);

            // Generate new filename for annotated version
            $pathInfo = pathinfo($pdfDocument->file_path);
            $newFilename = $pathInfo['filename'] . '_annotated_' . time() . '.' . $pathInfo['extension'];
            $newStoragePath = 'pdfs/' . $newFilename;
            $newFullPath = storage_path('app/public/' . $newStoragePath);

            // Move annotated PDF to permanent storage
            @mkdir(dirname($newFullPath), 0755, true);
            rename($outputPath, $newFullPath);

            // Update PDF document record or create new version
            $pdfDocument->update([
                'file_path' => $newStoragePath,
                'updated_at' => now(),
            ]);

            Log::info('PDF annotations saved successfully', [
                'pdf_id' => $pdfId,
                'new_path' => $newStoragePath
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Annotations saved successfully',
                'pdf_id' => $pdfId,
                'new_file_path' => $newStoragePath,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'PDF document not found'
            ], 404);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid request data',
                'validation_errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Failed to save annotated PDF', [
                'pdf_id' => $pdfId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to save annotations: ' . $e->getMessage()
            ], 500);
        }
    }
}
