<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\Room;
use Webkul\Project\Models\RoomLocation;
use Webkul\Project\Models\CabinetRun;
use App\Models\PdfPageAnnotation;

/**
 * Project Entity Tree Controller controller
 *
 */
class ProjectEntityTreeController extends Controller
{
    /**
     * Get hierarchical entity tree for project with annotation counts
     *
     * Returns: Room → Location → Run → Cabinet hierarchy with:
     * - Annotation counts at each level
     * - PDF page references where entities are annotated
     * - Entity metadata (names, IDs, types)
     */
    public function getEntityTree(Request $request, int $projectId): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        // Load rooms with their locations, runs, and cabinets
        $rooms = Room::where('project_id', $projectId)
            ->with([
                'locations.cabinetRuns.cabinets'
            ])
            ->orderBy('name')
            ->get();

        $tree = $rooms->map(function ($room) {
            // Count annotations for this room
            $roomAnnotationCount = PdfPageAnnotation::where('room_id', $room->id)
                ->where('annotation_type', 'room')
                ->count();

            // Get pages where room is annotated
            $roomPages = PdfPageAnnotation::where('room_id', $room->id)
                ->where('annotation_type', 'room')
                ->pluck('pdf_page_id')
                ->unique()
                ->toArray();

            return [
                'id' => $room->id,
                'type' => 'room',
                'name' => $room->name,
                'display_name' => $room->name,
                'room_type' => $room->room_type,
                'annotation_count' => $roomAnnotationCount,
                'pages' => $roomPages,
                'children' => $room->locations->map(function ($location) use ($room) {
                    // Count annotations for this location
                    $locationAnnotationCount = PdfPageAnnotation::where('room_location_id', $location->id)
                        ->where('annotation_type', 'location')
                        ->count();

                    // Get pages where location is annotated
                    $locationPages = PdfPageAnnotation::where('room_location_id', $location->id)
                        ->where('annotation_type', 'location')
                        ->pluck('pdf_page_id')
                        ->unique()
                        ->toArray();

                    return [
                        'id' => $location->id,
                        'type' => 'room_location',
                        'name' => $location->name,
                        'display_name' => $location->name,
                        'room_id' => $room->id,
                        'annotation_count' => $locationAnnotationCount,
                        'pages' => $locationPages,
                        'children' => $location->cabinetRuns->map(function ($run) use ($location) {
                            // Count annotations for this run
                            $runAnnotationCount = PdfPageAnnotation::where('cabinet_run_id', $run->id)
                                ->where('annotation_type', 'cabinet_run')
                                ->count();

                            // Get pages where run is annotated
                            $runPages = PdfPageAnnotation::where('cabinet_run_id', $run->id)
                                ->where('annotation_type', 'cabinet_run')
                                ->pluck('pdf_page_id')
                                ->unique()
                                ->toArray();

                            return [
                                'id' => $run->id,
                                'type' => 'cabinet_run',
                                'name' => $run->name,
                                'display_name' => $run->name,
                                'run_type' => $run->run_type,
                                'room_location_id' => $location->id,
                                'annotation_count' => $runAnnotationCount,
                                'pages' => $runPages,
                                'children' => $run->cabinets->map(function ($cabinet) use ($run) {
                                    // Count annotations for this cabinet
                                    $cabinetAnnotationCount = PdfPageAnnotation::where('cabinet_specification_id', $cabinet->id)
                                        ->where('annotation_type', 'cabinet')
                                        ->count();

                                    // Get pages where cabinet is annotated
                                    $cabinetPages = PdfPageAnnotation::where('cabinet_specification_id', $cabinet->id)
                                        ->where('annotation_type', 'cabinet')
                                        ->pluck('pdf_page_id')
                                        ->unique()
                                        ->toArray();

                                    return [
                                        'id' => $cabinet->id,
                                        'type' => 'cabinet',
                                        'name' => $cabinet->cabinet_number ?? "Cabinet {$cabinet->id}",
                                        'display_name' => $cabinet->cabinet_number ?? "Cabinet {$cabinet->id}",
                                        'cabinet_run_id' => $run->id,
                                        'annotation_count' => $cabinetAnnotationCount,
                                        'pages' => $cabinetPages,
                                    ];
                                })->toArray()
                            ];
                        })->toArray()
                    ];
                })->toArray()
            ];
        })->toArray();

