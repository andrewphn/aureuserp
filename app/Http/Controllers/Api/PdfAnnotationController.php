<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PdfDocument;
use App\Models\PdfAnnotationHistory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * PDF Page Annotation Controller
 *
 * Manages annotations on PDF pages with integration to project entities
 * (rooms, cabinet runs, cabinet specifications).
 *
 * All methods work with pdf_page_annotations table.
 */
class PdfAnnotationController extends Controller
{

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

            // User access already verified by auth:web middleware

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

            // User access already verified by auth:web middleware

            // Get project for entity creation
            $project = $pdfDocument->module;

            // Validate request
            $validated = $request->validate([
                'annotations' => 'required|array',
                'annotations.*.type' => 'nullable|string',
                'annotations.*.parent_annotation_id' => 'nullable|integer',  // For hierarchical relationships
                'annotations.*.x' => 'required|numeric|min:0|max:1',
                'annotations.*.y' => 'required|numeric|min:0|max:1',
                'annotations.*.width' => 'required|numeric|min:0|max:1',
                'annotations.*.height' => 'required|numeric|min:0|max:1',
                'annotations.*.text' => 'nullable|string',
                'annotations.*.room_type' => 'nullable|string',
                'annotations.*.color' => 'nullable|string',
                'annotations.*.room_id' => 'nullable|integer',
                'annotations.*.room_location_id' => 'nullable|integer',  // For location annotations
                'annotations.*.cabinet_run_id' => 'nullable|integer',  // For cabinet run annotations
                'annotations.*.cabinet_specification_id' => 'nullable|integer',  // For cabinet annotations
                'annotations.*.view_type' => 'nullable|string|in:plan,elevation,section,detail',  // View type enum
                'annotations.*.notes' => 'nullable|string',
                'annotations.*.annotation_type' => 'nullable|string',
                'annotations.*.context' => 'nullable|array',
                'create_entities' => 'nullable|boolean',
            ]);

            $createEntities = $validated['create_entities'] ?? false;

