<?php

namespace Webkul\Project\Services;

use App\Models\PdfPageAnnotation;

/**
 * View Type Tracker Service service
 *
 */
class ViewTypeTrackerService
{
    /**
     * Get existing view types for a given location across all pages in the document
     *
     * @param int $locationId The room_location_id to check
     * @param int $pdfDocumentId The PDF document ID to search within
     * @return array Map of view combinations to page arrays
     *               For plan: ['plan' => [2]]
     *               For elevation/section: ['elevation-front' => [3], 'section-A-A' => [5]]
     *               For detail: ['detail' => [4, 6, 8]] (multiple allowed)
     */
    public static function getLocationViewTypes(int $locationId, int $pdfDocumentId): array
    {
        $annotations = PdfPageAnnotation::whereHas('pdfPage', function ($query) use ($pdfDocumentId) {
                $query->where('document_id', $pdfDocumentId);
            })
            ->where('room_location_id', $locationId)
            ->where('annotation_type', 'location')
            ->get();

        $result = [];
        foreach ($annotations as $ann) {
            // For elevation and section, include orientation in the key
            if (in_array($ann->view_type, ['elevation', 'section']) && $ann->view_orientation) {
                $key = $ann->view_type . '-' . $ann->view_orientation;
            } else {
                $key = $ann->view_type;
            }

            // Store as array of pages (to handle multiple detail views)
            if (!isset($result[$key])) {
                $result[$key] = [];
            }
            $result[$key][] = $ann->pdfPage->page_number;
        }

        return $result;
    }
}
