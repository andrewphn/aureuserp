<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AnnotationService;
use App\Models\PdfDocument;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
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
}
