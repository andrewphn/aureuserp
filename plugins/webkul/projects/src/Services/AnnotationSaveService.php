<?php

namespace Webkul\Project\Services;

use App\Models\PdfAnnotationHistory;
use App\Models\PdfPage;
use App\Models\PdfPageAnnotation;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Project\Models\Cabinet;
use Webkul\Project\Models\Room;
use Webkul\Project\Utils\PositionInferenceUtil;

/**
 * Annotation Save Service service
 *
 */
class AnnotationSaveService
{
    protected EntityManagementService $entityManagement;

    protected AnnotationSyncService $annotationSync;

    /**
     * Create a new AnnotationSaveService instance
     *
     */
    public function __construct()
    {
        $this->entityManagement = new EntityManagementService;
        $this->annotationSync = new AnnotationSyncService;
    }

    /**
     * Main entry point for saving annotations
     *
     * @param  array  $formData  The validated form data
     * @param  array  $originalAnnotation  The original annotation data from Alpine.js
     * @param  int  $projectId  The project ID
     * @param  int  $pdfPageId  The PDF page ID
     * @param  string  $linkMode  The link mode ('existing' or 'create')
     * @param  int|null  $linkedEntityId  The linked entity ID if linking to existing
     * @return PdfPageAnnotation The created or updated annotation
     *
     * @throws \Exception If save fails
     */
    /**
     * Save Annotation
     *
     * @param array $formData
     * @param array $originalAnnotation
     * @param int $projectId
     * @param int $pdfPageId
     * @param string $linkMode
     * @param ?int $linkedEntityId
     * @return PdfPageAnnotation
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
     * @param  array  $formData  The form data
     * @param  array  $originalAnnotation  The original annotation
     */
    /**
     * Validate Duplicate View
     *
     * @param array $formData
     * @param array $originalAnnotation
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

        if (! $locationId || ! $viewType || ! $pdfPageId) {
            return;
        }

        $pdfPage = PdfPage::find($pdfPageId);
        if (! $pdfPage || ! $pdfPage->document_id) {
            return;
        }

        $takenViews = ViewTypeTrackerService::getLocationViewTypes($locationId, $pdfPage->document_id);

        // Build the key to check
        $checkKey = $viewType;
        if (in_array($viewType, ['elevation', 'section']) && $viewOrientation) {
            $checkKey = $viewType.'-'.$viewOrientation;
        }

        // Check if this view combination already exists
        if (isset($takenViews[$checkKey]) && ! empty($takenViews[$checkKey])) {
            $pages = implode(', ', $takenViews[$checkKey]);
            $viewLabel = $viewType === 'plan'
                ? 'Plan View'
                : ucfirst($viewType).' View'.($viewOrientation ? ' - '.$viewOrientation : '');

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
    /**
     * Create Annotation
     *
     * @param array $formData
     * @param array $originalAnnotation
     * @param int $projectId
     * @param int $pdfPageId
     * @param string $annotationType
     * @param string $linkMode
     * @param ?int $linkedEntityId
     * @return PdfPageAnnotation
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
            'pdf_page_id'              => $pdfPageId,
            'annotation_type'          => $annotationType,
            'label'                    => $entityResult['label'],
            'notes'                    => $formData['notes'] ?? '',
            'parent_annotation_id'     => $parentAnnotationId,
            'room_id'                  => $roomId,
            'room_location_id'         => $entityResult['room_location_id'] ?? null,
            'cabinet_run_id'           => $entityResult['cabinet_run_id'] ?? null,
            'cabinet_id' => $entityResult['cabinet_id'] ?? null,
            'x'                        => $originalAnnotation['normalizedX'] ?? 0,
            'y'                        => $normalizedY,
            'width'                    => $normalizedWidth,
            'height'                   => $normalizedHeight,
            'color'                    => $originalAnnotation['color'] ?? '#f59e0b',
            'view_type'                => $formData['view_type'] ?? 'plan',
            'view_orientation'         => $formData['view_orientation'] ?? null,
            'view_scale'               => $originalAnnotation['viewScale'] ?? null,
            'inferred_position'        => $positionData['inferred_position'],
            'vertical_zone'            => $positionData['vertical_zone'],
            'measurement_width'        => $formData['measurement_width'] ?? null,
            'measurement_height'       => $formData['measurement_height'] ?? null,
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
    /**
     * Update Annotation
     *
     * @param int $annotationId
     * @param array $formData
     * @param array $originalAnnotation
     * @param string $annotationType
     * @param string $linkMode
     * @param ?int $linkedEntityId
     * @return PdfPageAnnotation
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

        // Update cabinet entity hierarchy if linking to existing entity
        if ($linkMode === 'existing' && $linkedEntityId && $annotationType === 'cabinet') {
            $this->updateCabinetHierarchy($linkedEntityId, $annotation, $formData);
        }

        // Prepare update data
        $updateData = [
            'label'                    => $entityResult['label'],
            'notes'                    => $formData['notes'] ?? '',
            'parent_annotation_id'     => $parentAnnotationId,
            'room_id'                  => $roomId,
            'room_location_id'         => $entityResult['room_location_id'] ?? null,
            'cabinet_run_id'           => $entityResult['cabinet_run_id'] ?? null,
            'cabinet_id' => $entityResult['cabinet_id'] ?? null,
            'view_type'                => $formData['view_type'] ?? 'plan',
            'view_orientation'         => $formData['view_orientation'] ?? null,
            'view_scale'               => $formData['view_scale'] ?? null,  // Allow updating view scale
            'measurement_width'        => $formData['measurement_width'] ?? null,
            'measurement_height'       => $formData['measurement_height'] ?? null,
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
    /**
     * Handle Entity Linking
     *
     * @param array $formData
     * @param string $annotationType
     * @param int $projectId
     * @param ?int $parentAnnotationId
     * @param string $linkMode
     * @param ?int $linkedEntityId
     * @return array
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
            'label'                    => $formData['label'] ?? '',
            'room_id'                  => null,
            'room_location_id'         => null,
            'cabinet_run_id'           => null,
            'cabinet_id' => null,
        ];

        // Handle linking to existing entity
        if ($linkMode === 'existing' && $linkedEntityId) {
            return $this->linkToExistingEntity($annotationType, $linkedEntityId, $result);
        }

        // Handle creating new entity
        if ($linkMode === 'create' && isset($formData['entity']) && ! empty($formData['entity'])) {
            $entityData = $formData['entity'];

            // Get the correct name field for this entity type
            $nameField = $this->entityManagement->getEntityNameField($annotationType);
            $entityName = $entityData[$nameField] ?? $formData['label'];

            // Get parent entity ID based on type
            $parentEntityId = $this->getParentEntityId($annotationType, $parentAnnotationId);

            // Check if entity already exists (multi-view support)
            $existingEntity = $this->entityManagement->findExistingEntity($annotationType, $entityName, $parentEntityId);

            if ($existingEntity) {
                // Reuse existing entity
                return $this->linkToExistingEntity($annotationType, $existingEntity->id, $result);
            } else {
                // Create new entity
                $entity = $this->entityManagement->createEntity($annotationType, $entityData, $projectId, $parentEntityId);

                return $this->linkToExistingEntity($annotationType, $entity->id, $result);
            }
        }

        return $result;
    }

    /**
     * Handle entity updates for existing annotations
     */
    /**
     * Handle Entity Update
     *
     * @param PdfPageAnnotation $annotation
     * @param array $formData
     * @param string $annotationType
     * @param string $linkMode
     * @param ?int $linkedEntityId
     * @return array
     */
    protected function handleEntityUpdate(
        PdfPageAnnotation $annotation,
        array $formData,
        string $annotationType,
        string $linkMode,
        ?int $linkedEntityId
    ): array {
        $result = [
            'label'                    => $formData['label'] ?? $annotation->label,
            'room_location_id'         => $annotation->room_location_id,
            'cabinet_run_id'           => $annotation->cabinet_run_id,
            'cabinet_id' => $annotation->cabinet_id,
        ];

        $entityIdField = $this->entityManagement->getEntityIdField($annotationType);
        $currentEntityId = $annotation->$entityIdField;

        \Log::info('🔄 handleEntityUpdate called', [
            'annotation_id' => $annotation->id,
            'annotation_type' => $annotationType,
            'link_mode' => $linkMode,
            'linked_entity_id' => $linkedEntityId,
            'current_entity_id' => $currentEntityId,
            'entity_id_field' => $entityIdField,
        ]);

        // Handle switching to different entity
        if ($linkMode === 'existing' && $linkedEntityId && $linkedEntityId !== $currentEntityId) {
            \Log::info('✅ Switching to different entity', [
                'from' => $currentEntityId,
                'to' => $linkedEntityId,
            ]);
            return $this->linkToExistingEntity($annotationType, $linkedEntityId, $result);
        }

        // Handle updating existing entity
        if ($linkMode === 'create' && $currentEntityId && isset($formData['entity']) && ! empty($formData['entity'])) {
            $entityData = $formData['entity'];
            $this->entityManagement->updateEntity($annotationType, $currentEntityId, $entityData);

            // Load updated entity to get new name
            $entity = $this->entityManagement->loadEntity($annotationType, $currentEntityId);
            if ($entity) {
                // Use the correct name field for the entity type
                $result['label'] = $this->entityManagement->getEntityName($annotationType, $entity);
            }
        }

        return $result;
    }