            // Wrap everything in a database transaction
            \DB::transaction(function () use ($pdfPageId, $validated, $createEntities, $project, $pdfPage, &$savedAnnotations, &$createdEntities) {
                // Log deletion of existing annotations before deleting them
                $existingAnnotations = \App\Models\PdfPageAnnotation::where('pdf_page_id', $pdfPageId)->get();
                foreach ($existingAnnotations as $annotation) {
                    PdfAnnotationHistory::logAction(
                        pdfPageId: $pdfPageId,
                        action: 'deleted',
                        beforeData: $annotation->toArray(),
                        afterData: null,
                        annotationId: null  // Set to null since annotation will be deleted (foreign key constraint)
                    );
                }

                // Delete existing annotations for this page (replace strategy)
                \App\Models\PdfPageAnnotation::where('pdf_page_id', $pdfPageId)->delete();

                // Save new annotations
                $savedAnnotations = [];
                $createdEntities = [];
                $entityService = new \App\Services\AnnotationEntityService();

                foreach ($validated['annotations'] as $annotation) {
                    // Validate cabinet_run_id exists before using it
                    $cabinetRunId = $annotation['cabinet_run_id'] ?? null;
                    if ($cabinetRunId && !\Webkul\Project\Models\CabinetRun::find($cabinetRunId)) {
                        \Log::warning("Invalid cabinet_run_id {$cabinetRunId} provided, setting to null");
                        $cabinetRunId = null;
                    }

                    // Validate room_id exists before using it
                    $roomId = $annotation['room_id'] ?? null;
                    if ($roomId && !\Webkul\Project\Models\Room::find($roomId)) {
                        \Log::warning("Invalid room_id {$roomId} provided, setting to null");
                        $roomId = null;
                    }

                    $savedAnnotation = \App\Models\PdfPageAnnotation::create([
                        'pdf_page_id' => $pdfPageId,
                        'parent_annotation_id' => $annotation['parent_annotation_id'] ?? null,  // Hierarchical relationship
                        'annotation_type' => $annotation['annotation_type'] ?? 'room',
                        'x' => $annotation['x'],
                        'y' => $annotation['y'],
                        'width' => $annotation['width'],
                        'height' => $annotation['height'],
                        'label' => $annotation['text'] ?? null,
                        'room_type' => $annotation['room_type'] ?? null,
                        'color' => $annotation['color'] ?? null,
                        'room_id' => $roomId,
                        'room_location_id' => $annotation['room_location_id'] ?? null,  // For location annotations
                        'cabinet_run_id' => $cabinetRunId,
                        'cabinet_specification_id' => $annotation['cabinet_specification_id'] ?? null,  // For cabinet annotations
                        'view_type' => $annotation['view_type'] ?? 'plan',  // Default to plan view
                        'notes' => $annotation['notes'] ?? null,
                        'created_by' => Auth::id(),
                    ]);

                    // Log annotation creation - now within the transaction
                    PdfAnnotationHistory::logAction(
                        pdfPageId: $pdfPageId,
                        action: 'created',
                        beforeData: null,
                        afterData: $savedAnnotation->toArray(),
                        annotationId: $savedAnnotation->id
                    );

                    // Sync annotation notes to related entity's notes field
                    if (!empty($annotation['notes'])) {
                        $notes = $annotation['notes'];

                        // Update Room notes if room_id is present
                        if (!empty($savedAnnotation->room_id)) {
                            $room = \Webkul\Project\Models\Room::find($savedAnnotation->room_id);
                            if ($room) {
                                $room->notes = $notes;
                                $room->save();
                            }
                        }

                        // Update CabinetRun notes if cabinet_run_id is present
                        if (!empty($savedAnnotation->cabinet_run_id)) {
                            $cabinetRun = \Webkul\Project\Models\CabinetRun::find($savedAnnotation->cabinet_run_id);
                            if ($cabinetRun) {
                                $cabinetRun->notes = $notes;
                                $cabinetRun->save();
                            }
                        }

                        // Update CabinetSpecification notes if cabinet_specification_id is present
                        if (!empty($savedAnnotation->cabinet_specification_id)) {
                            $cabinet = \Webkul\Project\Models\CabinetSpecification::find($savedAnnotation->cabinet_specification_id);
                            if ($cabinet) {
                                $cabinet->notes = $notes;
                                $cabinet->save();
                            }
                        }
                    }

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
            });

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
     * Delete a single page annotation
     * DELETE /api/pdf/page/annotations/{annotationId}
     *
     * @param int $annotationId Annotation ID
     * @return JsonResponse
     */
    public function deletePageAnnotation(int $annotationId): JsonResponse
    {
        try {
            $annotation = \App\Models\PdfPageAnnotation::findOrFail($annotationId);
            $pdfPageId = $annotation->pdf_page_id;

            // Collect all annotations to delete (including children)
            $annotationsToDelete = $this->getAnnotationsToDelete($annotation);

            // Log deletion for each annotation before deleting
            foreach ($annotationsToDelete as $annotationToDelete) {
                PdfAnnotationHistory::logAction(
                    pdfPageId: $annotationToDelete->pdf_page_id,
                    action: 'deleted',
                    beforeData: $annotationToDelete->toArray(),
                    afterData: null,
                    annotationId: null  // Set to null since annotation will be deleted
                );
            }

            // Delete all annotations (parent and all children)
            $deletedCount = 0;
            foreach ($annotationsToDelete as $annotationToDelete) {
                $annotationToDelete->delete();
                $deletedCount++;
            }

            return response()->json([
                'success' => true,
                'message' => "Annotation and {$deletedCount} related annotations deleted successfully",
                'annotation_id' => $annotationId,
                'deleted_count' => $deletedCount,
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Annotation not found'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Failed to delete page annotation', [
                'annotation_id' => $annotationId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to delete annotation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all annotations that should be deleted when deleting a parent annotation
     * Implements hierarchical/cascading deletion using parent_annotation_id
     *
     * This recursively finds all child annotations regardless of whether they
     * have foreign keys set (works for both regular and isolation mode annotations)
     *
     * @param \App\Models\PdfPageAnnotation $annotation The annotation being deleted
     * @return array Array of annotations to delete (includes the parent annotation)
     */
    private function getAnnotationsToDelete(\App\Models\PdfPageAnnotation $annotation): array
    {
        $toDelete = collect([$annotation]);

        // Recursively find all children using parent_annotation_id
        $children = $this->findChildrenRecursively($annotation->id);
        $toDelete = $toDelete->merge($children);

        return $toDelete->unique('id')->all();
    }

    /**
     * Recursively find all child annotations by parent_annotation_id
     *
     * @param int $parentId The parent annotation ID to search for
     * @return \Illuminate\Support\Collection Collection of child annotations
     */
    private function findChildrenRecursively(int $parentId): \Illuminate\Support\Collection
    {
        // Find all direct children of this parent
        $children = \App\Models\PdfPageAnnotation::where('parent_annotation_id', $parentId)->get();

        if ($children->isEmpty()) {
            return collect();
        }

        $allDescendants = collect($children);

        // Recursively find children of each child
        foreach ($children as $child) {
            $grandchildren = $this->findChildrenRecursively($child->id);
            $allDescendants = $allDescendants->merge($grandchildren);
        }

        return $allDescendants;
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

            // User access already verified by auth:web middleware

            // Load annotations for this page
            $annotations = \App\Models\PdfPageAnnotation::where('pdf_page_id', $pdfPageId)
                ->orderBy('created_at', 'asc')
                ->get();

            // Get last modified timestamp for conflict resolution
            $lastModified = $annotations->max('updated_at');

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
                    'room_location_id' => $annotation->room_location_id,  // CRITICAL: Include room_location_id for location isolation mode
                    'parent_annotation_id' => $annotation->parent_annotation_id,  // Add parent relationship for hierarchy
                    'notes' => $annotation->notes,
                    'annotation_type' => $annotation->annotation_type,
                ];
            });

            return response()->json([
                'success' => true,
                'annotations' => $formattedAnnotations,
                'last_modified' => $lastModified ? $lastModified->toIso8601String() : null,  // For conflict resolution
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

            // User access already verified by auth:web middleware

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
                ->select('projects_cabinet_runs.id', 'projects_cabinet_runs.name', 'projects_cabinet_runs.run_type')
                ->orderBy('projects_cabinet_runs.name')
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

            // User access already verified by auth:web middleware

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
     * Get project context for cover page auto-population
     * GET /api/pdf/page/{pdfPageId}/project-context
     *
     * @param int $pdfPageId PDF Page ID
     * @return JsonResponse
     */
    public function getProjectContext(int $pdfPageId): JsonResponse
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

            // User access already verified by auth:web middleware

            // Get the project through the polymorphic relationship
            $project = $pdfDocument->module;

            if (!$project) {
                return response()->json([
                    'success' => true,
                    'project_context' => null,
                    'message' => 'No project associated with this document'
                ]);
            }

            // Load project with relationships
            $project->load(['partner', 'company', 'branch', 'addresses']);

            // Get primary project address
            $primaryAddress = $project->addresses()->where('is_primary', true)->first();

            return response()->json([
                'success' => true,
                'project_context' => [
                    'project_id' => $project->id,
                    'project_number' => $project->project_number,
                    'project_name' => $project->name,
                    'partner' => $project->partner ? [
                        'id' => $project->partner->id,
                        'name' => $project->partner->name,
                        'email' => $project->partner->email,
                        'phone' => $project->partner->phone,
                    ] : null,
                    'company' => $project->company ? [
                        'id' => $project->company->id,
                        'name' => $project->company->name,
                        'email' => $project->company->email,
                        'phone' => $project->company->phone,
                    ] : null,
                    'branch' => $project->branch ? [
                        'id' => $project->branch->id,
                        'name' => $project->branch->name,
                        'email' => $project->branch->email,
                        'phone' => $project->branch->phone,
                    ] : null,
                    'address' => $primaryAddress ? [
                        'street1' => $primaryAddress->street1,
                        'street2' => $primaryAddress->street2,
                        'city' => $primaryAddress->city,
                        'zip' => $primaryAddress->zip,
                        'state_id' => $primaryAddress->state_id,
                        'country_id' => $primaryAddress->country_id,
                    ] : null,
                ],
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'PDF page not found'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Failed to get project context', [
                'pdf_page_id' => $pdfPageId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve project context'
            ], 500);
        }
    }

    /**
     * Get annotation history for a PDF page
     * GET /api/pdf/page/{pdfPageId}/annotations/history
     *
     * @param int $pdfPageId PDF Page ID
     * @return JsonResponse
     */
    public function getAnnotationHistory(int $pdfPageId): JsonResponse
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

            // User access already verified by auth:web middleware

            // Get history for this page
            $history = PdfAnnotationHistory::forPage($pdfPageId);

            // Format history entries for frontend
            $formattedHistory = $history->map(function ($entry) {
                return [
                    'id' => $entry->id,
                    'annotation_id' => $entry->annotation_id,
                    'action' => $entry->action,
                    'user' => [
                        'id' => $entry->user_id,
                        'name' => $entry->user->name ?? 'Unknown',
                        'email' => $entry->user->email ?? null,
                    ],
                    'before_data' => $entry->before_data,
                    'after_data' => $entry->after_data,
                    'metadata' => $entry->metadata,
                    'ip_address' => $entry->ip_address,
                    'created_at' => $entry->created_at->toIso8601String(),
                    'created_at_human' => $entry->created_at->diffForHumans(),
                ];
            });

            return response()->json([
                'success' => true,
                'history' => $formattedHistory,
                'count' => $history->count(),
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
            Log::error('Failed to get annotation history', [
                'pdf_page_id' => $pdfPageId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve annotation history'
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

    /**
     * Get page metadata (page type, cover fields, etc.)
     * GET /api/pdf/page/{pdfPageId}/metadata
     *
     * @param int $pdfPageId PDF Page ID
     * @return JsonResponse
     */
    public function getPageMetadata(int $pdfPageId): JsonResponse
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

            // User access already verified by auth:web middleware

            // Get page metadata from the page_metadata JSON column
            $metadata = $pdfPage->page_metadata ?? [];

            return response()->json([
                'success' => true,
                'page_type' => $metadata['page_type'] ?? null,
                'cover_metadata' => $metadata['cover_metadata'] ?? null,
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
            Log::error('Failed to get page metadata', [
                'pdf_page_id' => $pdfPageId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve page metadata'
            ], 500);
        }
    }

    /**
     * Save page metadata (page type, cover fields, etc.)
     * POST /api/pdf/page/{pdfPageId}/metadata
     *
     * @param int $pdfPageId PDF Page ID
     * @param Request $request Metadata data
     * @return JsonResponse
     */
    public function savePageMetadata(int $pdfPageId, Request $request): JsonResponse
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

            // User access already verified by auth:web middleware

            // Validate request
            $validated = $request->validate([
                'page_type' => 'nullable|string|in:floor_plan,elevation,detail,cover,other',
                'cover_metadata' => 'nullable|array',
                'cover_metadata.customer_id' => 'nullable|integer',
                'cover_metadata.company_id' => 'nullable|integer',
                'cover_metadata.branch_id' => 'nullable|integer',
                'cover_metadata.address_street1' => 'nullable|string|max:255',
                'cover_metadata.address_street2' => 'nullable|string|max:255',
                'cover_metadata.address_city' => 'nullable|string|max:255',
                'cover_metadata.address_state_id' => 'nullable|integer',
                'cover_metadata.address_zip' => 'nullable|string|max:20',
                'cover_metadata.address_country_id' => 'nullable|integer',
            ]);

            // Get existing metadata
            $metadata = $pdfPage->page_metadata ?? [];

            // Update page type
            if (isset($validated['page_type'])) {
                $metadata['page_type'] = $validated['page_type'];
            }

            // Update cover metadata
            if (isset($validated['cover_metadata'])) {
                $metadata['cover_metadata'] = $validated['cover_metadata'];
            }

            // Save to page_metadata JSON column
            $pdfPage->page_metadata = $metadata;
            $pdfPage->save();

            return response()->json([
                'success' => true,
                'message' => 'Page metadata saved successfully',
                'page_type' => $metadata['page_type'] ?? null,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'details' => $e->errors()
            ], 422);

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
            Log::error('Failed to save page metadata', [
                'pdf_page_id' => $pdfPageId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to save page metadata: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save page type only (NEW - Phase 3.1)
     * POST /api/pdf/page/{pdfPageId}/page-type
     *
     * @param int $pdfPageId PDF Page ID
     * @param Request $request Page type data
     * @return JsonResponse
     */
    public function savePageType(int $pdfPageId, Request $request): JsonResponse
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

            // User access already verified by auth:web middleware

            // Validate request
            $validated = $request->validate([
                'page_type' => 'nullable|string|in:floor_plan,elevation,detail,cover,other',
            ]);

            // Get existing metadata or initialize empty array
            $metadata = $pdfPage->page_metadata ?? [];

            // Update page type
            $metadata['page_type'] = $validated['page_type'] ?? null;

            // Save to page_metadata JSON column
            $pdfPage->page_metadata = $metadata;
            $pdfPage->save();

            Log::info('Page type saved', [
                'pdf_page_id' => $pdfPageId,
                'page_number' => $pdfPage->page_number,
                'page_type' => $validated['page_type']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Page type saved successfully',
                'page_type' => $metadata['page_type'],
                'page_number' => $pdfPage->page_number,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'details' => $e->errors()
            ], 422);

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
            Log::error('Failed to save page type', [
                'pdf_page_id' => $pdfPageId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to save page type: ' . $e->getMessage()
            ], 500);
        }
    }
}
