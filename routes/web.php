<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PdfPreviewController;
use App\Http\Controllers\PdfAnnotationController;

if (! request()->getRequestUri() == '/login') {
    Route::redirect('/login', '/admin/login')
        ->name('login');
}

// PDF Preview Routes (server-side rendering via Nutrient Cloud API)
Route::get('/api/pdf/{pdfId}/page/{pageNumber}/render', [PdfPreviewController::class, 'renderPage'])
    ->name('pdf.render.page');
Route::get('/api/pdf/{pdfId}/page/{pageNumber}/render-base64', [PdfPreviewController::class, 'renderPageBase64'])
    ->name('pdf.render.page.base64');

// PDF Annotation Routes
Route::prefix('api/pdf/annotations')->group(function () {
    Route::get('/page/{pdfPageId}', [PdfAnnotationController::class, 'getPageAnnotations'])
        ->name('pdf.annotations.get');
    Route::post('/page/{pdfPageId}', [PdfAnnotationController::class, 'savePageAnnotations'])
        ->name('pdf.annotations.save');
    Route::post('/page/{pdfPageId}/create', [PdfAnnotationController::class, 'createAnnotation'])
        ->name('pdf.annotations.create');
    Route::patch('/{annotationId}/link', [PdfAnnotationController::class, 'linkAnnotation'])
        ->name('pdf.annotations.link');
    Route::delete('/{annotationId}', [PdfAnnotationController::class, 'deleteAnnotation'])
        ->name('pdf.annotations.delete');
    Route::get('/page/{pdfPageId}/cabinet-runs', [PdfAnnotationController::class, 'getAvailableCabinetRuns'])
        ->name('pdf.annotations.cabinet-runs');
    Route::get('/cabinet-run/{cabinetRunId}/cabinets', [PdfAnnotationController::class, 'getCabinetsInRun'])
        ->name('pdf.annotations.run-cabinets');

    // Save entire annotated PDF using Nutrient Processor API
    Route::post('/document/{pdfId}/save', [PdfAnnotationController::class, 'saveAnnotatedPdf'])
        ->name('pdf.annotations.save-document');
});