    /**
     * Helper method to link annotation to existing entity
     * Consolidates duplicate logic from handleEntityLinking and handleEntityUpdate
     */
    /**
     * Link To Existing Entity
     *
     * @param string $annotationType
     * @param int $entityId
     * @param array $result
     * @return array
     */
    protected function linkToExistingEntity(string $annotationType, int $entityId, array $result): array
    {
        $entity = $this->entityManagement->loadEntity($annotationType, $entityId);
        if ($entity) {
            // Use the correct name field for the entity type
            $result['label'] = $this->entityManagement->getEntityName($annotationType, $entity);
            $entityIdField = $this->entityManagement->getEntityIdField($annotationType);
            $result[$entityIdField] = $entityId;

            // CRITICAL: Copy hierarchy IDs from entity to annotation
            // This ensures the annotation has the same hierarchy context as the entity
            if ($annotationType === 'room') {
                $result['room_id'] = $entityId;
            } elseif ($annotationType === 'location') {
                $result['room_location_id'] = $entityId;
                $result['room_id'] = $entity->room_id ?? null;
            } elseif ($annotationType === 'cabinet_run') {
                $result['cabinet_run_id'] = $entityId;
                $result['room_location_id'] = $entity->room_location_id ?? null;
                $result['room_id'] = $entity->roomLocation->room_id ?? null;
            } elseif ($annotationType === 'cabinet') {
                $result['cabinet_id'] = $entityId;
                $result['cabinet_run_id'] = $entity->cabinet_run_id ?? null;

                // Get location and room IDs from cabinet entity or traverse cabinet run relationship
                $result['room_location_id'] = $entity->room_location_id ?? $entity->cabinetRun->room_location_id ?? null;
                $result['room_id'] = $entity->room_id ?? $entity->cabinetRun->roomLocation->room_id ?? null;
            }

            \Log::info('🔗 linkToExistingEntity result', [
                'annotation_type' => $annotationType,
                'entity_id' => $entityId,
                'entity_id_field' => $entityIdField,
                'new_label' => $result['label'],
                'hierarchy_copied' => [
                    'room_id' => $result['room_id'] ?? null,
                    'room_location_id' => $result['room_location_id'] ?? null,
                    'cabinet_run_id' => $result['cabinet_run_id'] ?? null,
                ],
                'result' => $result,
            ]);
        }

        return $result;
    }

