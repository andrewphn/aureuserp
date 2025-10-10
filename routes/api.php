<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PdfAnnotationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// PDF Annotation API Routes
Route::middleware(['web', 'auth:web', 'throttle:120,1'])->prefix('pdf')->name('api.pdf.')->group(function () {
    // Document-level annotation operations
    Route::get('/{documentId}/annotations', [PdfAnnotationController::class, 'index'])->name('annotations.index');
    Route::post('/{documentId}/annotations', [PdfAnnotationController::class, 'store'])->middleware('throttle:60,1')->name('annotations.store');
    Route::get('/{documentId}/annotations/count', [PdfAnnotationController::class, 'count'])->name('annotations.count');

    // Individual annotation operations
    Route::get('/annotations/{annotationId}', [PdfAnnotationController::class, 'show'])->name('annotations.show');
    Route::put('/annotations/{annotationId}', [PdfAnnotationController::class, 'update'])->middleware('throttle:60,1')->name('annotations.update');
    Route::delete('/annotations/{annotationId}', [PdfAnnotationController::class, 'destroy'])->middleware('throttle:30,1')->name('annotations.destroy');

    // Page-level metadata operations
    Route::get('/page/{pdfPageId}/project-number', [PdfAnnotationController::class, 'getProjectNumber'])->name('page.project-number');
    Route::get('/annotations/page/{pdfPageId}/cabinet-runs', [PdfAnnotationController::class, 'getCabinetRuns'])->name('page.cabinet-runs');
    Route::get('/page/{pdfPageId}/context', [PdfAnnotationController::class, 'getAnnotationContext'])->name('page.context');

    // Page-level annotation operations
    Route::post('/page/{pdfPageId}/annotations', [PdfAnnotationController::class, 'savePageAnnotations'])->middleware('throttle:60,1')->name('page.annotations.save');
    Route::get('/page/{pdfPageId}/annotations', [PdfAnnotationController::class, 'getPageAnnotations'])->name('page.annotations.load');
    Route::get('/page/{pdfPageId}/annotations/history', [PdfAnnotationController::class, 'getAnnotationHistory'])->name('page.annotations.history');
});

// Project Tags API Routes
Route::middleware(['web', 'auth:web'])->prefix('projects')->group(function () {
    Route::post('/{projectId}/tags', function (Request $request, $projectId) {
        $project = \Webkul\Project\Models\Project::findOrFail($projectId);
        $tagIds = $request->input('tag_ids', []);

        // Sync tags to the project
        $project->tags()->sync($tagIds);

        return response()->json([
            'success' => true,
            'message' => 'Tags updated successfully',
            'tag_count' => count($tagIds)
        ]);
    })->name('api.projects.tags.update');
});
