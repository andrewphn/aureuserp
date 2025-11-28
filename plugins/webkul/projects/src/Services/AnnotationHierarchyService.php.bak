<?php

namespace Webkul\Project\Services;

use App\Models\PdfPage;
use App\Models\PdfPageAnnotation;

class AnnotationHierarchyService
{
    /**
     * Get room_id by traversing parent annotation chain
     */
    public static function getRoomIdFromParent(?int $parentAnnotationId): ?int
    {
        if (!$parentAnnotationId) {
            return null;
        }

        $annotation = PdfPageAnnotation::find($parentAnnotationId);

        if (!$annotation) {
            return null;
        }

        // If this annotation is a room, return its room_id
        if ($annotation->annotation_type === 'room') {
            return $annotation->room_id;
        }

        // Otherwise, recursively check parent
        if ($annotation->parent_annotation_id) {
            return self::getRoomIdFromParent($annotation->parent_annotation_id);
        }

        // Fallback: return the annotation's room_id if it has one
        return $annotation->room_id;
    }

    /**
     * Get room_location_id from parent location annotation
     */
    public static function getRoomLocationIdFromParent(?int $parentAnnotationId): ?int
    {
        if (!$parentAnnotationId) {
            return null;
        }

        $annotation = PdfPageAnnotation::find($parentAnnotationId);

        if (!$annotation) {
            return null;
        }

        // If this annotation is a location, return its room_location_id
        if ($annotation->annotation_type === 'location') {
            return $annotation->room_location_id;
        }

        // Otherwise, recursively check parent
        if ($annotation->parent_annotation_id) {
            return self::getRoomLocationIdFromParent($annotation->parent_annotation_id);
        }

        return null;
    }

    /**
     * Get cabinet_run_id from parent cabinet_run annotation
     */
    public static function getCabinetRunIdFromParent(?int $parentAnnotationId): ?int
    {
        if (!$parentAnnotationId) {
            return null;
        }

        $annotation = PdfPageAnnotation::find($parentAnnotationId);

        if (!$annotation) {
            return null;
        }

        // If this annotation is a cabinet_run, return its cabinet_run_id
        if ($annotation->annotation_type === 'cabinet_run') {
            return $annotation->cabinet_run_id;
        }

        // Otherwise, recursively check parent
        if ($annotation->parent_annotation_id) {
            return self::getCabinetRunIdFromParent($annotation->parent_annotation_id);
        }

        return null;
    }

    /**
     * Build hierarchy path from annotation ID up to root
     */
    public static function buildHierarchyPath(int|string $annotationId, array $path = []): array
    {
        // Don't query if it's a temp ID
        if (is_string($annotationId) && str_starts_with($annotationId, 'temp_')) {
            return $path;
        }

        $annotation = PdfPageAnnotation::find($annotationId);

        if (!$annotation) {
            return $path;
        }

        // Add current annotation to path
        array_unshift($path, [
            'id' => $annotation->id,
            'label' => $annotation->label,
            'type' => $annotation->annotation_type,
        ]);

        // Recursively get parent
        if ($annotation->parent_annotation_id) {
            return self::buildHierarchyPath($annotation->parent_annotation_id, $path);
        }

        return $path;
    }

    /**
     * Get HTML for hierarchy breadcrumb display
     */
    public static function getHierarchyPathHtml(int|string $annotationId): string
    {
        if (empty($annotationId)) {
            return '<span class="text-gray-500">New annotation</span>';
        }

        $path = self::buildHierarchyPath($annotationId);

        if (empty($path)) {
            return '<span class="text-gray-500">Top level</span>';
        }

        $breadcrumbs = [];
        foreach ($path as $item) {
            $color = match($item['type']) {
                'room' => 'bg-blue-100 text-blue-800',
                'location' => 'bg-green-100 text-green-800',
                'cabinet_run' => 'bg-purple-100 text-purple-800',
                'cabinet' => 'bg-orange-100 text-orange-800',
                default => 'bg-gray-100 text-gray-800',
            };

            $breadcrumbs[] = sprintf(
                '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium %s">%s</span>',
                $color,
                htmlspecialchars($item['label'])
            );
        }

        return implode(' <span class="text-gray-400">→</span> ', $breadcrumbs);
    }

    /**
     * Get available parent annotations based on annotation type
     *
     * @param int|null $projectId The project ID
     * @param string|null $annotationType The type of annotation (room, location, cabinet_run, cabinet)
     * @param int|null $pdfPageId The PDF page ID
     * @param int|string|null $currentAnnotationId The current annotation ID (to exclude from list)
     * @return array Array of parent options [id => label]
     */
    public static function getAvailableParents(
        ?int $projectId,
        ?string $annotationType,
        ?int $pdfPageId,
        int|string|null $currentAnnotationId
    ): array {
        if (!$projectId || !$annotationType) {
            return [];
        }

        // Get PDF page ID from original annotation
        if (!$pdfPageId) {
            return [];
        }

        // Get PDF document ID to search across all pages
        $pdfPage = PdfPage::find($pdfPageId);
        if (!$pdfPage || !$pdfPage->document_id) {
            return [];
        }

        // Determine valid parent types based on annotation type
        $validParentTypes = match($annotationType) {
            'location' => ['room'],
            'cabinet_run' => ['location'],
            'cabinet' => ['cabinet_run'],
            default => [],
        };

        if (empty($validParentTypes)) {
            return [];
        }

        // Query annotations across ALL pages in the same PDF document
        // This allows locations on page 3 to have room parents from page 2
        return PdfPageAnnotation::whereHas('pdfPage', function ($query) use ($pdfPage) {
                $query->where('document_id', $pdfPage->document_id);
            })
            ->whereIn('annotation_type', $validParentTypes)
            ->where('id', '!=', $currentAnnotationId ?? 0) // Exclude self
            ->orderBy('label')
            ->get()
            ->mapWithKeys(function ($annotation) {
                // Include page number in label for context
                $pageNumber = $annotation->pdfPage->page_number ?? '?';
                return [$annotation->id => $annotation->label . ' (Page ' . $pageNumber . ')'];
            })
            ->toArray();
    }
}
