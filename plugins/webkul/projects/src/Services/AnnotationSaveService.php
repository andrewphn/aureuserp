<?php

namespace Webkul\Project\Services;

use App\Models\PdfPage;
use App\Models\PdfPageAnnotation;
use App\Models\PdfAnnotationHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification;
use Webkul\Project\Models\Room;
use Webkul\Project\Models\RoomLocation;
use Webkul\Project\Utils\PositionInferenceUtil;

class AnnotationSaveService
{
    protected EntityManagementService $entityManagement;
    protected AnnotationSyncService $annotationSync;

    public function __construct()
    {
        $this->entityManagement = new EntityManagementService();
        $this->annotationSync = new AnnotationSyncService();
    }

    /**
     * Main entry point for saving annotations
     *
     * @param array $formData The validated form data
     * @param array $originalAnnotation The original annotation data from Alpine.js
     * @param int $projectId The project ID
     * @param int $pdfPageId The PDF page ID
     * @param string $linkMode The link mode ('existing' or 'create')
     * @param int|null $linkedEntityId The linked entity ID if linking to existing
     * @return PdfPageAnnotation The created or updated annotation
     * @throws \Exception If save fails
     */
    public function saveAnnotation(
        array $formData,
        array $originalAnnotation,
        int $projectId,
        int $pdfPageId,
        string $linkMode,
        ?int $linkedEntityId
    ): PdfPageAnnotation {
        $annotationId = $originalAnnotation['id'];
        $annotationType = $originalAnnotation['type'] ?? 'room';

        // Check for duplicate views (for location annotations)
        $this->validateDuplicateView($formData, $originalAnnotation);

        // Determine if this is CREATE or UPDATE
        $isCreating = is_string($annotationId) && str_starts_with($annotationId, 'temp_');

        if ($isCreating) {
            return $this->createAnnotation($formData, $originalAnnotation, $projectId, $pdfPageId, $annotationType, $linkMode, $linkedEntityId);
        } else {
            return $this->updateAnnotation($annotationId, $formData, $originalAnnotation, $annotationType, $linkMode, $linkedEntityId);
        }
    }

    /**
     * Validate for duplicate view types
     *
     * @param array $formData The form data
     * @param array $originalAnnotation The original annotation
     * @return void
     */
    protected function validateDuplicateView(array $formData, array $originalAnnotation): void
    {
        if (($originalAnnotation['type'] ?? null) !== 'location') {
            return;
        }

        $locationId = $formData['linked_entity_id'] ?? $formData['room_location_id'] ?? null;
        $viewType = $formData['view_type'] ?? null;
        $viewOrientation = $formData['view_orientation'] ?? null;
        $pdfPageId = $originalAnnotation['pdfPageId'] ?? null;

        if (!$locationId || !$viewType || !$pdfPageId) {
            return;
        }

        $pdfPage = PdfPage::find($pdfPageId);
        if (!$pdfPage || !$pdfPage->document_id) {
            return;
        }

        $takenViews = ViewTypeTrackerService::getLocationViewTypes($locationId, $pdfPage->document_id);

        // Build the key to check
        $checkKey = $viewType;
        if (in_array($viewType, ['elevation', 'section']) && $viewOrientation) {
            $checkKey = $viewType . '-' . $viewOrientation;
        }

        // Check if this view combination already exists
        if (isset($takenViews[$checkKey]) && !empty($takenViews[$checkKey])) {
            $pages = implode(', ', $takenViews[$checkKey]);
            $viewLabel = $viewType === 'plan'
                ? 'Plan View'
                : ucfirst($viewType) . ' View' . ($viewOrientation ? ' - ' . $viewOrientation : '');

            Notification::make()
                ->title('Duplicate View Warning')
                ->body("A {$viewLabel} already exists for this location on page(s) {$pages}. You are creating an additional view.")
                ->warning()
                ->duration(8000)
                ->send();
        }
    }

