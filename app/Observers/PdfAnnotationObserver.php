<?php

namespace App\Observers;

use App\Models\PdfAnnotation;
use Illuminate\Support\Facades\Auth;

class PdfAnnotationObserver
{
    /**
     * Handle the PdfAnnotation "created" event.
     */
    public function created(PdfAnnotation $annotation): void
    {
        if (!function_exists('activity')) {
            return;
        }

        activity()
            ->performedOn($annotation->document)
            ->causedBy(Auth::user())
            ->withProperties([
                'annotation_id' => $annotation->id,
                'annotation_type' => $annotation->annotation_type,
                'page_number' => $annotation->page_number,
            ])
            ->log('Annotation added');
    }

    /**
     * Handle the PdfAnnotation "updated" event.
     */
    public function updated(PdfAnnotation $annotation): void
    {
        if (!function_exists('activity')) {
            return;
        }

        activity()
            ->performedOn($annotation->document)
            ->causedBy(Auth::user())
            ->withProperties([
                'annotation_id' => $annotation->id,
                'annotation_type' => $annotation->annotation_type,
                'page_number' => $annotation->page_number,
            ])
            ->log('Annotation modified');
    }

    /**
     * Handle the PdfAnnotation "deleted" event.
     */
    public function deleted(PdfAnnotation $annotation): void
    {
        if (!function_exists('activity')) {
            return;
        }

        activity()
            ->performedOn($annotation->document)
            ->causedBy(Auth::user())
            ->withProperties([
                'annotation_id' => $annotation->id,
                'annotation_type' => $annotation->annotation_type,
                'page_number' => $annotation->page_number,
            ])
            ->log('Annotation deleted');
    }
}
