<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Base Resource Controller for V1 API
 *
 * Provides standardized CRUD operations with:
 * - Filtering: ?filter[field]=value
 * - Sorting: ?sort=-created_at,name (- prefix for desc)
 * - Pagination: ?page=1&per_page=50
 * - Relations: ?include=rooms,cabinets
 * - Search: ?search=kitchen
 */
abstract class BaseResourceController extends BaseApiController
{
    /**
     * The Eloquent model class for this resource
     */
    protected string $modelClass;

    /**
     * Fields that can be searched with ?search=
     */
    protected array $searchableFields = ['name'];

    /**
     * Fields that can be filtered with ?filter[field]=value
     */
    protected array $filterableFields = [];

    /**
     * Fields that can be sorted with ?sort=field
     */
    protected array $sortableFields = ['id', 'created_at', 'updated_at'];

    /**
     * Relations that can be included with ?include=relation
     */
    protected array $includableRelations = [];

    /**
     * Default number of items per page
     */
    protected int $defaultPerPage = 25;

    /**
     * Maximum number of items per page
     */
    protected int $maxPerPage = 100;

    /**
     * Resource name for messages
     */
    protected function getResourceName(): string
    {
        return Str::headline(class_basename($this->modelClass));
    }

    /**
     * Get the base query for the resource
     */
    protected function getBaseQuery(): Builder
    {
        return $this->modelClass::query();
    }

    /**
     * Get validation rules for store operation
     */
    abstract protected function validateStore(): array;

    /**
     * Get validation rules for update operation
     */
    abstract protected function validateUpdate(): array;

    /**
     * Transform a model for API response
     */
    protected function transformModel(Model $model): array
    {
        return $model->toArray();
    }

