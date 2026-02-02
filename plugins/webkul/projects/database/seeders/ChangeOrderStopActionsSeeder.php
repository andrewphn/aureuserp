<?php

namespace Webkul\Project\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Webkul\Project\Models\ChangeOrder;
use Webkul\Project\Models\ChangeOrderStopAction;
use Webkul\Project\Models\Project;
use Webkul\Project\Models\Task;
use Webkul\Project\Enums\TaskState;

/**
 * Change Order Stop Actions Seeder
 *
 * Seeds sample change order stop action data for testing.
 * Creates a change order in approved state with sample stop actions
 * that demonstrate blocked tasks, delivery blocked, and notifications.
 */
class ChangeOrderStopActionsSeeder extends Seeder
{
    /**
     * Run the seeder.
     */
    public function run(): void
    {
        echo "\n=== Change Order Stop Actions Seeder ===\n\n";

        // Find a project to attach the change order to
        $project = Project::first();

        if (!$project) {
            echo "No projects found. Please run TcsSampleDataSeeder first.\n";
            return;
        }

        // Get a user ID for the performer
        $userId = DB::table('users')->first()?->id ?? 1;

        // Check if there's already an approved change order
        $changeOrder = ChangeOrder::where('project_id', $project->id)
            ->where('status', ChangeOrder::STATUS_APPROVED)
            ->first();

        if (!$changeOrder) {
            // Create a new change order in approved state
            $changeOrder = ChangeOrder::create([
                'project_id' => $project->id,
                'change_order_number' => 'CO-TEST-001',
                'title' => 'Test Change Order - Cabinet Material Change',
                'description' => 'Customer requested upgrade from paint grade to stain grade on kitchen cabinets.',
                'reason' => ChangeOrder::REASON_CLIENT_REQUEST,
                'status' => ChangeOrder::STATUS_APPROVED,
                'requested_by' => $userId,
                'requested_at' => Carbon::now()->subDays(3),
                'approved_by' => $userId,
                'approved_at' => Carbon::now()->subDays(1),
                'approval_notes' => 'Approved with revised pricing.',
                'price_delta' => 2500.00,
                'affected_stage' => 'production',
            ]);

            echo "Created test change order: {$changeOrder->change_order_number}\n";
        } else {
            echo "Using existing change order: {$changeOrder->change_order_number}\n";
        }

        // Update project with pending change order flags
        $project->update([
            'has_pending_change_order' => true,
            'active_change_order_id' => $changeOrder->id,
            'delivery_blocked' => true,
        ]);
        echo "Updated project flags for pending change order.\n";

        // Clear existing stop actions for this change order
        ChangeOrderStopAction::where('change_order_id', $changeOrder->id)->delete();

        // Create sample stop actions
        $stopActions = [];

        // 1. Block some tasks
        $tasks = Task::where('project_id', $project->id)
            ->whereIn('state', [TaskState::PENDING, TaskState::IN_PROGRESS])
            ->limit(3)
            ->get();

        foreach ($tasks as $task) {
            $previousState = $task->state->value;

            // Update task to blocked state
            $task->update([
                'blocked_by_change_order_id' => $changeOrder->id,
                'state_before_block' => $previousState,
                'state' => TaskState::BLOCKED,
            ]);

            $stopActions[] = ChangeOrderStopAction::create([
                'change_order_id' => $changeOrder->id,
                'action_type' => ChangeOrderStopAction::TYPE_TASK_BLOCKED,
                'entity_type' => 'Task',
                'entity_id' => $task->id,
                'previous_state' => $previousState,
                'new_state' => TaskState::BLOCKED->value,
                'performed_by' => $userId,
                'performed_at' => Carbon::now()->subDay(),
                'metadata' => [
                    'task_title' => $task->title,
                    'project_name' => $project->name,
                ],
            ]);

            echo "  - Blocked task: {$task->title}\n";
        }

        // 2. Block delivery
        $stopActions[] = ChangeOrderStopAction::create([
            'change_order_id' => $changeOrder->id,
            'action_type' => ChangeOrderStopAction::TYPE_DELIVERY_BLOCKED,
            'entity_type' => 'Project',
            'entity_id' => $project->id,
            'previous_state' => 'not_blocked',
            'new_state' => 'blocked',
            'performed_by' => $userId,
            'performed_at' => Carbon::now()->subDay(),
            'metadata' => [
                'project_name' => $project->name,
                'original_delivery_date' => $project->end_date?->toDateString(),
            ],
        ]);
        echo "  - Blocked delivery schedule\n";

        // 3. Record notification sent
        $stopActions[] = ChangeOrderStopAction::create([
            'change_order_id' => $changeOrder->id,
            'action_type' => ChangeOrderStopAction::TYPE_NOTIFICATION_SENT,
            'entity_type' => 'User',
            'entity_id' => $userId,
            'previous_state' => null,
            'new_state' => 'notified',
            'performed_by' => $userId,
            'performed_at' => Carbon::now()->subDay(),
            'metadata' => [
                'notification_type' => 'change_order_approved',
                'channel' => 'database',
                'recipient_role' => 'project_manager',
            ],
        ]);
        echo "  - Recorded notification sent\n";

        // Summary
        $totalActions = count($stopActions);
        echo "\n=== Seeding Complete ===\n";
        echo "Created {$totalActions} stop actions for change order {$changeOrder->change_order_number}\n\n";
    }
}
