<?php

namespace Webkul\Project\Services;

use App\Models\PdfPage;
use App\Models\PdfPageAnnotation;
use Illuminate\Support\HtmlString;
use Webkul\Project\Models\CabinetRun;
use Webkul\Project\Models\RoomLocation;

/**
 * Entity Detection Service service
 *
 */
class EntityDetectionService
{
    /**
     * Get entity detection status to display in form
     * Shows whether annotation will create new entity or link to existing one
     *
     * @param string|null $annotationType The type of annotation (location, cabinet_run)
     * @param string|null $label The label entered by user
     * @param int|null $parentAnnotationId The parent annotation ID
     * @param array $originalAnnotation The original annotation data
     * @return HtmlString
     */
    public static function getEntityDetectionStatus(
        ?string $annotationType,
        ?string $label,
        ?int $parentAnnotationId,
        array $originalAnnotation
    ): HtmlString {
        if (empty($label)) {
            return new HtmlString(
                '<span class="text-gray-500 dark:text-gray-400">Enter a label to check entity status</span>'
            );
        }

        // For locations: check if RoomLocation exists with same name + room_id
        if ($annotationType === 'location') {
            return self::detectLocationEntity($label, $parentAnnotationId, $originalAnnotation);
        }

        // For cabinet_runs: check if CabinetRun exists with same name + room_location_id
        if ($annotationType === 'cabinet_run') {
            return self::detectCabinetRunEntity($label, $parentAnnotationId, $originalAnnotation);
        }

        return new HtmlString('');
    }

    /**
     * Detect if a location entity exists or will be created
     */
    protected static function detectLocationEntity(
        string $label,
        ?int $parentAnnotationId,
        array $originalAnnotation
    ): HtmlString {
        $roomId = AnnotationHierarchyService::getRoomIdFromParent($parentAnnotationId);

        if (!$roomId) {
            return new HtmlString(
                '<span class="text-gray-500 dark:text-gray-400">Select a parent room first</span>'
            );
        }

        $existingLocation = RoomLocation::where('room_id', $roomId)
            ->where('name', $label)
            ->first();

        if ($existingLocation) {
            $pages = self::getPagesUsingEntity($originalAnnotation, 'room_location_id', $existingLocation->id);
            return self::buildExistingEntityHtml(
                $existingLocation->name,
                $pages,
                'room_location_id',
                $existingLocation->id
            );
        }

        return self::buildNewEntityHtml('location', $label);
    }

    /**
     * Detect if a cabinet run entity exists or will be created
     */
    protected static function detectCabinetRunEntity(
        string $label,
        ?int $parentAnnotationId,
        array $originalAnnotation
    ): HtmlString {
        $roomLocationId = AnnotationHierarchyService::getRoomLocationIdFromParent($parentAnnotationId);

        if (!$roomLocationId) {
            return new HtmlString(
                '<span class="text-gray-500 dark:text-gray-400">Select a parent location first</span>'
            );
        }

        $existingCabinetRun = CabinetRun::where('room_location_id', $roomLocationId)
            ->where('name', $label)
            ->first();

        if ($existingCabinetRun) {
            $pages = self::getPagesUsingEntity($originalAnnotation, 'cabinet_run_id', $existingCabinetRun->id);
            return self::buildExistingEntityHtml(
                $existingCabinetRun->name,
                $pages,
                'cabinet_run_id',
                $existingCabinetRun->id
            );
        }

        return self::buildNewEntityHtml('cabinet run', $label);
    }

    /**
     * Get page numbers where annotations use a specific entity
     */
    protected static function getPagesUsingEntity(array $originalAnnotation, string $entityField, int $entityId): array
    {
        $pdfPage = PdfPage::find($originalAnnotation['pdfPageId'] ?? null);
        $pdfDocumentId = $pdfPage?->document_id;

        if (!$pdfDocumentId) {
            return [];
        }

        $annotations = PdfPageAnnotation::whereHas('pdfPage', function ($query) use ($pdfDocumentId) {
                $query->where('document_id', $pdfDocumentId);
            })
            ->where($entityField, $entityId)
            ->with('pdfPage')
            ->get();

        return $annotations->pluck('pdfPage.page_number')->unique()->sort()->values()->toArray();
    }

    /**
     * Build HTML for existing entity detection
     */
    protected static function buildExistingEntityHtml(string $entityName, array $pages, string $fieldName, int $entityId): HtmlString
    {
        $pagesStr = !empty($pages) ? ' (Pages: ' . implode(', ', $pages) . ')' : '';

        return new HtmlString(
            '<div class="flex flex-col gap-2">' .
            '<div class="flex items-center gap-2">' .
            '<svg class="w-5 h-5 text-success-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">' .
            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>' .
            '</svg>' .
            '<span class="text-success-600 dark:text-success-400">' .
            'Will link to existing: <strong>' . e($entityName) . '</strong>' . $pagesStr .
            '</span>' .
            '</div>' .
            '<button ' .
            'type="button" ' .
            'wire:click="linkToExistingEntity(\'' . $fieldName . '\', ' . $entityId . ')" ' .
            'class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-primary fi-size-sm fi-btn-size-sm gap-1 px-3 py-2 text-sm inline-grid shadow-sm bg-custom-600 text-white hover:bg-custom-500 focus-visible:ring-custom-500/50 dark:bg-custom-500 dark:hover:bg-custom-400 dark:focus-visible:ring-custom-400/50 fi-ac-btn-action" ' .
            'style="--c-400:var(--primary-400);--c-500:var(--primary-500);--c-600:var(--primary-600);">' .
            '<svg class="fi-btn-icon transition duration-75 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">' .
            '<path d="M12.232 4.232a2.5 2.5 0 013.536 3.536l-1.225 1.224a.75.75 0 001.061 1.06l1.224-1.224a4 4 0 00-5.656-5.656l-3 3a4 4 0 00.225 5.865.75.75 0 00.977-1.138 2.5 2.5 0 01-.142-3.667l3-3z"/>' .
            '<path d="M11.603 7.963a.75.75 0 00-.977 1.138 2.5 2.5 0 01.142 3.667l-3 3a2.5 2.5 0 01-3.536-3.536l1.225-1.224a.75.75 0 00-1.061-1.06l-1.224 1.224a4 4 0 105.656 5.656l3-3a4 4 0 00-.225-5.865z"/>' .
            '</svg>' .
            '<span class="fi-btn-label">Auto-Link to Existing</span>' .
            '</button>' .
            '</div>'
        );
    }

    /**
     * Build HTML for new entity creation
     */
    protected static function buildNewEntityHtml(string $entityType, string $label): HtmlString
    {
        return new HtmlString(
            '<div class="flex items-center gap-2">' .
            '<svg class="w-5 h-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">' .
            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>' .
            '</svg>' .
            '<span class="text-primary-600 dark:text-primary-400">' .
            'Will create new ' . $entityType . ': <strong>' . e($label) . '</strong>' .
            '</span>' .
            '</div>'
        );
    }
}
