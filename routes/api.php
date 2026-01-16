<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PdfAnnotationController;
use App\Http\Controllers\Api\ClockController;
use App\Http\Controllers\Api\V1;

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

/*
|--------------------------------------------------------------------------
| V1 API Routes - External Integrations (n8n, mobile apps, etc.)
|--------------------------------------------------------------------------
|
| Token-based authentication via Laravel Sanctum.
| Rate limited to 60 requests per minute by default.
|
| Usage:
|   Authorization: Bearer {api_token}
|
| Features:
|   - Filtering: ?filter[field]=value
|   - Sorting: ?sort=-created_at,name (- prefix for descending)
|   - Pagination: ?page=1&per_page=50
|   - Relations: ?include=rooms,cabinets
|   - Search: ?search=kitchen
|
*/
Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:api'])->name('api.v1.')->group(function () {

    // ========================================
    // Projects Module
    // ========================================

    // Projects (top-level)
    Route::apiResource('projects', V1\ProjectController::class);

    // Nested: Projects → Rooms
    Route::apiResource('projects.rooms', V1\RoomController::class)->shallow();

    // Nested: Rooms → Room Locations
    Route::apiResource('rooms.locations', V1\RoomLocationController::class)->shallow();

    // Nested: Locations → Cabinet Runs
    Route::apiResource('locations.cabinet-runs', V1\CabinetRunController::class)->shallow();

    // Nested: Cabinet Runs → Cabinets
    Route::apiResource('cabinet-runs.cabinets', V1\CabinetController::class)->shallow();

    // Standalone listing routes for commonly queried resources
    Route::get('rooms', [V1\RoomController::class, 'index'])->name('rooms.index');
    Route::get('locations', [V1\RoomLocationController::class, 'index'])->name('locations.index');
    Route::get('cabinet-runs', [V1\CabinetRunController::class, 'index'])->name('cabinet-runs.index');
    Route::get('cabinets', [V1\CabinetController::class, 'index'])->name('cabinets.index');
    Route::get('sections', [V1\CabinetSectionController::class, 'index'])->name('sections.index');
    Route::get('drawers', [V1\DrawerController::class, 'index'])->name('drawers.index');
    Route::get('doors', [V1\DoorController::class, 'index'])->name('doors.index');
    Route::get('shelves', [V1\ShelfController::class, 'index'])->name('shelves.index');
    Route::get('pullouts', [V1\PulloutController::class, 'index'])->name('pullouts.index');
    Route::get('stretchers', [V1\StretcherController::class, 'index'])->name('stretchers.index');
    Route::get('faceframes', [V1\FaceframeController::class, 'index'])->name('faceframes.index');
    Route::get('moves', [V1\MoveController::class, 'index'])->name('moves.index');
    Route::get('locations', [V1\LocationController::class, 'index'])->name('inventory-locations.index');

    // Cabinet Components (nested under cabinets)
    Route::apiResource('cabinets.sections', V1\CabinetSectionController::class)->shallow();
    Route::apiResource('cabinets.stretchers', V1\StretcherController::class)->shallow();
    Route::apiResource('cabinets.faceframes', V1\FaceframeController::class)->shallow();

    // Section Components (nested under sections)
    Route::apiResource('sections.drawers', V1\DrawerController::class)->shallow();
    Route::apiResource('sections.doors', V1\DoorController::class)->shallow();
    Route::apiResource('sections.shelves', V1\ShelfController::class)->shallow();
    Route::apiResource('sections.pullouts', V1\PulloutController::class)->shallow();

    // Tasks & Milestones
    Route::apiResource('tasks', V1\TaskController::class);
    Route::apiResource('milestones', V1\MilestoneController::class);

    // ========================================
    // Employees Module
    // ========================================
    Route::apiResource('employees', V1\EmployeeController::class);
    Route::apiResource('departments', V1\DepartmentController::class);
    Route::apiResource('calendars', V1\CalendarController::class);

    // ========================================
    // Inventory Module
    // ========================================
    Route::apiResource('products', V1\ProductController::class);
    Route::apiResource('warehouses', V1\WarehouseController::class);
    Route::apiResource('warehouses.locations', V1\LocationController::class)->shallow();
    Route::apiResource('inventory-moves', V1\MoveController::class);

    // ========================================
    // Partners Module
    // ========================================
    Route::apiResource('partners', V1\PartnerController::class);

    // ========================================
    // Batch Operations
    // ========================================
    Route::post('batch/{resource}', [V1\BatchController::class, 'handle'])
        ->where('resource', '[a-z\-]+')
        ->name('batch');

    // ========================================
    // Webhooks
    // ========================================
    Route::prefix('webhooks')->name('webhooks.')->group(function () {
        Route::get('/', [V1\WebhookController::class, 'list'])->name('index');
        Route::post('subscribe', [V1\WebhookController::class, 'subscribe'])->name('subscribe');
        Route::get('{id}', [V1\WebhookController::class, 'show'])->name('show');
        Route::put('{id}', [V1\WebhookController::class, 'update'])->name('update');
        Route::delete('{id}', [V1\WebhookController::class, 'unsubscribe'])->name('unsubscribe');
        Route::get('events', [V1\WebhookController::class, 'events'])->name('events');
        Route::post('{id}/test', [V1\WebhookController::class, 'test'])->name('test');
        Route::get('{id}/deliveries', [V1\WebhookController::class, 'deliveries'])->name('deliveries');
    });
});

