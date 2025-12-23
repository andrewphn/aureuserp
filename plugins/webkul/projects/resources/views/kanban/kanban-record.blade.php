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

    // Order value
    $orderValue = $record->orders->first()?->total ?? null;

    // Task counts
    $tasks = $record->tasks ?? collect();
    $taskCount = $tasks->count();
    $completedTasks = $tasks->where('state', 'done')->count();

    // Milestone counts
    $milestones = $record->milestones ?? collect();
    $totalMilestones = $milestones->count();
    $completedMilestones = $milestones->where('is_completed', true)->count();
    $milestoneProgress = $totalMilestones > 0 ? round(($completedMilestones / $totalMilestones) * 100) : 0;

    // Chatter unread count
    $unreadCount = method_exists($record, 'unRead') ? $record->unRead()->count() : 0;

    // Card settings (from page)
    $settings = $this->cardSettings ?? [];
    $showBadges = $settings['show_badges'] ?? true;
    $showCustomer = $settings['show_customer'] ?? true;
    $showValue = $settings['show_value'] ?? true;
    $showDays = $settings['show_days'] ?? true;
    $showLinearFeet = $settings['show_linear_feet'] ?? true;
    $showMilestones = $settings['show_milestones'] ?? true;
    $showTasks = $settings['show_tasks'] ?? true;
    $compactMode = $settings['compact_mode'] ?? false;
@endphp

<div
    id="{{ $record->getKey() }}"
    class="group bg-white dark:bg-gray-800 rounded-lg shadow-sm hover:shadow-md
           transition-all duration-150 cursor-grab active:cursor-grabbing
           border border-gray-200 dark:border-gray-700"
    style="border-left: 3px solid {{ $stageColor }};"
    @if($record->timestamps && now()->diffInSeconds($record->{$record::UPDATED_AT}, true) < 3)
        x-data
        x-init="
            $el.classList.add('ring-2', 'ring-primary-400')
            setTimeout(() => $el.classList.remove('ring-2', 'ring-primary-400'), 2000)
        "
    @endif
