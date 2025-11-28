<?php

namespace Webkul\Project\Services;

use App\Models\PdfPageAnnotation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AnnotationSyncService
{
    /**
     * Sync metadata across all annotations linked to the same entity
     * This keeps label, notes, and parent relationships synchronized across multiple page views
     *
     * @param PdfPageAnnotation $annotation The annotation that was just updated
     * @param array $updateData The data that was updated (label, notes, parent, etc.)
     * @return void
     */
    public function syncMetadataAcrossPages(PdfPageAnnotation $annotation, array $updateData): void
    {
        // Determine the entity ID field to match on
        $entityField = match($annotation->annotation_type) {
            'room' => 'room_id',
            'location' => 'room_location_id',
            'cabinet_run' => 'cabinet_run_id',
            'cabinet' => 'cabinet_specification_id',
            default => null,
        };

        if (!$entityField || !$annotation->$entityField) {
            return; // No entity to match, skip sync
        }

        $entityId = $annotation->$entityField;

        // Get PDF document to search across all pages
        $pdfDocumentId = $annotation->pdfPage->document_id ?? null;
        if (!$pdfDocumentId) {
            return;
        }

        // Find all other annotations of the same type with the same entity ID
        $siblingAnnotations = $this->getSiblingAnnotations($annotation, $entityField, $entityId, $pdfDocumentId);

        if ($siblingAnnotations->isEmpty()) {
            return; // No siblings to sync
        }

        // Prepare metadata to sync (exclude page-specific fields)
        $syncData = [
            'label'            => $updateData['label'],
            'notes'            => $updateData['notes'] ?? null,
            'parent_annotation_id' => $updateData['parent_annotation_id'] ?? null,
            'room_id'          => $updateData['room_id'] ?? null,
            'room_location_id' => $updateData['room_location_id'] ?? null,
            'cabinet_run_id'   => $updateData['cabinet_run_id'] ?? null,
            'cabinet_specification_id' => $updateData['cabinet_specification_id'] ?? null,
            // NOTE: We do NOT sync view_type, view_orientation, x, y, width, height
            // Those are page-specific
        ];

        // Handle parent relationships specially across pages
        if ($syncData['parent_annotation_id']) {
            $this->syncWithParentMapping($annotation, $siblingAnnotations, $syncData);
        } else {
            // No parent, just sync metadata directly
            $this->syncDirectly($siblingAnnotations, $syncData);
        }
    }

    /**
     * Get all sibling annotations (same entity, different pages)
     *
     * @param PdfPageAnnotation $annotation The source annotation
     * @param string $entityField The entity ID field name
     * @param int $entityId The entity ID
     * @param int $pdfDocumentId The PDF document ID
     * @return Collection Collection of sibling annotations
     */
    protected function getSiblingAnnotations(
        PdfPageAnnotation $annotation,
        string $entityField,
        int $entityId,
        int $pdfDocumentId
    ): Collection {
        return PdfPageAnnotation::whereHas('pdfPage', function ($query) use ($pdfDocumentId) {
                $query->where('document_id', $pdfDocumentId);
            })
            ->where('annotation_type', $annotation->annotation_type)
            ->where($entityField, $entityId)
            ->where('id', '!=', $annotation->id) // Exclude the one we just updated
            ->get();
    }

    /**
     * Sync metadata with parent relationship mapping across pages
     * The parent on page 2 might be annotation ID 10, but on page 3 it might be ID 25
     * We need to find the equivalent parent on each page
     *
     * @param PdfPageAnnotation $annotation The source annotation
     * @param Collection $siblingAnnotations The sibling annotations to sync
     * @param array $syncData The data to sync
     * @return void
     */
    protected function syncWithParentMapping(
        PdfPageAnnotation $annotation,
        Collection $siblingAnnotations,
        array $syncData
    ): void {
        $parentAnnotation = PdfPageAnnotation::find($syncData['parent_annotation_id']);

        if (!$parentAnnotation) {
            // Parent not found, sync without parent
            $this->syncDirectly($siblingAnnotations, $syncData);
            return;
        }

        // Get the parent's entity field
        $parentEntityField = match($parentAnnotation->annotation_type) {
            'room' => 'room_id',
            'location' => 'room_location_id',
            'cabinet_run' => 'cabinet_run_id',
            'cabinet' => 'cabinet_specification_id',
            default => null,
        };

        if (!$parentEntityField || !$parentAnnotation->$parentEntityField) {
            // No parent entity, sync without parent
            $this->syncDirectly($siblingAnnotations, $syncData);
            return;
        }

        $parentEntityId = $parentAnnotation->$parentEntityField;

        // For each sibling, find the equivalent parent on its page
        foreach ($siblingAnnotations as $sibling) {
            $syncDataForSibling = $syncData;

            // Find parent annotation on sibling's page with same entity
            $equivalentParent = PdfPageAnnotation::where('pdf_page_id', $sibling->pdf_page_id)
                ->where('annotation_type', $parentAnnotation->annotation_type)
                ->where($parentEntityField, $parentEntityId)
                ->first();

            if ($equivalentParent) {
                $syncDataForSibling['parent_annotation_id'] = $equivalentParent->id;
            } else {
                // If parent doesn't exist on this page, keep it null
                $syncDataForSibling['parent_annotation_id'] = null;
            }

            $sibling->update($syncDataForSibling);

            Log::info('Synced annotation metadata across pages', [
                'from_annotation_id' => $annotation->id,
                'from_page' => $annotation->pdfPage->page_number,
                'to_annotation_id' => $sibling->id,
                'to_page' => $sibling->pdfPage->page_number,
                'entity_type' => $annotation->annotation_type,
                'entity_id' => $sibling->{$this->getEntityIdFieldForType($annotation->annotation_type)},
            ]);
        }
    }

    /**
     * Sync metadata directly without parent mapping
     *
     * @param Collection $siblingAnnotations The sibling annotations to sync
     * @param array $syncData The data to sync
     * @return void
     */
    protected function syncDirectly(Collection $siblingAnnotations, array $syncData): void
    {
        // Remove parent from sync data
        $syncDataWithoutParent = array_diff_key($syncData, ['parent_annotation_id' => null]);

        foreach ($siblingAnnotations as $sibling) {
            $sibling->update($syncDataWithoutParent);
        }
    }

    /**
     * Sync entity name to annotation label
     *
     * @param Model $entity The entity model
     * @param PdfPageAnnotation $annotation The annotation to update
     * @return void
     */
    public function syncEntityNameToAnnotation(Model $entity, PdfPageAnnotation $annotation): void
    {
        if ($annotation->label !== $entity->name) {
            $annotation->update(['label' => $entity->name]);
        }
    }

    /**
     * Sync entity name across all annotations linked to this entity
     *
     * @param Model $entity The entity model
     * @param string $annotationType The annotation type
     * @return void
     */
    public function syncEntityNameAcrossAllViews(Model $entity, string $annotationType): void
    {
        $entityField = $this->getEntityIdFieldForType($annotationType);

        $annotations = PdfPageAnnotation::where('annotation_type', $annotationType)
            ->where($entityField, $entity->id)
            ->get();

        foreach ($annotations as $annotation) {
            $this->syncEntityNameToAnnotation($entity, $annotation);
        }
    }

    /**
     * Get entity ID field for annotation type (helper method)
     *
     * @param string $annotationType The annotation type
     * @return string The entity ID field name
     */
    protected function getEntityIdFieldForType(string $annotationType): string
    {
        return match($annotationType) {
            'room' => 'room_id',
            'location' => 'room_location_id',
            'cabinet_run' => 'cabinet_run_id',
            'cabinet' => 'cabinet_specification_id',
            default => throw new \InvalidArgumentException("Unknown annotation type: {$annotationType}"),
        };
    }
}
