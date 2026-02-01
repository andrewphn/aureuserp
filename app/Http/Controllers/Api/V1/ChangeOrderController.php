<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Webkul\Project\Models\ChangeOrder;

/**
 * Change Order Controller for V1 API
 *
 * Handles project change orders for tracking modifications
 * to project scope, pricing, or schedule after initial agreement.
 */
class ChangeOrderController extends BaseResourceController
{
    protected string $modelClass = ChangeOrder::class;

    protected array $searchableFields = [
        'number',
        'title',
        'description',
        'reason',
    ];

    protected array $filterableFields = [
        'id',
        'project_id',
        'status',
        'change_type',
        'priority',
        'requested_by',
        'approved_by',
        'company_id',
    ];

    protected array $sortableFields = [
        'id',
        'number',
        'title',
        'requested_at',
        'approved_at',
        'amount',
        'created_at',
        'updated_at',
    ];

    protected array $includableRelations = [
        'project',
        'requestedBy',
        'approvedBy',
        'company',
    ];

    protected function validateStore(): array
    {
        return [
            'project_id' => 'required|integer|exists:projects_projects,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'reason' => 'nullable|string',
            'change_type' => 'required|string|in:scope,pricing,schedule,design,materials,other',
            'priority' => 'nullable|string|in:low,medium,high,critical',
            'amount' => 'nullable|numeric',
            'days_impact' => 'nullable|integer',
            'company_id' => 'nullable|integer|exists:companies,id',
        ];
    }

    protected function validateUpdate(): array
    {
        return [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'reason' => 'nullable|string',
            'change_type' => 'sometimes|string|in:scope,pricing,schedule,design,materials,other',
            'priority' => 'nullable|string|in:low,medium,high,critical',
            'amount' => 'nullable|numeric',
            'days_impact' => 'nullable|integer',
            'status' => 'nullable|string|in:draft,pending,approved,rejected,cancelled',
        ];
    }

    protected function beforeStore(array $data, Request $request): array
    {
        if (!isset($data['creator_id'])) {
            $data['creator_id'] = $request->user()->id;
        }

        if (!isset($data['requested_by'])) {
            $data['requested_by'] = $request->user()->id;
        }

        if (!isset($data['requested_at'])) {
            $data['requested_at'] = now();
        }

        if (!isset($data['status'])) {
            $data['status'] = 'draft';
        }

        if (!isset($data['priority'])) {
            $data['priority'] = 'medium';
        }

        // Generate change order number
        if (!isset($data['number'])) {
            $data['number'] = $this->generateChangeOrderNumber($data['project_id']);
        }

        return $data;
    }

    /**
     * Generate a unique change order number for the project
     */
    protected function generateChangeOrderNumber(int $projectId): string
    {
        $count = ChangeOrder::where('project_id', $projectId)->count();
        return 'CO-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
    }

    protected function transformModel(Model $model): array
    {
        $data = $model->toArray();

        // Add computed fields
        $data['is_draft'] = $model->status === 'draft';
        $data['is_pending'] = $model->status === 'pending';
        $data['is_approved'] = $model->status === 'approved';
        $data['is_rejected'] = $model->status === 'rejected';
        $data['can_approve'] = in_array($model->status, ['draft', 'pending']);
        $data['can_reject'] = in_array($model->status, ['draft', 'pending']);

        return $data;
    }

    /**
     * POST /change-orders/{id}/submit - Submit change order for approval
     */
    public function submit(int $id): JsonResponse
    {
        $changeOrder = ChangeOrder::find($id);

        if (!$changeOrder) {
            return $this->notFound('Change order not found');
        }

        if ($changeOrder->status !== 'draft') {
            return $this->error('Only draft change orders can be submitted', 422);
        }

        $changeOrder->update([
            'status' => 'pending',
        ]);

        $this->dispatchWebhookEvent($changeOrder->fresh(), 'submitted');

        return $this->success(
            $this->transformModel($changeOrder->fresh()),
            'Change order submitted for approval'
        );
    }

    /**
     * POST /change-orders/{id}/approve - Approve a change order
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $changeOrder = ChangeOrder::find($id);

        if (!$changeOrder) {
            return $this->notFound('Change order not found');
        }

        if (!in_array($changeOrder->status, ['draft', 'pending'])) {
            return $this->error('Change order cannot be approved in current status', 422);
        }

        $validated = $request->validate([
            'notes' => 'nullable|string',
        ]);

        $changeOrder->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'approval_notes' => $validated['notes'] ?? null,
        ]);

        // Update project pricing if amount specified
        if ($changeOrder->amount && $changeOrder->amount != 0) {
            $project = $changeOrder->project;
            if ($project) {
                // Add change order amount to project total
                $project->updateQuietly([
                    'change_order_total' => ($project->change_order_total ?? 0) + $changeOrder->amount,
                ]);
            }
        }

        $this->dispatchWebhookEvent($changeOrder->fresh(), 'approved');

        return $this->success(
            $this->transformModel($changeOrder->fresh()),
            'Change order approved'
        );
    }

    /**
     * POST /change-orders/{id}/reject - Reject a change order
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $changeOrder = ChangeOrder::find($id);

        if (!$changeOrder) {
            return $this->notFound('Change order not found');
        }

        if (!in_array($changeOrder->status, ['draft', 'pending'])) {
            return $this->error('Change order cannot be rejected in current status', 422);
        }

        $validated = $request->validate([
            'reason' => 'required|string',
        ]);

        $changeOrder->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['reason'],
            'rejected_at' => now(),
        ]);

        $this->dispatchWebhookEvent($changeOrder->fresh(), 'rejected');

        return $this->success(
            $this->transformModel($changeOrder->fresh()),
            'Change order rejected'
        );
    }

    /**
     * POST /change-orders/{id}/cancel - Cancel a change order
     */
    public function cancel(int $id): JsonResponse
    {
        $changeOrder = ChangeOrder::find($id);

        if (!$changeOrder) {
            return $this->notFound('Change order not found');
        }

        if ($changeOrder->status === 'cancelled') {
            return $this->error('Change order is already cancelled', 422);
        }

        if ($changeOrder->status === 'approved') {
            return $this->error('Approved change orders cannot be cancelled', 422);
        }

        $changeOrder->update([
            'status' => 'cancelled',
        ]);

        $this->dispatchWebhookEvent($changeOrder->fresh(), 'cancelled');

        return $this->success(
            $this->transformModel($changeOrder->fresh()),
            'Change order cancelled'
        );
    }

    /**
     * GET /change-orders/by-project/{projectId} - Get all change orders for a project
     */
    public function byProject(int $projectId): JsonResponse
    {
        $changeOrders = ChangeOrder::with(['requestedBy', 'approvedBy'])
            ->where('project_id', $projectId)
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate totals
        $approved = $changeOrders->where('status', 'approved');
        $totals = [
            'count' => $changeOrders->count(),
            'approved_count' => $approved->count(),
            'pending_count' => $changeOrders->where('status', 'pending')->count(),
            'total_amount' => $approved->sum('amount'),
            'total_days_impact' => $approved->sum('days_impact'),
        ];

        return $this->success([
            'project_id' => $projectId,
            'change_orders' => $changeOrders->map(fn($co) => $this->transformModel($co)),
            'totals' => $totals,
        ], 'Change orders retrieved');
    }
}
