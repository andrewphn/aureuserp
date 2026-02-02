@php
    use Webkul\Project\Enums\TaskState;

    $stageColor = $status['color'] ?? '#6b7280';

    // Days calculation
    $daysLeft = null;
    $isOverdue = false;
    if ($record->deadline) {
        $days = now()->diffInDays($record->deadline, false);
        $daysLeft = (int) $days;
        $isOverdue = $days < 0;
    }

    // State check
    $state = $record->state;
    $isBlocked = $state === TaskState::BLOCKED;
    $isDone = $state === TaskState::DONE;
    $isInProgress = $state === TaskState::IN_PROGRESS;

    // Priority check
    $isPriority = $record->priority;

    // Progress calculation
    $progress = $this->getTaskProgress($record);
    $progressPercent = $progress['percent'];
    $progressLabel = $progress['label'];

    // Hours info
    $allocatedHours = $record->allocated_hours;
    $effectiveHours = $record->effective_hours;

    // Determine status for components
    $statusType = null;
    if ($isBlocked) {
        $statusType = 'blocked';
    } elseif ($isDone) {
        $statusType = 'done';
    } elseif ($isOverdue) {
        $statusType = 'overdue';
    } elseif ($daysLeft !== null && $daysLeft <= 7 && $daysLeft >= 0) {
        $statusType = 'due_soon';
    } elseif ($isInProgress) {
        $statusType = 'in_progress';
    }

    // URLs for actions
    $editUrl = \Webkul\Project\Filament\Resources\TaskResource::getUrl('edit', ['record' => $record]);
@endphp

<div
    id="{{ $record->getKey() }}"
    wire:click="recordClicked('{{ $record->getKey() }}', { id: {{ $record->id }} })"
    class="group cursor-pointer"
>
    <x-filament::section
        compact
        class="hover:ring-2 hover:ring-primary-500/50 transition-all overflow-hidden !p-0"
    >
        {{-- Card Content --}}
        <div class="px-3.5 py-3">
            {{-- Row 1: Title with optional status badge --}}
            <div class="flex items-start justify-between gap-2">
                <h4 class="font-semibold text-gray-900 dark:text-white text-sm leading-snug line-clamp-2 flex-1">
                    @if($isPriority)
                        <x-filament::icon
                            icon="heroicon-s-star"
                            class="h-3.5 w-3.5 text-warning-500 inline mr-0.5"
                        />
                    @endif
                    {{ $record->title }}
                </h4>
                @include('webkul-project::kanban.components.status-badge', [
                    'status' => $statusType,
                    'isBlocked' => $isBlocked,
                ])
            </div>

            {{-- Row 2: Project name (if exists) --}}
            @if($record->project)
                <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-1.5">
                    {{ $record->project->name }}
                </p>
            @endif

            {{-- Row 3: Metadata line (Component) --}}
            @include('webkul-project::kanban.components.card-metadata', [
                'dueDate' => $record->deadline,
                'hours' => ['effective' => $effectiveHours, 'allocated' => $allocatedHours],
                'daysLeft' => $daysLeft,
                'isOverdue' => $isOverdue,
                'statusLabel' => $statusType,
            ])

            {{-- State icon (when no status label shown) --}}
            @if($state && !$statusType)
                <div class="flex items-center justify-end mt-2">
                    <x-filament::icon
                        :icon="$state->getIcon()"
                        @class([
                            'h-4 w-4',
                            'text-info-500' => $state === TaskState::PENDING,
                            'text-gray-500' => $state === TaskState::IN_PROGRESS,
                            'text-warning-500' => $state === TaskState::BLOCKED || $state === TaskState::CHANGE_REQUESTED,
                            'text-success-500' => $state === TaskState::APPROVED || $state === TaskState::DONE,
                            'text-danger-500' => $state === TaskState::CANCELLED,
                        ])
                    />
                </div>
            @endif

            {{-- Assigned users --}}
            @if($record->users->count() > 0)
                <div class="flex items-center gap-1 mt-2 pt-2 border-t border-gray-100 dark:border-gray-700">
                    <div class="flex -space-x-1">
                        @foreach($record->users->take(3) as $user)
                            <div class="w-5 h-5 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center text-[10px] font-medium text-primary-700 dark:text-primary-300 ring-1 ring-white dark:ring-gray-800"
                                 title="{{ $user->name }}">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                        @endforeach
                        @if($record->users->count() > 3)
                            <div class="w-5 h-5 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-[10px] font-medium text-gray-600 dark:text-gray-300 ring-1 ring-white dark:ring-gray-800">
                                +{{ $record->users->count() - 3 }}
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Hover Actions Bar (Component) --}}
            @include('webkul-project::kanban.components.hover-actions', [
                'record' => $record,
                'editUrl' => $editUrl,
            ])
        </div>

        {{-- Progress Bar (Component) --}}
        @include('webkul-project::kanban.components.progress-bar', [
            'percent' => $progressPercent,
            'label' => $progressLabel,
            'status' => $statusType ?? 'on_track',
        ])
    </x-filament::section>
</div>
