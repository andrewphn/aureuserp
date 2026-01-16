<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BatchController extends BaseApiController
{
    /**
     * Resource to controller mapping
     */
    protected array $resourceMap = [
        'projects' => ProjectController::class,
        'rooms' => RoomController::class,
        'room-locations' => RoomLocationController::class,
        'cabinet-runs' => CabinetRunController::class,
        'cabinets' => CabinetController::class,
        'cabinet-sections' => CabinetSectionController::class,
        'drawers' => DrawerController::class,
        'doors' => DoorController::class,
        'shelves' => ShelfController::class,
        'pullouts' => PulloutController::class,
        'stretchers' => StretcherController::class,
        'faceframes' => FaceframeController::class,
        'tasks' => TaskController::class,
        'milestones' => MilestoneController::class,
        'employees' => EmployeeController::class,
        'departments' => DepartmentController::class,
        'calendars' => CalendarController::class,
        'products' => ProductController::class,
        'warehouses' => WarehouseController::class,
        'locations' => LocationController::class,
        'moves' => MoveController::class,
        'partners' => PartnerController::class,
    ];

    /**
     * Handle batch operations for a resource
     *
     * Supports:
     * - create: Create multiple records
     * - update: Update multiple records
     * - delete: Delete multiple records
     */
    public function handle(Request $request, string $resource): JsonResponse
    {
        if (!isset($this->resourceMap[$resource])) {
            return $this->error("Unknown resource: {$resource}", null, 400);
        }

        $validated = $request->validate([
            'operation' => 'required|string|in:create,update,delete',
            'data' => 'required|array|min:1|max:100',
            'data.*.id' => 'required_if:operation,update,delete|integer',
        ]);

        $operation = $validated['operation'];
        $data = $validated['data'];
        $controller = app($this->resourceMap[$resource]);

        $results = [
            'success' => [],
            'failed' => [],
        ];

        DB::beginTransaction();

        try {
            foreach ($data as $index => $item) {
                try {
                    $result = match ($operation) {
                        'create' => $this->batchCreate($controller, $item, $request),
                        'update' => $this->batchUpdate($controller, $item, $request),
                        'delete' => $this->batchDelete($controller, $item['id']),
                    };

                    $results['success'][] = [
                        'index' => $index,
                        'id' => $result['id'] ?? $item['id'] ?? null,
                        'data' => $result['data'] ?? null,
                    ];
                } catch (\Illuminate\Validation\ValidationException $e) {
                    $results['failed'][] = [
                        'index' => $index,
                        'id' => $item['id'] ?? null,
                        'error' => 'Validation failed',
                        'details' => $e->errors(),
                    ];
                } catch (\Exception $e) {
                    $results['failed'][] = [
                        'index' => $index,
                        'id' => $item['id'] ?? null,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            // If any operations failed, rollback the entire batch
            if (!empty($results['failed'])) {
                DB::rollBack();
                return $this->error(
                    'Batch operation partially failed',
                    $results,
                    422
                );
            }

            DB::commit();

            return $this->success($results, sprintf(
                'Batch %s completed: %d succeeded',
                $operation,
                count($results['success'])
            ));
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Batch operation failed: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Execute batch create
     */
    protected function batchCreate($controller, array $data, Request $request): array
    {
        $fakeRequest = Request::create('', 'POST', $data);
        $fakeRequest->setUserResolver(fn () => $request->user());

        $response = $controller->store($fakeRequest);
        $content = json_decode($response->getContent(), true);

        if ($response->getStatusCode() >= 400) {
            throw new \Exception($content['message'] ?? 'Create failed');
        }

        return [
            'id' => $content['data']['id'] ?? null,
            'data' => $content['data'] ?? null,
        ];
    }

    /**
     * Execute batch update
     */
    protected function batchUpdate($controller, array $data, Request $request): array
    {
        $id = $data['id'];
        unset($data['id']);

        $fakeRequest = Request::create('', 'PUT', $data);
        $fakeRequest->setUserResolver(fn () => $request->user());

        $response = $controller->update($fakeRequest, $id);
        $content = json_decode($response->getContent(), true);

        if ($response->getStatusCode() >= 400) {
            throw new \Exception($content['message'] ?? 'Update failed');
        }

        return [
            'id' => $id,
            'data' => $content['data'] ?? null,
        ];
    }

    /**
     * Execute batch delete
     */
    protected function batchDelete($controller, int $id): array
    {
        $response = $controller->destroy($id);
        $content = json_decode($response->getContent(), true);

        if ($response->getStatusCode() >= 400) {
            throw new \Exception($content['message'] ?? 'Delete failed');
        }

        return ['id' => $id];
    }
}