    /**
     * Update cabinet entity with hierarchy IDs from annotation context
     * This ensures cabinet entities have proper parent relationships for tree display
     */
    /**
     * Update Cabinet Hierarchy
     *
     * @param int $cabinetId
     * @param PdfPageAnnotation $annotation
     * @param array $formData
     * @return void
     */
    protected function updateCabinetHierarchy(int $cabinetId, PdfPageAnnotation $annotation, array $formData): void
    {
        $cabinet = Cabinet::find($cabinetId);
        if (!$cabinet) {
            return;
        }

        // Get hierarchy IDs from form data (set by annotation editor)
        $roomId = $formData['entity']['room_id'] ?? null;
        $roomLocationId = $formData['entity']['room_location_id'] ?? null;
        $cabinetRunId = $formData['entity']['cabinet_run_id'] ?? null;
        $projectId = $annotation->pdfPage->document->project_id ?? null;

        // Fallback to annotation hierarchy if form data not available
        if (!$roomId) {
            $roomId = $annotation->room_id ?? AnnotationHierarchyService::getRoomIdFromParent($annotation->parent_annotation_id);
        }
        if (!$roomLocationId) {
            $roomLocationId = $annotation->room_location_id ?? AnnotationHierarchyService::getRoomLocationIdFromParent($annotation->parent_annotation_id);
        }
        if (!$cabinetRunId) {
            $cabinetRunId = $annotation->cabinet_run_id ?? AnnotationHierarchyService::getCabinetRunIdFromParent($annotation->parent_annotation_id);
        }

        // Build update data - only update NULL fields to avoid breaking existing relationships
        $updateData = [];
        if (is_null($cabinet->project_id) && $projectId) {
            $updateData['project_id'] = $projectId;
        }
        if (is_null($cabinet->room_id) && $roomId) {
            $updateData['room_id'] = $roomId;
        }
        if (is_null($cabinet->room_location_id) && $roomLocationId) {
            $updateData['room_location_id'] = $roomLocationId;
        }
        if (is_null($cabinet->cabinet_run_id) && $cabinetRunId) {
            $updateData['cabinet_run_id'] = $cabinetRunId;
        }

        // Update cabinet if we have any hierarchy IDs to set
        if (!empty($updateData)) {
            \Log::info('Updating cabinet hierarchy IDs', [
                'cabinet_id' => $cabinetId,
                'updateData' => $updateData,
                'before' => [
                    'project_id' => $cabinet->project_id,
                    'room_id' => $cabinet->room_id,
                    'room_location_id' => $cabinet->room_location_id,
                    'cabinet_run_id' => $cabinet->cabinet_run_id,
                ]
            ]);

            $cabinet->update($updateData);
        }
    }

