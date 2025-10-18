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
    Route::get('/page/{pdfPageId}/project-context', [PdfAnnotationController::class, 'getProjectContext'])->name('page.project-context');

    // Page-level annotation operations
    Route::post('/page/{pdfPageId}/annotations', [PdfAnnotationController::class, 'savePageAnnotations'])->middleware('throttle:60,1')->name('page.annotations.save');
    Route::get('/page/{pdfPageId}/annotations', [PdfAnnotationController::class, 'loadPageAnnotations'])->name('page.annotations.load');
    Route::get('/page/{pdfPageId}/annotations/history', [PdfAnnotationController::class, 'getAnnotationHistory'])->name('page.annotations.history');

    // Page metadata operations (page type, cover fields, etc.)
    Route::get('/page/{pdfPageId}/metadata', [PdfAnnotationController::class, 'getPageMetadata'])->name('page.metadata.get');
    Route::post('/page/{pdfPageId}/metadata', [PdfAnnotationController::class, 'savePageMetadata'])->middleware('throttle:60,1')->name('page.metadata.save');
});

// Project API Routes
Route::middleware(['web', 'auth:web'])->prefix('projects')->group(function () {
    // Get project list with health metrics for project selector
    Route::get('/list', [App\Http\Controllers\Api\FooterApiController::class, 'getProjectList'])
        ->name('api.projects.list');

    // Get project details for form auto-population
    Route::get('/{projectId}', [App\Http\Controllers\Api\FooterApiController::class, 'getProject'])
        ->name('api.projects.show');

    // Get project tree (Room → Location → Cabinet Run hierarchy for V3 annotation system)
    Route::get('/{projectId}/tree', function ($projectId) {
        $project = \Webkul\Project\Models\Project::with([
            'rooms' => function ($query) {
                $query->orderBy('sort_order')->orderBy('name');
            },
            'rooms.locations' => function ($query) {
                $query->orderBy('sort_order')->orderBy('name');
            },
            'rooms.locations.cabinetRuns' => function ($query) {
                $query->orderBy('sort_order')->orderBy('name');
            }
        ])->findOrFail($projectId);

        // Transform to tree structure expected by V3 component
        $tree = $project->rooms->map(function ($room) {
            return [
                'id' => $room->id,
                'name' => $room->name,
                'type' => 'room',
                'annotation_count' => 0, // TODO: Count annotations for this room
                'children' => $room->locations->map(function ($location) {
                    return [
                        'id' => $location->id,
                        'name' => $location->name,
                        'type' => 'room_location',
                        'annotation_count' => 0, // TODO: Count annotations for this location
                        'children' => $location->cabinetRuns->map(function ($run) {
                            return [
                                'id' => $run->id,
                                'name' => $run->name ?: "Run {$run->id}",
                                'type' => 'cabinet_run',
                                'annotation_count' => 0, // TODO: Count annotations for this run
                            ];
                        })->values()
                    ];
                })->values()
            ];
        })->values();

        return response()->json($tree);
    })->name('api.projects.tree');

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

    // Get project tags for global footer
    Route::get('/{projectId}/tags', [App\Http\Controllers\Api\FooterApiController::class, 'getProjectTags'])
        ->name('api.projects.tags.list');
});

// Project Entity Tree API Routes (for annotation system)
Route::middleware(['web', 'auth:web'])->prefix('project')->name('api.project.')->group(function () {
    // Get hierarchical entity tree with annotation counts
    Route::get('/{projectId}/entity-tree', [App\Http\Controllers\Api\ProjectEntityTreeController::class, 'getEntityTree'])
        ->name('entity-tree');

    // Get rooms for autocomplete
    Route::get('/{projectId}/rooms', [App\Http\Controllers\Api\ProjectEntityTreeController::class, 'getRooms'])
        ->name('rooms');

    // Create new room
    Route::post('/{projectId}/rooms', [App\Http\Controllers\Api\ProjectEntityTreeController::class, 'createRoom'])
        ->middleware('throttle:60,1')
        ->name('rooms.create');

    // Get locations for a room
    Route::get('/room/{roomId}/locations', [App\Http\Controllers\Api\ProjectEntityTreeController::class, 'getLocationsForRoom'])
        ->name('room.locations');

    // Create new location for a room
    Route::post('/room/{roomId}/locations', [App\Http\Controllers\Api\ProjectEntityTreeController::class, 'createLocationForRoom'])
        ->middleware('throttle:60,1')
        ->name('room.locations.create');
});

// Global Footer API Routes
Route::middleware(['web', 'auth:web'])->prefix('admin/api')->group(function () {
    Route::get('/partners/{partnerId}', [App\Http\Controllers\Api\FooterApiController::class, 'getPartner'])
        ->name('api.partners.show');

    Route::get('/production-estimate', [App\Http\Controllers\Api\FooterApiController::class, 'getProductionEstimate'])
        ->name('api.production-estimate');
});

// Footer Customizer API Routes
Route::middleware(['web', 'auth:web'])->prefix('footer')->name('api.footer.')->group(function () {
    // Get all user preferences for all contexts
    Route::get('/preferences', [App\Http\Controllers\Api\FooterApiController::class, 'getFooterPreferences'])
        ->name('preferences');

    // Save preferences for a specific context
    Route::post('/preferences', [App\Http\Controllers\Api\FooterApiController::class, 'saveFooterPreferences'])
        ->name('preferences.save');

    // Get available fields for a context type
    Route::get('/fields/{contextType}', [App\Http\Controllers\Api\FooterApiController::class, 'getAvailableFields'])
        ->name('fields')
        ->where('contextType', 'project|sale|inventory|production');

    // Apply persona template
    Route::post('/persona/{persona}', [App\Http\Controllers\Api\FooterApiController::class, 'applyPersonaTemplate'])
        ->name('persona.apply')
        ->where('persona', 'owner|project_manager|sales|inventory');

    // Reset to defaults for a specific context
    Route::post('/reset/{contextType}', [App\Http\Controllers\Api\FooterApiController::class, 'resetToDefaults'])
        ->name('reset')
        ->where('contextType', 'project|sale|inventory|production');
});