>
    <div class="{{ $compactMode ? 'p-2 space-y-1' : 'p-3 space-y-2' }}">
        {{-- Header: Title + Actions --}}
        <div class="flex items-start justify-between gap-2">
            <div class="flex-1 min-w-0">
                <h4
                    class="font-medium text-gray-900 dark:text-white {{ $compactMode ? 'text-xs' : 'text-sm' }} leading-tight line-clamp-2 cursor-pointer hover:text-primary-600 dark:hover:text-primary-400"
                    wire:click="recordClicked('{{ $record->getKey() }}', { id: {{ $record->id }} })"
                >
                    {{ $record->name }}
                </h4>
                @if($record->display_identifier)
                    <span class="text-[10px] text-gray-400 dark:text-gray-500">{{ $record->display_identifier }}</span>
                @endif
            </div>

            {{-- Actions (visible on hover) --}}
            <div class="flex items-center gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
                <button
                    wire:click.stop="openChatter('{{ $record->getKey() }}')"
                    class="relative p-1 text-gray-400 hover:text-primary-500 hover:bg-gray-100 dark:hover:bg-gray-700 rounded"
                    title="Chatter"
                >
                    <x-heroicon-m-chat-bubble-left-right class="w-3.5 h-3.5" />
                    @if($unreadCount > 0)
                        <span class="absolute -top-0.5 -right-0.5 bg-red-500 text-white text-[9px] font-bold rounded-full w-3.5 h-3.5 flex items-center justify-center">
                            {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                        </span>
                    @endif
                </button>
                <x-filament::dropdown placement="bottom-end">
                    <x-slot name="trigger">
                        <button class="p-1 text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 rounded">
                            <x-heroicon-m-ellipsis-horizontal class="w-3.5 h-3.5" />
                        </button>
                    </x-slot>
                    <x-filament::dropdown.list>
                        <x-filament::dropdown.list.item
                            wire:click="recordClicked('{{ $record->getKey() }}', { id: {{ $record->id }} })"
                            icon="heroicon-m-pencil-square"
                        >
                            Edit
                        </x-filament::dropdown.list.item>
                        <x-filament::dropdown.list.item
                            href="{{ route('filament.admin.resources.project.projects.view', $record) }}"
                            icon="heroicon-m-eye"
                        >
                            View
                        </x-filament::dropdown.list.item>
                        <x-filament::dropdown.list.item
                            wire:click="openChatter('{{ $record->getKey() }}')"
                            icon="heroicon-m-chat-bubble-left-right"
                        >
                            Chatter
                        </x-filament::dropdown.list.item>
                    </x-filament::dropdown.list>
                </x-filament::dropdown>
            </div>
        </div>

        {{-- Badges Row (compact) --}}
        @if($showBadges && ($isOverdue || $hasBlockers || $priority))
            <div class="flex flex-wrap gap-1">
                @if($isOverdue)
                    <span class="inline-flex items-center gap-0.5 text-[10px] font-semibold text-white bg-red-500 px-1.5 py-0.5 rounded">
                        <x-heroicon-m-clock class="w-3 h-3" />
                        {{ abs($daysLeft) }}d late
                    </span>
                @endif
                @if($hasBlockers)
                    <span class="inline-flex items-center gap-0.5 text-[10px] font-semibold text-white bg-purple-600 px-1.5 py-0.5 rounded" title="{{ implode(', ', $blockers) }}">
                        <x-heroicon-m-pause-circle class="w-3 h-3" />
                        Blocked
                    </span>
                @endif
                @if($priority === 'high')
                    <span class="inline-flex items-center gap-0.5 text-[10px] font-semibold text-white bg-orange-500 px-1.5 py-0.5 rounded">
                        <x-heroicon-m-fire class="w-3 h-3" />
                        Urgent
                    </span>
                @elseif($priority === 'medium')
                    <span class="text-[10px] font-semibold text-white bg-amber-500 px-1.5 py-0.5 rounded">
                        Priority
                    </span>
                @endif
            </div>
        @endif

        {{-- Customer --}}
        @if($showCustomer && $record->partner)
            <div class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                <x-heroicon-m-building-office class="w-3.5 h-3.5 shrink-0" />
                <span class="truncate">{{ $record->partner->name }}</span>
            </div>
        @endif

        {{-- Inline Metrics Row --}}
        @php
            $hasMetrics = ($showDays && $daysLeft !== null && !$isOverdue)
                || ($showLinearFeet && $linearFeet)
                || ($showValue && $orderValue)
                || ($showTasks && $taskCount > 0);
        @endphp
        @if($hasMetrics)
            <div class="flex items-center gap-3 text-[11px] text-gray-500 dark:text-gray-400">
                @if($showDays && $daysLeft !== null && !$isOverdue)
                    <span class="flex items-center gap-1" title="Days until due">
                        <x-heroicon-m-calendar class="w-3 h-3" />
                        {{ $daysLeft }}d
                    </span>
                @endif
                @if($showLinearFeet && $linearFeet)
                    <span class="flex items-center gap-1" title="Linear Feet">
                        <x-heroicon-m-arrows-right-left class="w-3 h-3" />
                        {{ $linearFeet }} LF
                    </span>
                @endif
                @if($showValue && $orderValue)
                    <span class="flex items-center gap-1 text-green-600 dark:text-green-400" title="Order Value">
                        <x-heroicon-m-currency-dollar class="w-3 h-3" />
                        {{ number_format($orderValue / 1000, 1) }}k
                    </span>
                @endif
                @if($showTasks && $taskCount > 0)
                    <span class="flex items-center gap-1" title="Tasks">
                        <x-heroicon-m-queue-list class="w-3 h-3" />
                        {{ $completedTasks }}/{{ $taskCount }}
                    </span>
                @endif
            </div>
        @endif

        {{-- Milestone Progress --}}
        @if($showMilestones && $totalMilestones > 0 && !$compactMode)
            <div class="space-y-1">
                <div class="flex justify-between text-[10px] text-gray-500 dark:text-gray-400">
                    <span>{{ $completedMilestones }}/{{ $totalMilestones }} milestones</span>
                    <span style="color: {{ $stageColor }};">{{ $milestoneProgress }}%</span>
                </div>
                <div class="h-1 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                    <div
                        class="h-full rounded-full transition-all duration-300"
                        style="width: {{ $milestoneProgress }}%; background-color: {{ $stageColor }};"
                    ></div>
                </div>
            </div>
        @endif
    </div>
</div>