        return response()->json([
            'success' => true,
            'project_id' => $projectId,
            'project_name' => $project->name,
            'tree' => $tree,
            'total_rooms' => count($tree),
            'timestamp' => now()->toIso8601String()
        ]);
    }

    /**
     * Get all rooms for project (for autocomplete)
     */
    public function getRooms(Request $request, int $projectId): JsonResponse
    {
        $rooms = Room::where('project_id', $projectId)
            ->select('id', 'name', 'room_type')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'rooms' => $rooms
        ]);
    }

    /**
     * Get all locations for a room (for autocomplete)
     */
    public function getLocationsForRoom(Request $request, int $roomId): JsonResponse
    {
        $locations = RoomLocation::where('room_id', $roomId)
            ->select('id', 'name', 'location_type', 'room_id')
            ->orderBy('sequence')
            ->get();

        return response()->json([
            'success' => true,
            'locations' => $locations
        ]);
    }

    /**
     * Create a new room
     */
    public function createRoom(Request $request, int $projectId): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'room_type' => 'nullable|string|max:255',
        ]);

        $room = Room::create([
            'project_id' => $projectId,
            'name' => $validated['name'],
            'room_type' => $validated['room_type'] ?? null,
            'creator_id' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'room' => $room
        ], 201);
    }

    /**
     * Create a new location for a room
     */
    public function createLocationForRoom(Request $request, int $roomId): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location_type' => 'nullable|string|max:255',
        ]);

        $location = RoomLocation::create([
            'room_id' => $roomId,
            'name' => $validated['name'],
            'location_type' => $validated['location_type'] ?? 'wall',
            'creator_id' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'location' => $location
        ], 201);
    }

    /**
     * Delete a room and all associated data
     */
    public function deleteRoom(int $roomId): JsonResponse
    {
        try {
            $room = Room::findOrFail($roomId);

            // Get all descendant entity IDs before deletion
            $locationIds = RoomLocation::where('room_id', $roomId)->pluck('id');
            $cabinetRunIds = CabinetRun::whereIn('room_location_id', $locationIds)->pluck('id');
            $cabinetIds = \Webkul\Project\Models\CabinetSpecification::whereIn('cabinet_run_id', $cabinetRunIds)->pluck('id');

            // Delete ALL annotations in the hierarchy
            // This is necessary because annotations use onDelete('set null') not cascade
            PdfPageAnnotation::where(function($query) use ($roomId, $locationIds, $cabinetRunIds, $cabinetIds) {
                $query->where('room_id', $roomId)
                    ->orWhereIn('room_location_id', $locationIds)
                    ->orWhereIn('cabinet_run_id', $cabinetRunIds)
                    ->orWhereIn('cabinet_specification_id', $cabinetIds);
            })->delete();

            // Delete room (cascade will handle locations, cabinet runs, etc.)
            $room->delete();

            return response()->json([
                'success' => true,
                'message' => 'Room deleted successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Room not found'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Failed to delete room', [
                'room_id' => $roomId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to delete room: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a location and all associated data
     */
    public function deleteLocation(int $locationId): JsonResponse
    {
        try {
            $location = RoomLocation::findOrFail($locationId);

            // Get all descendant entity IDs before deletion
            $cabinetRunIds = CabinetRun::where('room_location_id', $locationId)->pluck('id');
            $cabinetIds = \Webkul\Project\Models\CabinetSpecification::whereIn('cabinet_run_id', $cabinetRunIds)->pluck('id');

            // Delete ALL annotations in the hierarchy
            // This is necessary because annotations use onDelete('set null') not cascade
            PdfPageAnnotation::where(function($query) use ($locationId, $cabinetRunIds, $cabinetIds) {
                $query->where('room_location_id', $locationId)
                    ->orWhereIn('cabinet_run_id', $cabinetRunIds)
                    ->orWhereIn('cabinet_specification_id', $cabinetIds);
            })->delete();

            // Delete location (cascade will handle cabinet runs, etc.)
            $location->delete();

            return response()->json([
                'success' => true,
                'message' => 'Location deleted successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Location not found'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Failed to delete location', [
                'location_id' => $locationId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to delete location: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a cabinet run and all associated data
     */
    public function deleteCabinetRun(int $cabinetRunId): JsonResponse
    {
        try {
            $cabinetRun = CabinetRun::findOrFail($cabinetRunId);

            // Get all descendant entity IDs before deletion
            $cabinetIds = \Webkul\Project\Models\CabinetSpecification::where('cabinet_run_id', $cabinetRunId)->pluck('id');

            // Delete ALL annotations in the hierarchy
            // This is necessary because annotations use onDelete('set null') not cascade
            PdfPageAnnotation::where(function($query) use ($cabinetRunId, $cabinetIds) {
                $query->where('cabinet_run_id', $cabinetRunId)
                    ->orWhereIn('cabinet_specification_id', $cabinetIds);
            })->delete();

            // Delete cabinet run (cascade will handle cabinets, etc.)
            $cabinetRun->delete();

            return response()->json([
                'success' => true,
                'message' => 'Cabinet run deleted successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Cabinet run not found'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Failed to delete cabinet run', [
                'cabinet_run_id' => $cabinetRunId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to delete cabinet run: ' . $e->getMessage()
            ], 500);
        }
    }
}
