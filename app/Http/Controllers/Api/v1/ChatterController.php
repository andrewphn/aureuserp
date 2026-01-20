<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Webkul\Chatter\Models\Message;

/**
 * Chatter Controller - API for messages/notes on any resource
 *
 * Chatter is a polymorphic messaging system that can be attached to any model.
 * Use messageable_type and messageable_id to link messages to resources.
 *
 * Common messageable_types:
 * - Webkul\Project\Models\Project
 * - Webkul\Project\Models\Cabinet
 * - Webkul\Partner\Models\Partner
 * - Webkul\Sales\Models\Order
 */
class ChatterController extends BaseResourceController
{
    protected string $modelClass = Message::class;

    protected array $searchableFields = ['subject', 'body', 'name', 'summary'];

    protected array $filterableFields = [
        'messageable_type',
        'messageable_id',
        'type',
        'is_internal',
        'activity_type_id',
        'causer_id',
        'assigned_to',
    ];

    protected array $sortableFields = [
        'id',
        'created_at',
        'updated_at',
        'date_deadline',
        'pinned_at',
    ];

    protected array $includableRelations = [
        'attachments',
        'causer',
        'activityType',
        'assignedTo',
        'company',
    ];

    protected function validateStore(): array
    {
        return [
            'messageable_type' => 'required|string',
            'messageable_id' => 'required|integer',
            'type' => 'nullable|string|in:comment,note,activity,log',
            'subject' => 'nullable|string|max:255',
            'body' => 'nullable|string',
            'summary' => 'nullable|string|max:500',
            'is_internal' => 'boolean',
            'date_deadline' => 'nullable|date',
            'activity_type_id' => 'nullable|integer|exists:support_activity_types,id',
            'assigned_to' => 'nullable|integer|exists:users,id',
        ];
    }

    protected function validateUpdate(): array
    {
        return [
            'type' => 'nullable|string|in:comment,note,activity,log',
            'subject' => 'nullable|string|max:255',
            'body' => 'nullable|string',
            'summary' => 'nullable|string|max:500',
            'is_internal' => 'boolean',
            'date_deadline' => 'nullable|date',
            'activity_type_id' => 'nullable|integer|exists:support_activity_types,id',
            'assigned_to' => 'nullable|integer|exists:users,id',
            'pinned_at' => 'nullable|date',
        ];
    }

    protected function beforeStore(array $data, Request $request): array
    {
        // Set company_id from authenticated user if not provided
        if (empty($data['company_id'])) {
            $data['company_id'] = $request->user()->default_company_id ?? 1;
        }

        return $data;
    }