    /**
     * Create a new annotation
     */
    protected function createAnnotation(
        array $formData,
        array $originalAnnotation,
        int $projectId,
        int $pdfPageId,
        string $annotationType,
        string $linkMode,
        ?int $linkedEntityId
    ): PdfPageAnnotation {
        // Get PDF page for dimension calculations
        $pdfPage = PdfPage::find($pdfPageId);
        $pageWidth = $pdfPage?->page_width ?? 2592;
        $pageHeight = $pdfPage?->page_height ?? 1728;

        // Calculate normalized dimensions
        $normalizedWidth = ($originalAnnotation['pdfWidth'] ?? 0) / $pageWidth;
        $normalizedHeight = ($originalAnnotation['pdfHeight'] ?? 0) / $pageHeight;
        $normalizedY = $originalAnnotation['normalizedY'] ?? 0;

        // Infer position from coordinates
        $positionData = PositionInferenceUtil::inferPositionFromCoordinates($normalizedY, $normalizedHeight);

        // Get parent annotation ID
        $parentAnnotationId = $formData['parent_annotation_id'] ?? null;

        // Handle entity linking/creation
        $entityResult = $this->handleEntityLinking(
            $formData,
            $annotationType,
            $projectId,
            $parentAnnotationId,
            $linkMode,
            $linkedEntityId
        );

        // Auto-calculate room_id from parent chain
        $roomId = $annotationType === 'room'
            ? $entityResult['room_id']
            : AnnotationHierarchyService::getRoomIdFromParent($parentAnnotationId);

        // Auto-create cabinet_run if cabinet is created directly under a location
        if ($annotationType === 'cabinet' && $parentAnnotationId) {
            $parentAnnotationId = $this->autoCreateCabinetRun(
                $parentAnnotationId,
                $formData,
                $originalAnnotation,
                $pdfPageId,
                $roomId,
                $normalizedY,
                $normalizedWidth,
                $normalizedHeight,
                $positionData
            );
            $formData['parent_annotation_id'] = $parentAnnotationId;
        }

        // Create the annotation
        $annotation = PdfPageAnnotation::create([
            'pdf_page_id'      => $pdfPageId,
            'annotation_type'  => $annotationType,
            'label'            => $entityResult['label'],
            'notes'            => $formData['notes'] ?? '',
            'parent_annotation_id' => $parentAnnotationId,
            'room_id'          => $roomId,
            'room_location_id' => $entityResult['room_location_id'] ?? null,
            'cabinet_run_id'   => $entityResult['cabinet_run_id'] ?? null,
            'cabinet_specification_id' => $entityResult['cabinet_specification_id'] ?? null,
            'x'                => $originalAnnotation['normalizedX'] ?? 0,
            'y'                => $normalizedY,
            'width'            => $normalizedWidth,
            'height'           => $normalizedHeight,
            'color'            => $originalAnnotation['color'] ?? '#f59e0b',
            'view_type'        => $formData['view_type'] ?? 'plan',
            'view_orientation' => $formData['view_orientation'] ?? null,
            'view_scale'       => $originalAnnotation['viewScale'] ?? null,
            'inferred_position' => $positionData['inferred_position'],
            'vertical_zone'    => $positionData['vertical_zone'],
        ]);

        // Log creation
        PdfAnnotationHistory::logAction(
            pdfPageId: $annotation->pdf_page_id,
            action: 'created',
            beforeData: null,
            afterData: $annotation->toArray(),
            annotationId: $annotation->id
        );

        return $annotation;
    }

