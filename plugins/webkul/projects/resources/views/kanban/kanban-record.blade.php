@php
    $stageColor = $status['color'] ?? '#6b7280';

    // Days calculation
    $daysLeft = null;
    $isOverdue = false;
    if ($record->desired_completion_date) {
        $days = now()->diffInDays($record->desired_completion_date, false);
        $daysLeft = (int) $days;
        $isOverdue = $days < 0;
    }

    // Blockers check
    $blockers = $this->getProjectBlockers($record);
    $hasBlockers = !empty($blockers);

    // Priority check
    $priority = $this->getProjectPriority($record);

    // Linear feet
    $linearFeet = $record->estimated_linear_feet;

    // Milestone progress (simple fraction)
    $milestones = $record->milestones ?? collect();
    $totalMilestones = $milestones->count();
    $completedMilestones = $milestones->where('is_completed', true)->count();

    // Chatter unread count
    $unreadCount = method_exists($record, 'unRead') ? $record->unRead()->count() : 0;

    // 3-state border color system
    $isUrgent = $isOverdue || ($daysLeft !== null && $daysLeft <= 7 && $daysLeft >= 0) || $priority === 'high';

    $borderColor = '#e5e7eb'; // Normal = light gray
    $statusBadgeColor = 'gray';
    if ($hasBlockers) {
        $borderColor = '#7c3aed'; // purple - Blocked
        $statusBadgeColor = 'danger';
    } elseif ($isUrgent) {
        $borderColor = '#ea580c'; // orange - Urgent
        $statusBadgeColor = 'warning';
    }
@endphp

<div
    id="{{ $record->getKey() }}"
    wire:click="recordClicked('{{ $record->getKey() }}', { id: {{ $record->id }} })"
    class="group cursor-pointer"
>
    <x-filament::section
        compact
        class="hover:ring-2 hover:ring-primary-500/50 transition-all"
        :attributes="new \Illuminate\View\ComponentAttributeBag(['style' => 'border-left: 4px solid ' . $borderColor . ';'])"
    >
        {{-- Row 1: Title with optional blocker badge --}}
        <div class="flex items-start justify-between gap-2">
            <h4 class="font-semibold text-gray-900 dark:text-white text-sm leading-snug line-clamp-2 flex-1">
                {{ $record->name }}
            </h4>
            @if($hasBlockers)
                <x-filament::badge color="danger" size="sm">
                    Blocked
                </x-filament::badge>
            @elseif($isOverdue)
                <x-filament::badge color="danger" size="sm">
                    Overdue
                </x-filament::badge>
            @elseif($daysLeft !== null && $daysLeft <= 7)
                <x-filament::badge color="warning" size="sm">
                    Due Soon
                </x-filament::badge>
            @endif
        </div>

        {{-- Row 2: Customer (if exists) --}}
        @if($record->partner)
            <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-1.5 flex items-center gap-1">
                <x-filament::icon
                    icon="heroicon-m-user"
                    class="h-3 w-3 text-gray-400"
                />
                {{ $record->partner->name }}
            </p>
        @endif

        {{-- Row 3: Metadata line --}}
        <div class="flex items-center justify-between mt-3 text-xs">
            {{-- Left: Due date --}}
            <div class="flex items-center gap-1 text-gray-500">
                @if($record->desired_completion_date)
                    <x-filament::icon
                        icon="heroicon-m-calendar"
                        class="h-3.5 w-3.5 text-gray-400"
                    />
                    <span class="font-medium">{{ $record->desired_completion_date->format('M j') }}</span>
                @endif
            </div>

            {{-- Right: Key metrics --}}
            <div class="flex items-center gap-2 text-gray-500">
                {{-- Linear Feet --}}
                @if($linearFeet)
                    <x-filament::badge color="gray" size="sm">
                        {{ number_format($linearFeet, 0) }} LF
                    </x-filament::badge>
                @endif

                {{-- Days indicator --}}
                @if($daysLeft !== null)
                    @if($isOverdue)
                        <x-filament::badge color="danger" size="sm">
                            {{ abs($daysLeft) }}d late
                        </x-filament::badge>
                    @elseif($daysLeft <= 7)
                        <x-filament::badge color="warning" size="sm">
                            {{ $daysLeft }}d
                        </x-filament::badge>
                    @else
                        <span class="font-medium text-gray-500">{{ $daysLeft }}d</span>
                    @endif
                @endif

                {{-- Progress fraction --}}
                @if($totalMilestones > 0)
                    <x-filament::badge color="info" size="sm">
                        {{ $completedMilestones }}/{{ $totalMilestones }}
                    </x-filament::badge>
                @endif
            </div>
        </div>

        {{-- Hover actions bar --}}
        <div class="flex items-center justify-end gap-1 mt-3 pt-2.5 border-t border-gray-100 dark:border-gray-700
                    opacity-0 group-hover:opacity-100 transition-opacity">
            <x-filament::icon-button
                wire:click.stop="openChatter('{{ $record->getKey() }}')"
                icon="heroicon-m-chat-bubble-left-right"
                color="gray"
                size="sm"
                label="Open Chatter"
            >
                @if($unreadCount > 0)
                    <x-slot name="badge">
                        {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                    </x-slot>
                @endif
            </x-filament::icon-button>

            <x-filament::icon-button
                tag="a"
                href="{{ route('filament.admin.resources.project.projects.view', $record) }}"
                wire:click.stop
                icon="heroicon-m-arrow-top-right-on-square"
                color="gray"
                size="sm"
                label="View Details"
            />
        </div>
    </x-filament::section>
</div>