    /**
     * GET /chatter/for/{type}/{id} - Get all messages for a specific resource
     *
     * Example: GET /api/v1/chatter/for/project/123
     */
    public function forResource(Request $request, string $type, int $id): JsonResponse
    {
        // Map short type names to full model classes
        $typeMap = $this->getTypeMap();
        $messageableType = $typeMap[$type] ?? $type;

        // Check permission on the parent resource
        if (!$this->canAccessParentResource($type, $id)) {
            return $this->forbidden("You don't have permission to view messages for this {$type}");
        }

        $query = Message::query()
            ->where('messageable_type', $messageableType)
            ->where('messageable_id', $id);

        // Apply includes
        $query = $this->applyIncludes($query, $request);

        // Apply additional filters
        if ($request->has('type')) {
            $query->where('type', $request->query('type'));
        }

        if ($request->has('is_internal')) {
            $query->where('is_internal', $request->boolean('is_internal'));
        }

        // Order by pinned first, then by created_at desc
        $query->orderByRaw('pinned_at IS NOT NULL DESC')
              ->orderBy('created_at', 'desc');

        $perPage = $this->getPerPage($request);
        $paginator = $query->paginate($perPage);

        $data = collect($paginator->items())->map(fn ($model) => $this->transformModel($model));

        return response()->json([
            'success' => true,
            'message' => 'Chatter messages retrieved',
            'data' => $data,
            'resource' => [
                'type' => $type,
                'id' => $id,
                'messageable_type' => $messageableType,
            ],
            'pagination' => [
                'total' => $paginator->total(),
                'count' => $paginator->count(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'total_pages' => $paginator->lastPage(),
            ],
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * POST /chatter/for/{type}/{id} - Add a message to a specific resource
     *
     * Example: POST /api/v1/chatter/for/project/123
     */
    public function addToResource(Request $request, string $type, int $id): JsonResponse
    {
        // Check permission on the parent resource (need update permission to add messages)
        if (!$this->canAccessParentResource($type, $id, 'update')) {
            return $this->forbidden("You don't have permission to add messages to this {$type}");
        }

        $typeMap = $this->getTypeMap();
        $messageableType = $typeMap[$type] ?? $type;

        // Merge the resource info into the request
        $request->merge([
            'messageable_type' => $messageableType,
            'messageable_id' => $id,
        ]);

        return $this->store($request);
    }

    /**
     * POST /chatter/{id}/pin - Pin a message
     */
    public function pin(int $id): JsonResponse
    {
        $message = Message::find($id);

        if (!$message) {
            return $this->notFound('Message not found');
        }

        // Check update permission on message
        if (!$this->authorizeAction('update', $message)) {
            return $this->forbidden("You don't have permission to pin this message");
        }

        $message->update(['pinned_at' => now()]);

        return $this->success(
            $this->transformModel($message->fresh()),
            'Message pinned'
        );
    }

    /**
     * POST /chatter/{id}/unpin - Unpin a message
     */
    public function unpin(int $id): JsonResponse
    {
        $message = Message::find($id);

        if (!$message) {
            return $this->notFound('Message not found');
        }

        // Check update permission on message
        if (!$this->authorizeAction('update', $message)) {
            return $this->forbidden("You don't have permission to unpin this message");
        }

        $message->update(['pinned_at' => null]);

        return $this->success(
            $this->transformModel($message->fresh()),
            'Message unpinned'
        );
    }

    /**
     * GET /chatter/types - Get available messageable types
     */
    public function types(): JsonResponse
    {
        return $this->success([
            'types' => $this->getTypeMap(),
            'message_types' => ['comment', 'note', 'activity', 'log'],
        ], 'Chatter types retrieved');
    }

    /**
     * Get the type mapping for messageable types
     */
    protected function getTypeMap(): array
    {
        return [
            'project' => 'Webkul\\Project\\Models\\Project',
            'cabinet' => 'Webkul\\Project\\Models\\Cabinet',
            'room' => 'Webkul\\Project\\Models\\Room',
            'partner' => 'Webkul\\Partner\\Models\\Partner',
            'sales_order' => 'Webkul\\Sales\\Models\\Order',
            'purchase_order' => 'Webkul\\Purchase\\Models\\Order',
            'invoice' => 'Webkul\\Account\\Models\\Move',
            'task' => 'Webkul\\Project\\Models\\Task',
            'employee' => 'Webkul\\Employee\\Models\\Employee',
        ];
    }

    /**
     * Check if user can access the parent resource
     *
     * @param string $type The resource type (project, cabinet, etc.)
     * @param int $id The resource ID
     * @param string $action The action (view, update, etc.)
     * @return bool
     */
    protected function canAccessParentResource(string $type, int $id, string $action = 'view'): bool
    {
        $user = request()->user();
        if (!$user) {
            return false;
        }

        $typeMap = $this->getTypeMap();
        $modelClass = $typeMap[$type] ?? null;

        if (!$modelClass || !class_exists($modelClass)) {
            return false;
        }

        // Get the resource name for permission
        $resourceName = \Illuminate\Support\Str::snake(class_basename($modelClass));
        $permission = "{$action}_{$resourceName}";

        // Check permission
        if (!$user->can($permission)) {
            return false;
        }

        // For instance-level check, verify the resource exists and user can access it
        $model = $modelClass::find($id);
        if (!$model) {
            return false;
        }

        // Try to check policy if it exists
        try {
            return $user->can($action, $model);
        } catch (\Exception $e) {
            // If no policy, just return based on permission
            return true;
        }
    }
}