    /**
     * Update an existing annotation
     */
    protected function updateAnnotation(
        int $annotationId,
        array $formData,
        array $originalAnnotation,
        string $annotationType,
        string $linkMode,
        ?int $linkedEntityId
    ): PdfPageAnnotation {
        $annotation = PdfPageAnnotation::findOrFail($annotationId);
        $beforeData = $annotation->toArray();

        // Get parent and calculate room_id
        $parentAnnotationId = $formData['parent_annotation_id'] ?? null;
        $roomId = $annotation->annotation_type === 'room'
            ? ($formData['entity']['room_id'] ?? $annotation->room_id)
            : AnnotationHierarchyService::getRoomIdFromParent($parentAnnotationId);

        // Handle entity updates
        $entityResult = $this->handleEntityUpdate(
            $annotation,
            $formData,
            $annotationType,
            $linkMode,
            $linkedEntityId
        );

        // Prepare update data
        $updateData = [
            'label'            => $entityResult['label'],
            'notes'            => $formData['notes'] ?? '',
            'parent_annotation_id' => $parentAnnotationId,
            'room_id'          => $roomId,
            'room_location_id' => $entityResult['room_location_id'] ?? null,
            'cabinet_run_id'   => $entityResult['cabinet_run_id'] ?? null,
            'cabinet_specification_id' => $entityResult['cabinet_specification_id'] ?? null,
            'view_type'        => $formData['view_type'] ?? 'plan',
            'view_orientation' => $formData['view_orientation'] ?? null,
        ];

        // Update annotation
        $annotation->update($updateData);

        // Sync metadata across all instances of this entity
        $this->annotationSync->syncMetadataAcrossPages($annotation, $updateData);

        // Log update
        PdfAnnotationHistory::logAction(
            pdfPageId: $annotation->pdf_page_id,
            action: 'updated',
            beforeData: $beforeData,
            afterData: $annotation->fresh()->toArray(),
            annotationId: $annotation->id
        );

        return $annotation;
    }

    /**
     * Handle entity linking or creation for new annotations
     */
    protected function handleEntityLinking(
        array $formData,
        string $annotationType,
        int $projectId,
        ?int $parentAnnotationId,
        string $linkMode,
        ?int $linkedEntityId
    ): array {
        $result = [
            'label' => $formData['label'] ?? '',
            'room_id' => null,
            'room_location_id' => null,
            'cabinet_run_id' => null,
            'cabinet_specification_id' => null,
        ];

        // Handle linking to existing entity
        if ($linkMode === 'existing' && $linkedEntityId) {
            return $this->linkToExistingEntity($annotationType, $linkedEntityId, $result);
        }

        // Handle creating new entity
        if ($linkMode === 'create' && isset($formData['entity']) && !empty($formData['entity'])) {
            $entityData = $formData['entity'];
            $entityName = $entityData['name'] ?? $formData['label'];

            // Get parent entity ID based on type
            $parentEntityId = $this->getParentEntityId($annotationType, $parentAnnotationId);

            // Check if entity already exists (multi-view support)
            $existingEntity = $this->entityManagement->findExistingEntity($annotationType, $entityName, $parentEntityId);

            if ($existingEntity) {
                // Reuse existing entity
                return $this->linkToExistingEntity($annotationType, $existingEntity->id, $result);
            } else {
                // Create new entity
                $entity = $this->entityManagement->createEntity($annotationType, $entityData, $projectId);
                return $this->linkToExistingEntity($annotationType, $entity->id, $result);
            }
        }

        return $result;
    }

    /**
     * Handle entity updates for existing annotations
     */
    protected function handleEntityUpdate(
        PdfPageAnnotation $annotation,
        array $formData,
        string $annotationType,
        string $linkMode,
        ?int $linkedEntityId
    ): array {
        $result = [
            'label' => $formData['label'] ?? $annotation->label,
            'room_location_id' => $annotation->room_location_id,
            'cabinet_run_id' => $annotation->cabinet_run_id,
            'cabinet_specification_id' => $annotation->cabinet_specification_id,
        ];

        $entityIdField = $this->entityManagement->getEntityIdField($annotationType);
        $currentEntityId = $annotation->$entityIdField;

        // Handle switching to different entity
        if ($linkMode === 'existing' && $linkedEntityId && $linkedEntityId !== $currentEntityId) {
            return $this->linkToExistingEntity($annotationType, $linkedEntityId, $result);
        }

        // Handle updating existing entity
        if ($linkMode === 'create' && $currentEntityId && isset($formData['entity']) && !empty($formData['entity'])) {
            $entityData = $formData['entity'];
            $this->entityManagement->updateEntity($annotationType, $currentEntityId, $entityData);

            // Load updated entity to get new name
            $entity = $this->entityManagement->loadEntity($annotationType, $currentEntityId);
            if ($entity) {
                $result['label'] = $entity->name;
            }
        }

        return $result;
    }

