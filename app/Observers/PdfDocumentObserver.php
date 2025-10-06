<?php

namespace App\Observers;

use App\Jobs\ProcessPdfJob;
use App\Models\PdfDocument;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Facades\LogActivity;

/**
 * Observer for PdfDocument model to log activities on the parent module
 * and dispatch background processing jobs
 */
class PdfDocumentObserver
{
    /**
     * Handle the PdfDocument "created" event.
     */
    public function created(PdfDocument $document): void
    {
        // Log activity on the parent module (Project, Quotation, etc.)
        if ($document->module) {
            LogActivity::performedOn($document->module)
                ->causedBy($document->uploader)
                ->withProperties([
                    'document_id' => $document->id,
                    'file_name' => $document->file_name,
                    'file_size' => $document->formatted_file_size,
                    'page_count' => $document->page_count,
                    'view_url' => Storage::url($document->file_path),
                ])
                ->log("uploaded PDF document \"{$document->file_name}\" ({$document->formatted_file_size})");
        }

        // Dispatch background job to process PDF (extract pages, generate thumbnails, etc.)
        ProcessPdfJob::dispatch($document);
    }

    /**
     * Handle the PdfDocument "deleted" event.
     */
    public function deleted(PdfDocument $document): void
    {
        // Log activity on the parent module
        if ($document->module) {
            LogActivity::performedOn($document->module)
                ->causedBy(auth()->user())
                ->withProperties([
                    'document_id' => $document->id,
                    'file_name' => $document->file_name,
                ])
                ->log("deleted PDF document \"{$document->file_name}\"");
        }

        // Clean up generated thumbnails
        if ($document->id) {
            $thumbnailDirectory = "pdf-thumbnails/{$document->id}";
            if (Storage::disk('public')->exists($thumbnailDirectory)) {
                Storage::disk('public')->deleteDirectory($thumbnailDirectory);
            }
        }
    }
}
