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

    // Status-based styling
    $isUrgent = $isOverdue || ($daysLeft !== null && $daysLeft <= 7 && $daysLeft >= 0) || $isPriority;

    // Progress bar color based on status
    $progressColorClass = 'bg-success-500'; // Green - on track
    $progressBgClass = 'bg-success-100 dark:bg-success-900/20';
    $statusLabel = null;

    if ($isBlocked) {
        $progressColorClass = 'bg-purple-500';
        $progressBgClass = 'bg-purple-100 dark:bg-purple-900/20';
        $statusLabel = 'Blocked';
    } elseif ($isDone) {
        $progressColorClass = 'bg-success-500';
        $progressBgClass = 'bg-success-100 dark:bg-success-900/20';
        $statusLabel = 'Done';
    } elseif ($isOverdue) {
        $progressColorClass = 'bg-danger-500';
        $progressBgClass = 'bg-danger-100 dark:bg-danger-900/20';
        $statusLabel = 'Overdue';
    } elseif ($daysLeft !== null && $daysLeft <= 7 && $daysLeft >= 0) {
        $progressColorClass = 'bg-warning-500';
        $progressBgClass = 'bg-warning-100 dark:bg-warning-900/20';
        $statusLabel = 'Due Soon';
    } elseif ($isInProgress) {
        $progressColorClass = 'bg-info-500';
        $progressBgClass = 'bg-info-100 dark:bg-info-900/20';
    }
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
                @if($statusLabel)
                    <x-filament::badge
                        :color="$isBlocked ? 'gray' : ($isDone ? 'success' : ($isOverdue ? 'danger' : 'warning'))"
                        size="sm"
                        @class([
                            '!bg-purple-100 !text-purple-700 dark:!bg-purple-900/30 dark:!text-purple-300' => $isBlocked,
                        ])
                    >
                        {{ $statusLabel }}
                    </x-filament::badge>
                @endif
            </div>

            {{-- Row 2: Project name (if exists) --}}
            @if($record->project)
                <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-1.5 flex items-center gap-1">
                    <x-filament::icon
                        icon="heroicon-m-folder"
                        class="h-3 w-3 text-gray-400"
                    />
                    {{ $record->project->name }}
                </p>
            @endif

            {{-- Row 3: Metadata line --}}
            <div class="flex items-center justify-between mt-3 text-xs">
                {{-- Left: Due date --}}
                <div class="flex items-center gap-1 text-gray-500">
                    @if($record->deadline)
                        <x-filament::icon
                            icon="heroicon-m-calendar"
                            class="h-3.5 w-3.5 text-gray-400"
                        />
                        <span class="font-medium">{{ $record->deadline->format('M j') }}</span>
                    @endif
                </div>

                {{-- Right: Key metrics --}}
                <div class="flex items-center gap-2 text-gray-500">
                    {{-- Hours --}}
                    @if($allocatedHours)
                        <span class="font-medium">{{ number_format($effectiveHours ?? 0, 1) }}/{{ number_format($allocatedHours, 1) }}h</span>
                    @endif

                    {{-- Days indicator --}}
                    @if($daysLeft !== null && !$statusLabel)
                        <span class="font-medium">{{ $daysLeft }}d</span>
                    @elseif($isOverdue)
                        <span class="font-bold" style="color: #dc2626;">{{ abs($daysLeft) }}d late</span>
                    @endif

                    {{-- State icon --}}
                    @if($state && !$statusLabel)
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
                    @endif
                </div>
            </div>

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

            {{-- Hover actions bar --}}
            <div class="flex items-center justify-end gap-1 mt-3 pt-2.5 border-t border-gray-100 dark:border-gray-700
                        opacity-0 group-hover:opacity-100 transition-opacity">
                <x-filament::icon-button
                    wire:click.stop="openChatter('{{ $record->getKey() }}')"
                    icon="heroicon-m-chat-bubble-left-right"
                    color="gray"
                    size="sm"
                    label="Open Chatter"
                />

                <x-filament::icon-button
                    tag="a"
                    href="{{ \Webkul\Project\Filament\Resources\TaskResource::getUrl('edit', ['record' => $record]) }}"
                    wire:click.stop
                    icon="heroicon-m-arrow-top-right-on-square"
                    color="gray"
                    size="sm"
                    label="View Details"
                />
            </div>
        </div>

        {{-- Progress Bar (at bottom of card) --}}
        <div class="relative h-6 {{ $progressBgClass }} border-t border-gray-100 dark:border-gray-700">
            {{-- Filled portion --}}
            <div
                class="absolute inset-y-0 left-0 {{ $progressColorClass }} transition-all duration-300"
                style="width: {{ $progressPercent }}%;"
            ></div>

            {{-- Progress text overlay --}}
            <div class="absolute inset-0 flex items-center justify-between px-3">
                <span class="text-[10px] font-bold text-gray-700 dark:text-gray-200 relative z-10">
                    {{ $progressLabel }}
                </span>
                <span class="text-[10px] font-bold text-gray-700 dark:text-gray-200 relative z-10">
                    {{ $progressPercent }}%
                </span>
            </div>
        </div>
    </x-filament::section>
</div>
