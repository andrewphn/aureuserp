<?php

namespace App\Observers;

use App\Models\PdfPageAnnotation;
use Illuminate\Support\Facades\Auth;

/**
 * Pdf Page Annotation Observer class
 *
 */
class PdfPageAnnotationObserver
{
    /**
     * Handle the PdfPageAnnotation "created" event.
     */
    public function created(PdfPageAnnotation $annotation): void
    {
        if (!function_exists('activity')) {
            return;
        }

        activity()
            ->performedOn($annotation->pdfPage->document)
            ->causedBy(Auth::user())
            ->withProperties([
                'annotation_id' => $annotation->id,
                'annotation_type' => $annotation->annotation_type,
                'page_number' => $annotation->pdfPage->page_number,
            ])
            ->log('Annotation added');
    }

    /**
     * Handle the PdfPageAnnotation "updated" event.
     */
    public function updated(PdfPageAnnotation $annotation): void
    {
        if (!function_exists('activity')) {
            return;
        }

        activity()
            ->performedOn($annotation->pdfPage->document)
            ->causedBy(Auth::user())
            ->withProperties([
                'annotation_id' => $annotation->id,
                'annotation_type' => $annotation->annotation_type,
                'page_number' => $annotation->pdfPage->page_number,
            ])
            ->log('Annotation modified');
    }

    /**
     * Handle the PdfPageAnnotation "deleted" event.
     */
    public function deleted(PdfPageAnnotation $annotation): void
    {
        if (!function_exists('activity')) {
            return;
        }

        activity()
            ->performedOn($annotation->pdfPage->document)
            ->causedBy(Auth::user())
            ->withProperties([
                'annotation_id' => $annotation->id,
                'annotation_type' => $annotation->annotation_type,
                'page_number' => $annotation->pdfPage->page_number,
            ])
            ->log('Annotation deleted');
    }
}
