<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GeminiCabinetAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Webkul\Project\Models\Project;

class CabinetAiController extends Controller
{
    protected GeminiCabinetAssistantService $aiService;

    public function __construct(GeminiCabinetAssistantService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Send a message to the Cabinet AI Assistant
     *
     * POST /api/cabinet-ai/message
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:2000',
            'project_id' => 'required|integer|exists:projects_projects,id',
            'session_id' => 'nullable|string|max:100',
            'mode' => 'nullable|in:quick,guided',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $project = Project::with(['rooms.locations.cabinetRuns.cabinets'])->find($request->project_id);

            if (!$project) {
                return response()->json([
                    'success' => false,
                    'error' => 'Project not found',
                ], 404);
            }

            // Build spec data from project
            $specData = $this->buildSpecDataFromProject($project);

            // Generate session ID if not provided
            $sessionId = $request->session_id ?? 'api_' . $project->id . '_' . uniqid();
            $mode = $request->mode ?? 'quick';

            // Process message through AI service
            $result = $this->aiService->processMessage(
                $request->message,
                $sessionId,
                $specData,
                $mode
            );

            return response()->json([
                'success' => true,
                'session_id' => $sessionId,
                'response' => $result['response'] ?? '',
                'commands' => $result['commands'] ?? [],
                'is_error' => $result['isError'] ?? false,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Analyze a floor plan or cabinet image
     *
     * POST /api/cabinet-ai/image
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function analyzeImage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|file|mimes:jpeg,jpg,png,webp|max:10240', // 10MB max
            'project_id' => 'required|integer|exists:projects_projects,id',
            'session_id' => 'nullable|string|max:100',
            'prompt' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $project = Project::with(['rooms.locations.cabinetRuns.cabinets'])->find($request->project_id);

            if (!$project) {
                return response()->json([
                    'success' => false,
                    'error' => 'Project not found',
                ], 404);
            }

            // Build spec data from project
            $specData = $this->buildSpecDataFromProject($project);

            // Generate session ID if not provided
            $sessionId = $request->session_id ?? 'api_' . $project->id . '_' . uniqid();

            // Get image data
            $image = $request->file('image');
            $imageBase64 = base64_encode(file_get_contents($image->getRealPath()));
            $mimeType = $image->getMimeType();

            // Optional prompt for context
            $prompt = $request->prompt ?? 'Analyze this floor plan or cabinet image and suggest a cabinet specification.';

            // Process image through AI service
            $result = $this->aiService->processImage(
                $imageBase64,
                $mimeType,
                $sessionId,
                $specData,
                $prompt
            );

            return response()->json([
                'success' => true,
                'session_id' => $sessionId,
                'response' => $result['response'] ?? '',
                'commands' => $result['commands'] ?? [],
                'is_error' => $result['isError'] ?? false,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get conversation history for a session
     *
     * GET /api/cabinet-ai/history/{sessionId}
     *
     * @param string $sessionId
     * @return JsonResponse
     */
    public function getHistory(string $sessionId): JsonResponse
    {
        try {
            $history = $this->aiService->getConversationHistory($sessionId);

            return response()->json([
                'success' => true,
                'session_id' => $sessionId,
                'messages' => $history,
                'count' => count($history),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear conversation history for a session
     *
     * DELETE /api/cabinet-ai/history/{sessionId}
     *
     * @param string $sessionId
     * @return JsonResponse
     */
    public function clearHistory(string $sessionId): JsonResponse
    {
        try {
            $this->aiService->clearConversationHistory($sessionId);

            return response()->json([
                'success' => true,
                'message' => 'Conversation history cleared',
                'session_id' => $sessionId,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Execute AI commands on a project
     *
     * POST /api/cabinet-ai/execute
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function executeCommands(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'project_id' => 'required|integer|exists:projects_projects,id',
            'commands' => 'required|array|min:1',
            'commands.*.action' => 'required|string|in:add_room,add_location,add_cabinet_run,add_cabinets,delete_entity,update_pricing',
            'commands.*.params' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $project = Project::find($request->project_id);

            if (!$project) {
                return response()->json([
                    'success' => false,
                    'error' => 'Project not found',
                ], 404);
            }

            $results = [];
            $errors = [];

            foreach ($request->commands as $index => $command) {
                try {
                    $result = $this->executeCommand($project, $command['action'], $command['params']);
                    $results[] = [
                        'index' => $index,
                        'action' => $command['action'],
                        'success' => true,
                        'result' => $result,
                    ];
                } catch (\Exception $e) {
                    $errors[] = [
                        'index' => $index,
                        'action' => $command['action'],
                        'error' => $e->getMessage(),
                    ];
                }
            }

            return response()->json([
                'success' => count($errors) === 0,
                'executed' => count($results),
                'failed' => count($errors),
                'results' => $results,
                'errors' => $errors,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get current spec data for a project
     *
     * GET /api/cabinet-ai/spec/{projectId}
     *
     * @param int $projectId
     * @return JsonResponse
     */
    public function getSpecData(int $projectId): JsonResponse
    {
        try {
            $project = Project::with(['rooms.locations.cabinetRuns.cabinets'])->find($projectId);

            if (!$project) {
                return response()->json([
                    'success' => false,
                    'error' => 'Project not found',
                ], 404);
            }

            $specData = $this->buildSpecDataFromProject($project);

            // Calculate totals
            $totalLinearFeet = 0;
            $totalEstimate = 0;

            foreach ($specData as $room) {
                foreach ($room['locations'] ?? [] as $location) {
                    foreach ($location['runs'] ?? [] as $run) {
                        $totalLinearFeet += $run['total_linear_feet'] ?? 0;
                        $totalEstimate += $run['estimated_price'] ?? 0;
                    }
                }
            }

            return response()->json([
                'success' => true,
                'project_id' => $projectId,
                'project_name' => $project->name,
                'rooms' => $specData,
                'summary' => [
                    'total_rooms' => count($specData),
                    'total_linear_feet' => round($totalLinearFeet, 2),
                    'estimated_price' => round($totalEstimate, 2),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Build spec data array from project model
     *
     * @param Project $project
     * @return array
     */
    protected function buildSpecDataFromProject(Project $project): array
    {
        $specData = [];

        foreach ($project->rooms as $room) {
            $roomData = [
                'id' => $room->id,
                'name' => $room->name,
                'room_type' => $room->room_type ?? 'general',
                'description' => $room->description,
                'locations' => [],
            ];

            foreach ($room->locations as $location) {
                $locationData = [
                    'id' => $location->id,
                    'name' => $location->name,
                    'location_type' => $location->location_type ?? 'wall',
                    'cabinet_level' => $location->cabinet_level ?? 2,
                    'runs' => [],
                ];

                foreach ($location->cabinetRuns as $run) {
                    $runData = [
                        'id' => $run->id,
                        'name' => $run->name,
                        'run_type' => $run->run_type ?? 'base',
                        'total_linear_feet' => $run->total_linear_feet ?? 0,
                        'estimated_price' => $run->estimated_price ?? 0,
                        'cabinets' => [],
                    ];

                    foreach ($run->cabinets as $cabinet) {
                        $runData['cabinets'][] = [
                            'id' => $cabinet->id,
                            'name' => $cabinet->name,
                            'cabinet_type' => $cabinet->cabinet_type ?? 'base',
                            'width_inches' => $cabinet->width_inches ?? 0,
                            'quantity' => $cabinet->quantity ?? 1,
                        ];
                    }

                    $locationData['runs'][] = $runData;
                }

                $roomData['locations'][] = $locationData;
            }

            $specData[] = $roomData;
        }

        return $specData;
    }

    /**
     * Execute a single command on a project
     *
     * @param Project $project
     * @param string $action
     * @param array $params
     * @return array
     */
    protected function executeCommand(Project $project, string $action, array $params): array
    {
        switch ($action) {
            case 'add_room':
                return $this->executeAddRoom($project, $params);

            case 'add_location':
                return $this->executeAddLocation($project, $params);

            case 'add_cabinet_run':
                return $this->executeAddCabinetRun($project, $params);

            case 'add_cabinets':
                return $this->executeAddCabinets($project, $params);

            case 'delete_entity':
                return $this->executeDeleteEntity($project, $params);

            case 'update_pricing':
                return $this->executeUpdatePricing($project, $params);

            default:
                throw new \InvalidArgumentException("Unknown action: {$action}");
        }
    }

    /**
     * Execute add_room command
     */
    protected function executeAddRoom(Project $project, array $params): array
    {
        $room = $project->rooms()->create([
            'name' => $params['name'] ?? 'New Room',
            'room_type' => $params['room_type'] ?? 'general',
            'description' => $params['description'] ?? null,
            'floor_number' => $params['floor_number'] ?? 1,
        ]);

        return [
            'type' => 'room',
            'id' => $room->id,
            'name' => $room->name,
        ];
    }

    /**
     * Execute add_location command
     */
    protected function executeAddLocation(Project $project, array $params): array
    {
        $roomId = $params['room_id'] ?? null;
        $roomName = $params['room_name'] ?? null;

        $room = null;
        if ($roomId) {
            $room = $project->rooms()->find($roomId);
        } elseif ($roomName) {
            $room = $project->rooms()->where('name', 'LIKE', "%{$roomName}%")->first();
        }

        if (!$room) {
            throw new \InvalidArgumentException('Room not found');
        }

        $location = $room->locations()->create([
            'name' => $params['name'] ?? 'New Location',
            'location_type' => $params['location_type'] ?? 'wall',
            'cabinet_level' => $params['cabinet_level'] ?? 2,
        ]);

        return [
            'type' => 'location',
            'id' => $location->id,
            'name' => $location->name,
            'room_id' => $room->id,
        ];
    }

    /**
     * Execute add_cabinet_run command
     */
    protected function executeAddCabinetRun(Project $project, array $params): array
    {
        $locationId = $params['location_id'] ?? null;

        if (!$locationId) {
            throw new \InvalidArgumentException('location_id is required');
        }

        // Find location through project's rooms
        $location = null;
        foreach ($project->rooms as $room) {
            $location = $room->locations()->find($locationId);
            if ($location) break;
        }

        if (!$location) {
            throw new \InvalidArgumentException('Location not found');
        }

        $run = $location->cabinetRuns()->create([
            'name' => $params['name'] ?? 'New Run',
            'run_type' => $params['run_type'] ?? 'base',
            'total_linear_feet' => $params['total_linear_feet'] ?? 0,
        ]);

        return [
            'type' => 'cabinet_run',
            'id' => $run->id,
            'name' => $run->name,
            'location_id' => $location->id,
        ];
    }

    /**
     * Execute add_cabinets command
     */
    protected function executeAddCabinets(Project $project, array $params): array
    {
        $runId = $params['run_id'] ?? null;
        $cabinets = $params['cabinets'] ?? [];

        if (!$runId) {
            throw new \InvalidArgumentException('run_id is required');
        }

        // Find run through project hierarchy
        $run = null;
        foreach ($project->rooms as $room) {
            foreach ($room->locations as $location) {
                $run = $location->cabinetRuns()->find($runId);
                if ($run) break 2;
            }
        }

        if (!$run) {
            throw new \InvalidArgumentException('Cabinet run not found');
        }

        $created = [];
        foreach ($cabinets as $cabinetData) {
            $cabinet = $run->cabinets()->create([
                'name' => $cabinetData['name'] ?? 'Cabinet',
                'cabinet_type' => $cabinetData['cabinet_type'] ?? 'base',
                'width_inches' => $cabinetData['width_inches'] ?? 24,
                'quantity' => $cabinetData['quantity'] ?? 1,
            ]);
            $created[] = [
                'id' => $cabinet->id,
                'name' => $cabinet->name,
            ];
        }

        // Recalculate run totals
        $this->recalculateRunTotals($run);

        return [
            'type' => 'cabinets',
            'run_id' => $run->id,
            'created' => $created,
            'count' => count($created),
        ];
    }

    /**
     * Execute delete_entity command
     */
    protected function executeDeleteEntity(Project $project, array $params): array
    {
        $entityType = $params['entity_type'] ?? null;
        $entityId = $params['entity_id'] ?? null;

        if (!$entityType || !$entityId) {
            throw new \InvalidArgumentException('entity_type and entity_id are required');
        }

        switch ($entityType) {
            case 'room':
                $room = $project->rooms()->find($entityId);
                if ($room) {
                    $room->delete();
                    return ['deleted' => 'room', 'id' => $entityId];
                }
                break;

            case 'location':
                foreach ($project->rooms as $room) {
                    $location = $room->locations()->find($entityId);
                    if ($location) {
                        $location->delete();
                        return ['deleted' => 'location', 'id' => $entityId];
                    }
                }
                break;

            case 'cabinet_run':
                foreach ($project->rooms as $room) {
                    foreach ($room->locations as $location) {
                        $run = $location->cabinetRuns()->find($entityId);
                        if ($run) {
                            $run->delete();
                            return ['deleted' => 'cabinet_run', 'id' => $entityId];
                        }
                    }
                }
                break;

            case 'cabinet':
                foreach ($project->rooms as $room) {
                    foreach ($room->locations as $location) {
                        foreach ($location->cabinetRuns as $run) {
                            $cabinet = $run->cabinets()->find($entityId);
                            if ($cabinet) {
                                $cabinet->delete();
                                $this->recalculateRunTotals($run);
                                return ['deleted' => 'cabinet', 'id' => $entityId];
                            }
                        }
                    }
                }
                break;
        }

        throw new \InvalidArgumentException("Entity not found: {$entityType} #{$entityId}");
    }

    /**
     * Execute update_pricing command
     */
    protected function executeUpdatePricing(Project $project, array $params): array
    {
        $locationId = $params['location_id'] ?? null;
        $cabinetLevel = $params['cabinet_level'] ?? null;

        if (!$locationId || !$cabinetLevel) {
            throw new \InvalidArgumentException('location_id and cabinet_level are required');
        }

        // Find location
        $location = null;
        foreach ($project->rooms as $room) {
            $location = $room->locations()->find($locationId);
            if ($location) break;
        }

        if (!$location) {
            throw new \InvalidArgumentException('Location not found');
        }

        $location->update(['cabinet_level' => $cabinetLevel]);

        // Recalculate all runs in this location
        foreach ($location->cabinetRuns as $run) {
            $this->recalculateRunTotals($run, $cabinetLevel);
        }

        return [
            'type' => 'pricing_updated',
            'location_id' => $locationId,
            'cabinet_level' => $cabinetLevel,
        ];
    }

    /**
     * Recalculate run totals
     */
    protected function recalculateRunTotals($run, ?int $cabinetLevel = null): void
    {
        $totalInches = 0;
        foreach ($run->cabinets as $cabinet) {
            $totalInches += ($cabinet->width_inches ?? 0) * ($cabinet->quantity ?? 1);
        }

        $totalLinearFeet = $totalInches / 12;

        // Get price per LF from location's cabinet level
        $level = $cabinetLevel ?? $run->location->cabinet_level ?? 2;
        $pricePerLf = GeminiCabinetAssistantService::PRICING_TIERS[$level]['price'] ?? 298;

        $run->update([
            'total_linear_feet' => $totalLinearFeet,
            'estimated_price' => $totalLinearFeet * $pricePerLf,
        ]);
    }
}
