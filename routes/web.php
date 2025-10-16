<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PdfPreviewController;

if (! request()->getRequestUri() == '/login') {
    Route::redirect('/login', '/admin/login')
        ->name('login');
}

// PDF Preview Routes (server-side rendering via Nutrient Cloud API)
Route::get('/api/pdf/{pdfId}/page/{pageNumber}/render', [PdfPreviewController::class, 'renderPage'])
    ->name('pdf.render.page');
Route::get('/api/pdf/{pdfId}/page/{pageNumber}/render-base64', [PdfPreviewController::class, 'renderPageBase64'])
    ->name('pdf.render.page.base64');

// NOTE: PDF Annotation API routes have been moved to routes/api.php
// using the new App\Http\Controllers\Api\PdfAnnotationController