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
Route::get('/test-auth-debug', function () {
    return response()->json([
        'authenticated' => auth()->check(),
        'user_id' => auth()->id(),
        'user_email' => auth()->user()?->email,
        'browser_testing_env' => env('BROWSER_TESTING'),
        'session_id' => session()->getId(),
    ]);
})->middleware('web');

// Employee HR Form Routes
// Blank intake form (no employee data) - public for easy access
Route::get('/hr/intake-form/blank', function () {
    try {
        $html = \Webkul\Employee\Filament\Exports\EmployeeIntakeFormExporter::generateBlankFormHtml();
        return response($html)->header('Content-Type', 'text/html');
    } catch (\Exception $e) {
        abort(404, 'Template not found: ' . $e->getMessage());
    }
})->name('employee.intake-form.blank');

// Employee intake form - pre-filled with employee data (requires auth)
Route::middleware(['auth', 'web'])->group(function () {
    Route::get('/hr/employee/{employee}/intake-form', function ($employeeId) {
        try {
            $html = \Webkul\Employee\Filament\Exports\EmployeeIntakeFormExporter::generateIntakeFormHtml($employeeId);
            return response($html)->header('Content-Type', 'text/html');
        } catch (\Exception $e) {
            abort(404, 'Employee not found or template error: ' . $e->getMessage());
        }
    })->name('employee.intake-form');
});