    /**
     * GET /resource - List all resources
     */
    public function index(Request $request): JsonResponse
    {
        // Check viewAny permission
        if (!$this->authorizeAction('viewAny')) {
            return $this->forbidden("You don't have permission to view {$this->getResourceName()} records");
        }

        $query = $this->getBaseQuery();

        // Apply includes
        $query = $this->applyIncludes($query, $request);

        // Apply filters
        $query = $this->applyFilters($query, $request);

        // Apply search
        $query = $this->applySearch($query, $request);

        // Apply sorting
        $query = $this->applySorting($query, $request);

        // Paginate
        $perPage = $this->getPerPage($request);
        $paginator = $query->paginate($perPage);

        // Transform results
        $data = collect($paginator->items())->map(fn ($model) => $this->transformModel($model));

        return response()->json([
            'success' => true,
            'message' => "{$this->getResourceName()} retrieved",
            'data' => $data,
            'pagination' => [
                'total' => $paginator->total(),
                'count' => $paginator->count(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'total_pages' => $paginator->lastPage(),
                'links' => [
                    'first' => $paginator->url(1),
                    'last' => $paginator->url($paginator->lastPage()),
                    'next' => $paginator->nextPageUrl(),
                    'previous' => $paginator->previousPageUrl(),
                ],
            ],
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * POST /resource - Create a new resource
     */
    public function store(Request $request): JsonResponse
    {
        // Check create permission
        if (!$this->authorizeAction('create')) {
            return $this->forbidden("You don't have permission to create {$this->getResourceName()} records");
        }

        $validated = $request->validate($this->validateStore());

        // Allow child classes to modify data before creation
        $validated = $this->beforeStore($validated, $request);

        $model = $this->modelClass::create($validated);

        // Allow child classes to perform actions after creation
        $this->afterStore($model, $request);

        // Dispatch webhook event
        $this->dispatchWebhookEvent($model, 'created');

        return $this->success(
            $this->transformModel($model->fresh()),
            "{$this->getResourceName()} created",
            201
        );
    }

    /**
     * GET /resource/{id} - Show a single resource
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $query = $this->getBaseQuery();

        // Apply includes
        $query = $this->applyIncludes($query, $request);

        $model = $query->find($id);

        if (!$model) {
            return $this->notFound("{$this->getResourceName()} not found");
        }

        // Check view permission
        if (!$this->authorizeAction('view', $model)) {
            return $this->forbidden("You don't have permission to view this {$this->getResourceName()}");
        }

        return $this->success(
            $this->transformModel($model),
            "{$this->getResourceName()} retrieved"
        );
    }

    /**
     * PUT/PATCH /resource/{id} - Update a resource
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $model = $this->modelClass::find($id);

        if (!$model) {
            return $this->notFound("{$this->getResourceName()} not found");
        }

        // Check update permission
        if (!$this->authorizeAction('update', $model)) {
            return $this->forbidden("You don't have permission to update this {$this->getResourceName()}");
        }

        $validated = $request->validate($this->validateUpdate());

        // Allow child classes to modify data before update
        $validated = $this->beforeUpdate($validated, $model, $request);

        $model->update($validated);

        // Allow child classes to perform actions after update
        $this->afterUpdate($model, $request);

        // Dispatch webhook event
        $this->dispatchWebhookEvent($model, 'updated');

        return $this->success(
            $this->transformModel($model->fresh()),
            "{$this->getResourceName()} updated"
        );
    }

    /**
     * DELETE /resource/{id} - Delete a resource
     */
    public function destroy(int $id): JsonResponse
    {
        $model = $this->modelClass::find($id);

        if (!$model) {
            return $this->notFound("{$this->getResourceName()} not found");
        }

        // Check delete permission
        if (!$this->authorizeAction('delete', $model)) {
            return $this->forbidden("You don't have permission to delete this {$this->getResourceName()}");
        }

        // Allow child classes to perform actions before deletion
        $this->beforeDestroy($model);

        // Store model data for webhook before deleting
        $modelData = $this->transformModel($model);

        $model->delete();

        // Dispatch webhook event with stored data
        $this->dispatchWebhookEventWithData($modelData, 'deleted');

        return $this->success(null, "{$this->getResourceName()} deleted");
    }

    /**
     * Apply relation includes to query
     */
    protected function applyIncludes(Builder $query, Request $request): Builder
    {
        $includes = $request->query('include');

        if (empty($includes)) {
            return $query;
        }

        $relations = explode(',', $includes);
        $validRelations = array_intersect($relations, $this->includableRelations);

        if (!empty($validRelations)) {
            $query->with($validRelations);
        }

        return $query;
    }

    /**
     * Apply filters to query
     */
    protected function applyFilters(Builder $query, Request $request): Builder
    {
        $filters = $request->query('filter', []);

        if (!is_array($filters)) {
            return $query;
        }

        foreach ($filters as $field => $value) {
            if (!in_array($field, $this->filterableFields)) {
                continue;
            }

            // Handle null values
            if ($value === 'null') {
                $query->whereNull($field);
                continue;
            }

            // Handle arrays (IN clause)
            if (is_array($value)) {
                $query->whereIn($field, $value);
                continue;
            }

            // Handle operators
            if (preg_match('/^(gte|gt|lte|lt|ne):(.+)$/', $value, $matches)) {
                $operator = match ($matches[1]) {
                    'gte' => '>=',
                    'gt' => '>',
                    'lte' => '<=',
                    'lt' => '<',
                    'ne' => '!=',
                };
                $query->where($field, $operator, $matches[2]);
                continue;
            }

            // Handle LIKE search with * wildcard
            if (str_contains($value, '*')) {
                $query->where($field, 'LIKE', str_replace('*', '%', $value));
                continue;
            }

            // Exact match
            $query->where($field, $value);
        }

        return $query;
    }

    /**
     * Apply search to query
     */
    protected function applySearch(Builder $query, Request $request): Builder
    {
        $search = $request->query('search');

        if (empty($search) || empty($this->searchableFields)) {
            return $query;
        }

        $query->where(function ($q) use ($search) {
            foreach ($this->searchableFields as $field) {
                $q->orWhere($field, 'LIKE', "%{$search}%");
            }
        });

        return $query;
    }

    /**
     * Apply sorting to query
     */
    protected function applySorting(Builder $query, Request $request): Builder
    {
        $sort = $request->query('sort');

        if (empty($sort)) {
            return $query->orderBy('id', 'desc');
        }

        $fields = explode(',', $sort);

        foreach ($fields as $field) {
            $direction = 'asc';

            if (str_starts_with($field, '-')) {
                $direction = 'desc';
                $field = substr($field, 1);
            }

            if (in_array($field, $this->sortableFields)) {
                $query->orderBy($field, $direction);
            }
        }

        return $query;
    }

    /**
     * Get per_page value from request
     */
    protected function getPerPage(Request $request): int
    {
        $perPage = $request->query('per_page', $this->defaultPerPage);
        return min(max((int) $perPage, 1), $this->maxPerPage);
    }

    /**
     * Hook: Modify data before store
     */
    protected function beforeStore(array $data, Request $request): array
    {
        return $data;
    }

    /**
     * Hook: After store
     */
    protected function afterStore(Model $model, Request $request): void
    {
        // Override in child classes
    }

    /**
     * Hook: Modify data before update
     */
    protected function beforeUpdate(array $data, Model $model, Request $request): array
    {
        return $data;
    }

    /**
     * Hook: After update
     */
    protected function afterUpdate(Model $model, Request $request): void
    {
        // Override in child classes
    }

    /**
     * Hook: Before destroy
     */
    protected function beforeDestroy(Model $model): void
    {
        // Override in child classes
    }

    /**
     * Dispatch webhook event for model changes
     */
    protected function dispatchWebhookEvent(Model $model, string $action): void
    {
        $eventName = Str::snake(class_basename($model)) . '.' . $action;
        \App\Jobs\DispatchWebhook::dispatch($eventName, $this->transformModel($model));
    }

    /**
     * Dispatch webhook event with custom data
     */
    protected function dispatchWebhookEventWithData(array $data, string $action): void
    {
        $eventName = Str::snake(class_basename($this->modelClass)) . '.' . $action;
        \App\Jobs\DispatchWebhook::dispatch($eventName, $data);
    }

    /**
     * Check if user has permission for this action
     */
    protected function checkAbility(string $ability): bool
    {
        $resourceName = Str::kebab(Str::plural(class_basename($this->modelClass)));
        return request()->user()->tokenCan("{$resourceName}:{$ability}") ||
               request()->user()->tokenCan('*');
    }

    /**
     * Authorize an action using the model's policy
     *
     * Maps standard CRUD actions to Filament Shield permission names:
     * - viewAny -> view_any_{resource}
     * - view -> view_{resource}
     * - create -> create_{resource}
     * - update -> update_{resource}
     * - delete -> delete_{resource}
     *
     * @param string $action The action to authorize (viewAny, view, create, update, delete)
     * @param Model|null $model The model instance for instance-level checks
     * @return bool
     */
    protected function authorizeAction(string $action, ?Model $model = null): bool
    {
        $user = request()->user();

        if (!$user) {
            return false;
        }

        // Get the resource name for permission (e.g., 'project', 'cabinet')
        $resourceName = Str::snake(class_basename($this->modelClass));

        // Map action to Filament Shield permission name
        $permissionMap = [
            'viewAny' => "view_any_{$resourceName}",
            'view' => "view_{$resourceName}",
            'create' => "create_{$resourceName}",
            'update' => "update_{$resourceName}",
            'delete' => "delete_{$resourceName}",
            'deleteAny' => "delete_any_{$resourceName}",
            'forceDelete' => "force_delete_{$resourceName}",
            'restore' => "restore_{$resourceName}",
        ];

        $permission = $permissionMap[$action] ?? "{$action}_{$resourceName}";

        // Check if user has the permission
        if (!$user->can($permission)) {
            return false;
        }

        // For instance-level actions, also check the policy if it exists
        if ($model && in_array($action, ['view', 'update', 'delete', 'forceDelete', 'restore'])) {
            try {
                return $user->can($action, $model);
            } catch (\Exception $e) {
                // If policy doesn't exist, fall back to permission check only
                return true;
            }
        }

        return true;
    }

    /**
     * Get the permission name for the current resource
     */
    protected function getPermissionName(string $action): string
    {
        $resourceName = Str::snake(class_basename($this->modelClass));
        return "{$action}_{$resourceName}";
    }
}