    /**
     * Helper method to link annotation to existing entity
     * Consolidates duplicate logic from handleEntityLinking and handleEntityUpdate
     */
    protected function linkToExistingEntity(string $annotationType, int $entityId, array $result): array
    {
        $entity = $this->entityManagement->loadEntity($annotationType, $entityId);
        if ($entity) {
            $result['label'] = $entity->name;
            $entityIdField = $this->entityManagement->getEntityIdField($annotationType);
            $result[$entityIdField] = $entityId;

            // For room entities, also set room_id
            if ($annotationType === 'room') {
                $result['room_id'] = $entityId;
            }
        }
        return $result;
    }

    /**
     * Get parent entity ID for annotation type
     */
    protected function getParentEntityId(string $annotationType, ?int $parentAnnotationId): ?int
    {
        return match($annotationType) {
            'location' => AnnotationHierarchyService::getRoomIdFromParent($parentAnnotationId),
            'cabinet_run' => AnnotationHierarchyService::getRoomLocationIdFromParent($parentAnnotationId),
            'cabinet' => AnnotationHierarchyService::getCabinetRunIdFromParent($parentAnnotationId),
            default => null,
        };
    }

    /**
     * Auto-create cabinet_run if cabinet is created directly under a location
     */
    protected function autoCreateCabinetRun(
        int $parentAnnotationId,
        array $formData,
        array $originalAnnotation,
        int $pdfPageId,
        ?int $roomId,
        float $normalizedY,
        float $normalizedWidth,
        float $normalizedHeight,
        array $positionData
    ): int {
        $parentAnnotation = DB::table('pdf_page_annotations')
            ->where('id', $parentAnnotationId)
            ->first();

        // Only auto-create if parent is a location (not a cabinet_run)
        if (!$parentAnnotation || $parentAnnotation->annotation_type !== 'location') {
            return $parentAnnotationId;
        }

        // Create a cabinet run annotation
        $newCabinetRunId = DB::table('pdf_page_annotations')->insertGetId([
            'pdf_page_id' => $pdfPageId,
            'parent_annotation_id' => $parentAnnotationId,
            'annotation_type' => 'cabinet_run',
            'label' => ($formData['label'] ?? 'Cabinet') . ' Run',
            'notes' => 'Auto-created cabinet run for ' . ($formData['label'] ?? 'cabinet'),
            'room_id' => $roomId,
            'room_location_id' => $formData['room_location_id'] ?? null,
            'cabinet_run_id' => $formData['cabinet_run_id'] ?? null,
            'x' => $originalAnnotation['normalizedX'] ?? 0,
            'y' => $normalizedY ?? 0,
            'width' => $normalizedWidth ?? 100,
            'height' => $normalizedHeight ?? 100,
            'color' => $originalAnnotation['color'] ?? '#f59e0b',
            'view_type' => $formData['view_type'] ?? 'plan',
            'view_orientation' => $formData['view_orientation'] ?? null,
            'view_scale' => $originalAnnotation['viewScale'] ?? null,
            'inferred_position' => $positionData['inferred_position'] ?? null,
            'vertical_zone' => $positionData['vertical_zone'] ?? null,
            'creator_id' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Notification::make()
            ->title('Auto-Created Cabinet Run')
            ->body("Created cabinet run \"{$formData['label']} Run\" to maintain proper hierarchy.")
            ->success()
            ->duration(5000)
            ->send();

        return $newCabinetRunId;
    }
}