    /**
     * Get parent entity ID for annotation type
     */
    /**
     * Get Parent Entity Id
     *
     * @param string $annotationType
     * @param ?int $parentAnnotationId
     * @return ?int
     */
    protected function getParentEntityId(string $annotationType, ?int $parentAnnotationId): ?int
    {
        return match ($annotationType) {
            'location'    => AnnotationHierarchyService::getRoomIdFromParent($parentAnnotationId),
            'cabinet_run' => AnnotationHierarchyService::getRoomLocationIdFromParent($parentAnnotationId),
            'cabinet'     => AnnotationHierarchyService::getCabinetRunIdFromParent($parentAnnotationId),
            default       => null,
        };
    }

    /**
     * Auto-create cabinet_run if cabinet is created directly under a location
     */
    /**
     * Auto Create Cabinet Run
     *
     * @param int $parentAnnotationId
     * @param array $formData
     * @param array $originalAnnotation
     * @param int $pdfPageId
     * @param ?int $roomId
     * @param float $normalizedY
     * @param float $normalizedWidth
     * @param float $normalizedHeight
     * @param array $positionData
     * @return int
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
        if (! $parentAnnotation || $parentAnnotation->annotation_type !== 'location') {
            return $parentAnnotationId;
        }

        // Create a cabinet run annotation
        $newCabinetRunId = DB::table('pdf_page_annotations')->insertGetId([
            'pdf_page_id'          => $pdfPageId,
            'parent_annotation_id' => $parentAnnotationId,
            'annotation_type'      => 'cabinet_run',
            'label'                => ($formData['label'] ?? 'Cabinet').' Run',
            'notes'                => 'Auto-created cabinet run for '.($formData['label'] ?? 'cabinet'),
            'room_id'              => $roomId,
            'room_location_id'     => $formData['room_location_id'] ?? null,
            'cabinet_run_id'       => $formData['cabinet_run_id'] ?? null,
            'x'                    => $originalAnnotation['normalizedX'] ?? 0,
            'y'                    => $normalizedY ?? 0,
            'width'                => $normalizedWidth ?? 100,
            'height'               => $normalizedHeight ?? 100,
            'color'                => $originalAnnotation['color'] ?? '#f59e0b',
            'view_type'            => $formData['view_type'] ?? 'plan',
            'view_orientation'     => $formData['view_orientation'] ?? null,
            'view_scale'           => $originalAnnotation['viewScale'] ?? null,
            'inferred_position'    => $positionData['inferred_position'] ?? null,
            'vertical_zone'        => $positionData['vertical_zone'] ?? null,
            'creator_id'           => auth()->id(),
            'created_at'           => now(),
            'updated_at'           => now(),
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
