<?php

namespace Webkul\Project\Services\ChangeOrders;

use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Webkul\Project\Models\ChangeOrder;
use Webkul\Project\Models\Project;
use Webkul\Security\Models\User;

/**
 * Change Order Notification Service
 *
 * Handles sending notifications to stakeholders about change order events.
 * Uses Filament database notifications for in-app alerts.
 */
class ChangeOrderNotificationService
{
    /**
     * Notify PM that a change order has been submitted for approval.
     */
    public function notifySubmitted(ChangeOrder $changeOrder): void
    {
        $project = $changeOrder->project;
        $pm = $project->user;

        if (!$pm) {
            Log::warning('Cannot notify PM - no project manager assigned', [
                'project_id' => $project->id,
                'change_order_id' => $changeOrder->id,
            ]);
            return;
        }

        Notification::make()
            ->title('Change Order Submitted for Approval')
            ->body("Change order {$changeOrder->change_order_number} for project {$project->name} requires your approval.")
            ->icon('heroicon-o-document-check')
            ->warning()
            ->actions([
                \Filament\Notifications\Actions\Action::make('view')
                    ->label('View Change Order')
                    ->url(route('filament.admin.projects.resources.change-orders.view', ['record' => $changeOrder->id]))
                    ->markAsRead(),
            ])
            ->sendToDatabase($pm);

        Log::info('Notified PM of change order submission', [
            'change_order_id' => $changeOrder->id,
            'pm_id' => $pm->id,
        ]);
    }

    /**
     * Notify stakeholders that a change order has been approved
     * and stop actions have been executed.
     */
    public function notifyApproved(ChangeOrder $changeOrder, array $stopActionsSummary = []): void
    {
        $project = $changeOrder->project;
        $recipients = $this->getStakeholders($project);

        $blockedTasksCount = $stopActionsSummary['tasks_blocked'] ?? 0;
        $heldPOsCount = $stopActionsSummary['pos_held'] ?? 0;

        $bodyParts = [
            "Change order {$changeOrder->change_order_number} for project {$project->name} has been approved.",
        ];

        if ($blockedTasksCount > 0) {
            $bodyParts[] = "{$blockedTasksCount} task(s) have been blocked.";
        }

        if ($heldPOsCount > 0) {
            $bodyParts[] = "{$heldPOsCount} purchase order(s) have been put on hold.";
        }

        $body = implode(' ', $bodyParts);

        foreach ($recipients as $recipient) {
            Notification::make()
                ->title('Change Order Approved - Work Halted')
                ->body($body)
                ->icon('heroicon-o-exclamation-triangle')
                ->danger()
                ->sendToDatabase($recipient);
        }

        Log::info('Notified stakeholders of change order approval', [
            'change_order_id' => $changeOrder->id,
            'recipients_count' => count($recipients),
            'tasks_blocked' => $blockedTasksCount,
            'pos_held' => $heldPOsCount,
        ]);
    }

    /**
     * Notify stakeholders that a change order has been applied
     * and work can resume.
     */
    public function notifyApplied(ChangeOrder $changeOrder): void
    {
        $project = $changeOrder->project;
        $recipients = $this->getStakeholders($project);

        foreach ($recipients as $recipient) {
            Notification::make()
                ->title('Change Order Applied - Work Can Resume')
                ->body("Change order {$changeOrder->change_order_number} for project {$project->name} has been applied. All blocked tasks and held purchase orders have been restored.")
                ->icon('heroicon-o-check-circle')
                ->success()
                ->sendToDatabase($recipient);
        }

        Log::info('Notified stakeholders of change order application', [
            'change_order_id' => $changeOrder->id,
            'recipients_count' => count($recipients),
        ]);
    }

    /**
     * Notify stakeholders that a change order has been cancelled.
     */
    public function notifyCancelled(ChangeOrder $changeOrder, bool $wasApproved = false): void
    {
        $project = $changeOrder->project;
        $recipients = $this->getStakeholders($project);

        $body = "Change order {$changeOrder->change_order_number} for project {$project->name} has been cancelled.";

        if ($wasApproved) {
            $body .= ' All blocked tasks and held purchase orders have been restored.';
        }

        foreach ($recipients as $recipient) {
            Notification::make()
                ->title('Change Order Cancelled')
                ->body($body)
                ->icon('heroicon-o-x-circle')
                ->info()
                ->sendToDatabase($recipient);
        }

        Log::info('Notified stakeholders of change order cancellation', [
            'change_order_id' => $changeOrder->id,
            'recipients_count' => count($recipients),
            'was_approved' => $wasApproved,
        ]);
    }

    /**
     * Get stakeholders to notify for a project.
     *
     * Returns: PM, Designer, Purchasing Manager
     *
     * @return array<User>
     */
    protected function getStakeholders(Project $project): array
    {
        $stakeholders = [];

        // Project Manager
        if ($project->user) {
            $stakeholders[$project->user_id] = $project->user;
        }

        // Designer
        if ($project->designer) {
            $stakeholders[$project->designer_id] = $project->designer;
        }

        // Purchasing Manager
        if ($project->purchasingManager) {
            $stakeholders[$project->purchasing_manager_id] = $project->purchasingManager;
        }

        return array_values($stakeholders);
    }
}
