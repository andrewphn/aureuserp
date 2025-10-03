<?php

namespace App\Services;

use App\Models\PdfAnnotation;
use App\Models\PdfDocument;
use App\Events\AnnotationCreated;
use App\Events\AnnotationUpdated;
use App\Events\AnnotationDeleted;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class AnnotationService
{
    protected NutrientService $nutrientService;

    public function __construct(NutrientService $nutrientService)
    {
        $this->nutrientService = $nutrientService;
    }

    /**
     * Export annotations as InstantJSON format
     *
     * @param int $documentId Document ID
     * @param int|null $pageNumber Optional page number filter
     * @return array InstantJSON formatted annotations
     */
    public function exportAnnotationsAsInstantJSON(int $documentId, ?int $pageNumber = null): array
    {
        $query = PdfAnnotation::where('document_id', $documentId)
            ->with('author')
            ->orderBy('page_number')
            ->orderBy('created_at');

        if ($pageNumber !== null) {
            $query->where('page_number', $pageNumber);
        }

        $annotations = $query->get();

        // Convert to InstantJSON format
        $instantJSON = [
            'format' => 'https://pspdfkit.com/instant-json/v1',
            'annotations' => $annotations->map(function ($annotation) {
                return array_merge(
                    $annotation->annotation_data ?? [],
                    [
                        'id' => $annotation->id,
                        'name' => $annotation->author_name,
                        'createdAt' => $annotation->created_at->toIso8601String(),
                        'updatedAt' => $annotation->updated_at->toIso8601String(),
                        'pageIndex' => $annotation->page_number - 1, // Nutrient uses 0-based index
                    ]
                );
            })->values()->toArray()
        ];

        return $instantJSON;
    }

    /**
     * Import InstantJSON and save to database
     *
     * @param int $documentId Document ID
     * @param array $instantJSON InstantJSON payload
     * @return Collection Saved annotations
     * @throws \Exception
     */
    public function importInstantJSON(int $documentId, array $instantJSON): Collection
    {
        // Validate document exists
        $document = PdfDocument::findOrFail($documentId);

        // Validate InstantJSON format
        if (!isset($instantJSON['annotations']) || !is_array($instantJSON['annotations'])) {
            throw new \InvalidArgumentException('Invalid InstantJSON format: missing annotations array');
        }

        $user = Auth::user();
        $savedAnnotations = collect();

        foreach ($instantJSON['annotations'] as $annotationData) {
            try {
                // Generate unique ID if not provided
                if (!isset($annotationData['id'])) {
                    $annotationData['id'] = $this->generateAnnotationId();
                }

                // Extract page number (Nutrient uses 0-based pageIndex)
                $pageNumber = isset($annotationData['pageIndex'])
                    ? $annotationData['pageIndex'] + 1  // Convert to 1-based
                    : ($annotationData['page'] ?? 1);

                // Extract annotation type
                $annotationType = $annotationData['type'] ?? 'unknown';

                // Validate annotation schema
                $this->validateAnnotationSchema($annotationData);

                // Find or create annotation
                $annotation = PdfAnnotation::updateOrCreate(
                    [
                        'document_id' => $documentId,
                        'id' => $annotationData['id']
                    ],
                    [
                        'page_number' => $pageNumber,
                        'annotation_type' => $annotationType,
                        'annotation_data' => $annotationData,
                        'author_id' => $user->id,
                        'author_name' => $user->name,
                    ]
                );

                $savedAnnotations->push($annotation);

                // Broadcast annotation created event
                if ($annotation->wasRecentlyCreated) {
                    event(new AnnotationCreated($annotation));
                }

            } catch (\Exception $e) {
                Log::error('Failed to import annotation', [
                    'document_id' => $documentId,
                    'annotation_id' => $annotationData['id'] ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
                // Continue processing other annotations
            }
        }

        return $savedAnnotations;
    }

    /**
     * Generate unique annotation ID using ULID format
     *
     * @return string
     */
    public function generateAnnotationId(): string
    {
        return $this->nutrientService->generateAnnotationId();
    }

    /**
     * Validate annotation data against Nutrient schema
     *
     * @param array $annotationData Annotation data
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function validateAnnotationSchema(array $annotationData): bool
    {
        // Required fields for all annotations
        $requiredFields = ['type'];

        foreach ($requiredFields as $field) {
            if (!isset($annotationData[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }

        $type = $annotationData['type'];

        // Get valid annotation types from config
        $annotationPresets = config('nutrient.viewer_options.annotation_presets', []);
        $validTypes = array_keys($annotationPresets);

        // Add common Nutrient annotation types
        $validTypes = array_merge($validTypes, [
            'pspdfkit/ink',
            'pspdfkit/highlight',
            'pspdfkit/text',
            'pspdfkit/note',
            'pspdfkit/arrow',
            'pspdfkit/line',
            'pspdfkit/rectangle',
            'pspdfkit/ellipse',
            'pspdfkit/stamp',
            'pspdfkit/image',
            'pspdfkit/link'
        ]);

        // Type-specific validation
        switch ($type) {
            case 'pspdfkit/highlight':
            case 'pspdfkit/ink':
                if (!isset($annotationData['boundingBox']) && !isset($annotationData['rects'])) {
                    throw new \InvalidArgumentException("{$type} annotation requires boundingBox or rects");
                }
                break;

            case 'pspdfkit/text':
            case 'pspdfkit/note':
                if (!isset($annotationData['text']) && !isset($annotationData['contents'])) {
                    throw new \InvalidArgumentException("{$type} annotation requires text or contents");
                }
                break;

            case 'pspdfkit/line':
            case 'pspdfkit/arrow':
                if (!isset($annotationData['startPoint']) || !isset($annotationData['endPoint'])) {
                    throw new \InvalidArgumentException("{$type} annotation requires startPoint and endPoint");
                }
                break;
        }

        // Validate color format if present
        if (isset($annotationData['strokeColor'])) {
            if (!$this->isValidColor($annotationData['strokeColor'])) {
                throw new \InvalidArgumentException('Invalid color format for strokeColor');
            }
        }

        if (isset($annotationData['fillColor'])) {
            if (!$this->isValidColor($annotationData['fillColor'])) {
                throw new \InvalidArgumentException('Invalid color format for fillColor');
            }
        }

        return true;
    }

    /**
     * Validate color format (hex, rgb, or Nutrient Color object)
     *
     * @param mixed $color Color value
     * @return bool
     */
    protected function isValidColor($color): bool
    {
        // Hex color
        if (is_string($color) && preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
            return true;
        }

        // Nutrient Color object
        if (is_array($color) && isset($color['r'], $color['g'], $color['b'])) {
            return $color['r'] >= 0 && $color['r'] <= 1 &&
                   $color['g'] >= 0 && $color['g'] <= 1 &&
                   $color['b'] >= 0 && $color['b'] <= 1;
        }

        return false;
    }

    /**
     * Ensure annotations are saved with retry logic
     *
     * @param int $documentId Document ID
     * @param array $annotationIds Annotation IDs to verify
     * @param int $maxRetries Maximum retry attempts
     * @return bool
     */
    public function ensureAnnotationsSaved(int $documentId, array $annotationIds, int $maxRetries = 3): bool
    {
        $attempt = 0;

        while ($attempt < $maxRetries) {
            try {
                // Check if all annotations exist in database
                $count = PdfAnnotation::where('document_id', $documentId)
                    ->whereIn('id', $annotationIds)
                    ->count();

                if ($count === count($annotationIds)) {
                    Log::info('Annotations confirmed saved', [
                        'document_id' => $documentId,
                        'annotation_ids' => $annotationIds,
                        'attempt' => $attempt + 1
                    ]);
                    return true;
                }

                // Wait before retry (exponential backoff)
                $waitTime = pow(2, $attempt) * 100000; // 100ms, 200ms, 400ms
                usleep($waitTime);

                $attempt++;

            } catch (\Exception $e) {
                Log::error('Error verifying annotation save', [
                    'document_id' => $documentId,
                    'error' => $e->getMessage(),
                    'attempt' => $attempt + 1
                ]);

                $attempt++;
            }
        }

        Log::warning('Failed to confirm all annotations saved', [
            'document_id' => $documentId,
            'annotation_ids' => $annotationIds,
            'attempts' => $maxRetries
        ]);

        return false;
    }

    /**
     * Get single annotation by ID
     *
     * @param int $annotationId Annotation ID
     * @return PdfAnnotation|null
     */
    public function getAnnotation(int $annotationId): ?PdfAnnotation
    {
        return PdfAnnotation::with(['document', 'author'])->find($annotationId);
    }

    /**
     * Update single annotation
     *
     * @param int $annotationId Annotation ID
     * @param array $annotationData Updated annotation data
     * @return PdfAnnotation
     * @throws \Exception
     */
    public function updateAnnotation(int $annotationId, array $annotationData): PdfAnnotation
    {
        $annotation = PdfAnnotation::findOrFail($annotationId);

        // Validate user has permission to update
        if ($annotation->author_id !== Auth::id()) {
            throw new \Exception('Unauthorized to update this annotation');
        }

        // Validate schema
        $this->validateAnnotationSchema($annotationData);

        // Update annotation
        $annotation->update([
            'annotation_data' => $annotationData,
            'annotation_type' => $annotationData['type'] ?? $annotation->annotation_type,
        ]);

        $updatedAnnotation = $annotation->fresh();

        // Broadcast annotation updated event
        event(new AnnotationUpdated($updatedAnnotation));

        return $updatedAnnotation;
    }

    /**
     * Delete annotation (soft delete)
     *
     * @param int $annotationId Annotation ID
     * @return bool
     * @throws \Exception
     */
    public function deleteAnnotation(int $annotationId): bool
    {
        $annotation = PdfAnnotation::findOrFail($annotationId);

        // Validate user has permission to delete
        if ($annotation->author_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            throw new \Exception('Unauthorized to delete this annotation');
        }

        // Store data before deletion
        $annotationId = $annotation->id;
        $documentId = $annotation->document_id;
        $authorId = Auth::id();

        $deleted = $annotation->delete();

        // Broadcast annotation deleted event
        if ($deleted) {
            event(new AnnotationDeleted($annotationId, $documentId, $authorId));
        }

        return $deleted;
    }

    /**
     * Get annotation count for document
     *
     * @param int $documentId Document ID
     * @param int|null $pageNumber Optional page filter
     * @return int
     */
    public function getAnnotationCount(int $documentId, ?int $pageNumber = null): int
    {
        $query = PdfAnnotation::where('document_id', $documentId);

        if ($pageNumber !== null) {
            $query->where('page_number', $pageNumber);
        }

        return $query->count();
    }
}