// PDF Page Annotation API Routes (Unified System)
// All routes work with pdf_page_annotations table
Route::middleware(['web', 'auth:web', 'throttle:120,1'])->prefix('pdf')->name('api.pdf.')->group(function () {
    // Page-level metadata operations
    Route::get('/page/{pdfPageId}/project-number', [PdfAnnotationController::class, 'getProjectNumber'])->name('page.project-number');
    Route::get('/annotations/page/{pdfPageId}/cabinet-runs', [PdfAnnotationController::class, 'getCabinetRuns'])->name('page.cabinet-runs');
    Route::get('/page/{pdfPageId}/context', [PdfAnnotationController::class, 'getAnnotationContext'])->name('page.context');
    Route::get('/page/{pdfPageId}/project-context', [PdfAnnotationController::class, 'getProjectContext'])->name('page.project-context');

    // Page-level annotation operations
    Route::post('/page/{pdfPageId}/annotations', [PdfAnnotationController::class, 'savePageAnnotations'])->middleware('throttle:60,1')->name('page.annotations.save');
    Route::get('/page/{pdfPageId}/annotations', [PdfAnnotationController::class, 'loadPageAnnotations'])->name('page.annotations.load');
    Route::delete('/page/annotations/{annotationId}', [PdfAnnotationController::class, 'deletePageAnnotation'])->middleware('throttle:30,1')->name('page.annotations.delete');
    Route::get('/page/{pdfPageId}/annotations/history', [PdfAnnotationController::class, 'getAnnotationHistory'])->name('page.annotations.history');

    // Page metadata operations (page type, cover fields, etc.)
    Route::get('/page/{pdfPageId}/metadata', [PdfAnnotationController::class, 'getPageMetadata'])->name('page.metadata.get');
    Route::post('/page/{pdfPageId}/metadata', [PdfAnnotationController::class, 'savePageMetadata'])->middleware('throttle:60,1')->name('page.metadata.save');

    // Page type operations (NEW - Phase 3.1)
    Route::post('/page/{pdfPageId}/page-type', [PdfAnnotationController::class, 'savePageType'])->middleware('throttle:60,1')->name('page.page-type.save');
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

        // Get annotation counts for all entities in this project
        // Room annotations: WHERE room_id = room.id AND deleted_at IS NULL (exclude soft-deleted)
        $roomAnnotationCounts = \DB::table('pdf_page_annotations')
            ->select('room_id', \DB::raw('COUNT(*) as count'))
            ->whereNotNull('room_id')
            ->whereNull('deleted_at')
            ->groupBy('room_id')
            ->pluck('count', 'room_id');

        // Room pages and view types
        $roomPages = \DB::table('pdf_page_annotations')
            ->join('pdf_pages', 'pdf_page_annotations.pdf_page_id', '=', 'pdf_pages.id')
            ->select('room_id', 'pdf_pages.page_number', 'pdf_page_annotations.view_type')
            ->whereNotNull('room_id')
            ->whereNull('pdf_page_annotations.deleted_at')
            ->get()
            ->groupBy('room_id')
            ->map(function ($annotations) {
                return $annotations->map(function ($anno) {
                    return [
                        'page' => $anno->page_number,
                        'viewType' => $anno->view_type
                    ];
                })->unique()->values();
            });

        // Cabinet run annotations: WHERE cabinet_run_id = run.id AND deleted_at IS NULL (exclude soft-deleted)
        $runAnnotationCounts = \DB::table('pdf_page_annotations')
            ->select('cabinet_run_id', \DB::raw('COUNT(*) as count'))
            ->whereNotNull('cabinet_run_id')
            ->whereNull('deleted_at')
            ->groupBy('cabinet_run_id')
            ->pluck('count', 'cabinet_run_id');

        // Cabinet run pages and view types
        $runPages = \DB::table('pdf_page_annotations')
            ->join('pdf_pages', 'pdf_page_annotations.pdf_page_id', '=', 'pdf_pages.id')
            ->select('cabinet_run_id', 'pdf_pages.page_number', 'pdf_page_annotations.view_type')
            ->whereNotNull('cabinet_run_id')
            ->whereNull('pdf_page_annotations.deleted_at')
            ->get()
            ->groupBy('cabinet_run_id')
            ->map(function ($annotations) {
                return $annotations->map(function ($anno) {
                    return [
                        'page' => $anno->page_number,
                        'viewType' => $anno->view_type
                    ];
                })->unique()->values();
            });

        // Location annotations: Count by room_location_id
        $locationAnnotationCounts = \DB::table('pdf_page_annotations')
            ->select('room_location_id', \DB::raw('COUNT(*) as count'))
            ->whereNotNull('room_location_id')
            ->whereNull('deleted_at')
            ->groupBy('room_location_id')
            ->pluck('count', 'room_location_id');

        // Location pages and view types
        $locationPages = \DB::table('pdf_page_annotations')
            ->join('pdf_pages', 'pdf_page_annotations.pdf_page_id', '=', 'pdf_pages.id')
            ->select('room_location_id', 'pdf_pages.page_number', 'pdf_page_annotations.view_type')
            ->whereNotNull('room_location_id')
            ->whereNull('pdf_page_annotations.deleted_at')
            ->get()
            ->groupBy('room_location_id')
            ->map(function ($annotations) {
                return $annotations->map(function ($anno) {
                    return [
                        'page' => $anno->page_number,
                        'viewType' => $anno->view_type
                    ];
                })->unique()->values();
            });

        // Cabinet annotations: Get cabinet details grouped by cabinet_run_id
        $cabinetsByCabinetRun = \DB::table('pdf_page_annotations')
            ->join('pdf_pages', 'pdf_page_annotations.pdf_page_id', '=', 'pdf_pages.id')
            ->select(
                'pdf_page_annotations.cabinet_run_id',
                'pdf_page_annotations.id',
                'pdf_page_annotations.label',
                'pdf_pages.page_number',
                'pdf_page_annotations.view_type'
            )
            ->where('pdf_page_annotations.annotation_type', 'cabinet')
            ->whereNotNull('pdf_page_annotations.cabinet_run_id')
            ->whereNull('pdf_page_annotations.deleted_at')
            ->get()
            ->groupBy('cabinet_run_id')
            ->map(function ($cabinets) {
                return $cabinets->map(function ($cabinet) {
                    return [
                        'id' => $cabinet->id,
                        'name' => $cabinet->label,
                        'type' => 'cabinet',
                        'pages' => [[
                            'page' => $cabinet->page_number,
                            'viewType' => $cabinet->view_type
                        ]]
                    ];
                })->values();
            });

        // Transform to tree structure expected by V3 component
        $tree = $project->rooms->map(function ($room) use ($roomAnnotationCounts, $roomPages, $locationAnnotationCounts, $locationPages, $runAnnotationCounts, $runPages, $cabinetsByCabinetRun) {
            return [
                'id' => $room->id,
                'name' => $room->name,
                'type' => 'room',
                'annotation_count' => $roomAnnotationCounts->get($room->id, 0),
                'pages' => $roomPages->get($room->id, collect())->toArray(),
                'children' => $room->locations->map(function ($location) use ($locationAnnotationCounts, $locationPages, $runAnnotationCounts, $runPages, $cabinetsByCabinetRun) {
                    return [
                        'id' => $location->id,
                        'name' => $location->name,
                        'type' => 'room_location',
                        'annotation_count' => $locationAnnotationCounts->get($location->id, 0),
                        'pages' => $locationPages->get($location->id, collect())->toArray(),
                        'children' => $location->cabinetRuns->map(function ($run) use ($runAnnotationCounts, $runPages, $cabinetsByCabinetRun) {
                            return [
                                'id' => $run->id,
                                'name' => $run->name ?: "Run {$run->id}",
                                'type' => 'cabinet_run',
                                'annotation_count' => $runAnnotationCounts->get($run->id, 0),
                                'pages' => $runPages->get($run->id, collect())->toArray(),
                                'children' => $cabinetsByCabinetRun->get($run->id, collect())->toArray(),
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

    // Delete entity routes
    Route::delete('/room/{roomId}', [App\Http\Controllers\Api\ProjectEntityTreeController::class, 'deleteRoom'])
        ->middleware('throttle:30,1')
        ->name('room.delete');

    Route::delete('/location/{locationId}', [App\Http\Controllers\Api\ProjectEntityTreeController::class, 'deleteLocation'])
        ->middleware('throttle:30,1')
        ->name('location.delete');

    Route::delete('/cabinet-run/{cabinetRunId}', [App\Http\Controllers\Api\ProjectEntityTreeController::class, 'deleteCabinetRun'])
        ->middleware('throttle:30,1')
        ->name('cabinet-run.delete');
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

// Cabinet AI Assistant API Routes
Route::middleware(['web', 'auth:web'])->prefix('cabinet-ai')->name('api.cabinet-ai.')->group(function () {
    // Send message to AI assistant
    Route::post('/message', [App\Http\Controllers\Api\CabinetAiController::class, 'sendMessage'])
        ->middleware('throttle:30,1')
        ->name('message');

    // Analyze floor plan or cabinet image
    Route::post('/image', [App\Http\Controllers\Api\CabinetAiController::class, 'analyzeImage'])
        ->middleware('throttle:10,1')
        ->name('image');

    // Get conversation history
    Route::get('/history/{sessionId}', [App\Http\Controllers\Api\CabinetAiController::class, 'getHistory'])
        ->name('history');

    // Clear conversation history
    Route::delete('/history/{sessionId}', [App\Http\Controllers\Api\CabinetAiController::class, 'clearHistory'])
        ->name('history.clear');

    // Execute AI commands directly
    Route::post('/execute', [App\Http\Controllers\Api\CabinetAiController::class, 'executeCommands'])
        ->middleware('throttle:30,1')
        ->name('execute');

    // Get current spec data for a project
    Route::get('/spec/{projectId}', [App\Http\Controllers\Api\CabinetAiController::class, 'getSpecData'])
        ->name('spec');
});

// DWG/DXF Parser API Routes - CAD File Parsing
Route::middleware(['web', 'auth:web'])->prefix('dwg')->name('api.dwg.')->group(function () {
    // Parse uploaded DWG/DXF file
    Route::post('/parse', [App\Http\Controllers\Api\DwgController::class, 'parse'])
        ->middleware('throttle:30,1')
        ->name('parse');

    // Parse file from storage path
    Route::post('/parse-path', [App\Http\Controllers\Api\DwgController::class, 'parseFromPath'])
        ->middleware('throttle:30,1')
        ->name('parse-path');

    // Convert to SVG
    Route::post('/to-svg', [App\Http\Controllers\Api\DwgController::class, 'toSvg'])
        ->middleware('throttle:30,1')
        ->name('to-svg');

    // Get layer statistics
    Route::post('/layer-stats', [App\Http\Controllers\Api\DwgController::class, 'layerStats'])
        ->middleware('throttle:30,1')
        ->name('layer-stats');

    // Check parsing capabilities
    Route::get('/capabilities', [App\Http\Controllers\Api\DwgController::class, 'capabilities'])
        ->name('capabilities');
});

// Document Scanner API Routes - AI-powered document scanning
Route::middleware(['web', 'auth:web'])->prefix('document-scanner')->name('api.document-scanner.')->group(function () {
    // Scan receiving document (packing slip)
    Route::post('/scan-receiving', [App\Http\Controllers\Api\DocumentScannerApiController::class, 'scanReceiving'])
        ->middleware('throttle:10,1')
        ->name('scan-receiving');

    // Learn vendor SKU mappings from user confirmations
    Route::post('/learn-mappings', [App\Http\Controllers\Api\DocumentScannerApiController::class, 'learnMappings'])
        ->middleware('throttle:30,1')
        ->name('learn-mappings');

    // Create new product from scan data
    Route::post('/create-product', [App\Http\Controllers\Api\DocumentScannerApiController::class, 'createProduct'])
        ->middleware('throttle:30,1')
        ->name('create-product');
});

// Products API Routes - Product listing for scanner
Route::middleware(['web', 'auth:web'])->prefix('products')->name('api.products.')->group(function () {
    // List products for dropdown
    Route::get('/list', [App\Http\Controllers\Api\DocumentScannerApiController::class, 'listProducts'])
        ->name('list');
});

// Clock API Routes - Time Clock System for TCS Employees
Route::middleware(['web', 'auth:web'])->prefix('clock')->name('api.clock.')->group(function () {
    // Authenticated user clock operations
    Route::get('/status', [ClockController::class, 'status'])->name('status');
    Route::post('/in', [ClockController::class, 'clockIn'])->name('in');
    Route::post('/out', [ClockController::class, 'clockOut'])->name('out');
    Route::post('/manual', [ClockController::class, 'addManualEntry'])->name('manual');
    Route::get('/weekly', [ClockController::class, 'weeklySummary'])->name('weekly');

    // Owner/Manager operations
    Route::get('/attendance', [ClockController::class, 'todayAttendance'])->name('attendance');
    Route::get('/status/{userId}', [ClockController::class, 'getUserStatus'])->name('user.status');
    Route::post('/approve/{entryId}', [ClockController::class, 'approveEntry'])->name('approve');
    Route::post('/assign-project/{entryId}', [ClockController::class, 'assignToProject'])->name('assign-project');

    // Kiosk mode (shop floor tablet)
    Route::post('/kiosk/in', [ClockController::class, 'kioskClockIn'])->name('kiosk.in');
    Route::post('/kiosk/out', [ClockController::class, 'kioskClockOut'])->name('kiosk.out');

    // Export operations
    Route::get('/export/weekly', [ClockController::class, 'exportWeekly'])->name('export.weekly');
    Route::get('/export/weekly/{userId}', [ClockController::class, 'exportUserWeekly'])->name('export.weekly.user');
    Route::get('/export/team', [ClockController::class, 'exportTeamSummary'])->name('export.team');
    Route::get('/export/payroll', [ClockController::class, 'exportPayroll'])->name('export.payroll');
});
