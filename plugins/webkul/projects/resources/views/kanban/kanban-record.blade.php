@php
    $stageColor = $status['color'] ?? '#6b7280';

    // Card settings (from Customize View modal)
    $showCustomer = $cardSettings['show_customer'] ?? true;
    $showDays = $cardSettings['show_days'] ?? true;
    $showLinearFeet = $cardSettings['show_linear_feet'] ?? true;
    $showMilestones = $cardSettings['show_milestones'] ?? true;
    $compactMode = $cardSettings['compact_mode'] ?? false;

    // Days calculation
    $daysLeft = null;
    $isOverdue = false;
    if ($record->desired_completion_date) {
        $days = now()->diffInDays($record->desired_completion_date, false);
        $daysLeft = (int) $days;
        $isOverdue = $days < 0;
    }

    // Stage expiry calculation
    $daysInStage = null;
    $isStageExpiring = false;
    $stageExpiryDaysLeft = null;
    $maxDaysInStage = $status['max_days_in_stage'] ?? null;
    $expiryWarningDays = $status['expiry_warning_days'] ?? 3;

    if ($maxDaysInStage && $record->stage_entered_at) {
        $daysInStage = (int) now()->diffInDays($record->stage_entered_at);
        $stageExpiryDaysLeft = $maxDaysInStage - $daysInStage;
        $isStageExpiring = $daysInStage >= ($maxDaysInStage - $expiryWarningDays);
    }

    // Blockers check
    $blockers = $this->getProjectBlockers($record);
    $hasBlockers = !empty($blockers);

    // Priority check
    $priority = $this->getProjectPriority($record);

    // Linear feet
    $linearFeet = $record->estimated_linear_feet;

    // Milestone progress
    $milestones = $record->milestones ?? collect();
    $totalMilestones = $milestones->count();
    $completedMilestones = $milestones->where('is_completed', true)->count();
    $progressPercent = $totalMilestones > 0 ? round(($completedMilestones / $totalMilestones) * 100) : 0;
    $progressLabel = $totalMilestones > 0 ? "{$completedMilestones}/{$totalMilestones} milestones" : 'No milestones';

    // Chatter unread count
    $unreadCount = method_exists($record, 'unRead') ? $record->unRead()->count() : 0;

    // Status-based styling
    $isUrgent = $isOverdue || ($daysLeft !== null && $daysLeft <= 7 && $daysLeft >= 0) || $priority === 'high';

    // Determine status for components
    $statusType = null;
    if ($hasBlockers) {
        $statusType = 'blocked';
    } elseif ($isOverdue) {
        $statusType = 'overdue';
    } elseif ($daysLeft !== null && $daysLeft <= 7 && $daysLeft >= 0) {
        $statusType = 'due_soon';
    }

    // URLs for actions
    $editUrl = \Webkul\Project\Filament\Resources\ProjectResource::getUrl('edit', ['record' => $record]);
    $viewUrl = route('filament.admin.resources.project.projects.view', $record);
@endphp

<div
    id="{{ $record->getKey() }}"
    data-card-id="{{ $record->getKey() }}"
    data-due-date="{{ $record->desired_completion_date?->format('Y-m-d') ?? '' }}"
    data-linear-feet="{{ $record->estimated_linear_feet ?? 0 }}"
    data-days-left="{{ $daysLeft ?? 9999 }}"
    data-sort-order="{{ $loop->index ?? 0 }}"
    x-data="{ showMenu: false, menuX: 0, menuY: 0 }"
    @contextmenu.prevent.stop="showMenu = true; menuX = $event.clientX; menuY = $event.clientY"
    @click.away="showMenu = false"
    @keydown.escape.window="showMenu = false"
    @click="if (handleCardClick('{{ $record->getKey() }}', $event)) { $wire.openQuickActions('{{ $record->getKey() }}') }"
    :style="isSelected('{{ $record->getKey() }}') ? 'outline: 4px solid #3b82f6; outline-offset: 2px; box-shadow: 0 0 0 8px rgba(59, 130, 246, 0.2);' : ''"
    class="group cursor-pointer relative rounded-lg transition-all"
>
    {{-- Right-Click Context Menu (Component) --}}
    @include('webkul-project::kanban.components.context-menu', [
        'record' => $record,
        'hasBlockers' => $hasBlockers,
        'unreadCount' => $unreadCount,
        'editUrl' => $editUrl,
        'viewUrl' => $viewUrl,
        'type' => 'project',
    ])

    <x-filament::section
        compact
        class="hover:ring-2 hover:ring-primary-500/50 transition-all overflow-hidden !p-0"
    >
        {{-- Card Content --}}
        <div class="{{ $compactMode ? 'px-2.5 py-2' : 'px-3.5 py-3' }}">
            {{-- Row 1: Title with optional status badge --}}
            <div class="flex items-start justify-between gap-2">
                <h4 class="font-semibold text-gray-900 dark:text-white leading-snug flex-1 {{ $compactMode ? 'text-xs line-clamp-1' : 'text-sm line-clamp-2' }}">
                    {{ $record->name }}
                </h4>
                @include('webkul-project::kanban.components.status-badge', [
                    'status' => $statusType,
                    'isBlocked' => $hasBlockers,
                ])
            </div>

            {{-- Row 2: Customer (if enabled and exists) --}}
            @if($showCustomer && $record->partner)
                <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-1.5 flex items-center gap-1">
                    <x-filament::icon icon="heroicon-m-user" class="h-3 w-3 text-gray-400" />
                    {{ $record->partner->name }}
                </p>
            @endif

            {{-- Row 3: Metadata line (Component) --}}
            @if($showDays || $showLinearFeet)
                @include('webkul-project::kanban.components.card-metadata', [
                    'dueDate' => $showDays ? $record->desired_completion_date : null,
                    'linearFeet' => $showLinearFeet ? $linearFeet : null,
                    'daysLeft' => $showDays ? $daysLeft : null,
                    'isOverdue' => $isOverdue,
                    'statusLabel' => $statusType,
                ])
            @endif

            {{-- Stage Expiry Warning --}}
            @if($isStageExpiring && $stageExpiryDaysLeft !== null)
                <div class="flex items-center gap-1.5 mt-2 px-2 py-1 rounded text-xs font-medium"
                     style="background-color: {{ $stageExpiryDaysLeft <= 0 ? '#fef2f2' : '#fff7ed' }}; color: {{ $stageExpiryDaysLeft <= 0 ? '#dc2626' : '#ea580c' }};">
                    <x-heroicon-m-clock class="h-3.5 w-3.5" />
                    @if($stageExpiryDaysLeft <= 0)
                        {{ abs($stageExpiryDaysLeft) }}d over stage limit
                    @else
                        {{ $stageExpiryDaysLeft }}d left in stage
                    @endif
                </div>
            @endif

            {{-- Hover Actions Bar (Component) --}}
            @include('webkul-project::kanban.components.hover-actions', [
                'record' => $record,
                'editUrl' => $editUrl,
                'viewUrl' => $viewUrl,
                'compactMode' => $compactMode,
            ])
        </div>

        {{-- Progress Bar (Component) --}}
        @if($showMilestones)
            @include('webkul-project::kanban.components.progress-bar', [
                'percent' => $progressPercent,
                'label' => $progressLabel,
                'status' => $statusType ?? 'on_track',
            ])
        @endif
    </x-filament::section>
</div>
