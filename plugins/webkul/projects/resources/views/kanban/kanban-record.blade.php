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

    // Milestone counts (used as overall progress)
    $milestones = $record->milestones ?? collect();
    $totalMilestones = $milestones->count();
    $completedMilestones = $milestones->where('is_completed', true)->count();
    $milestoneProgress = $totalMilestones > 0 ? round(($completedMilestones / $totalMilestones) * 100) : 0;

    // Chatter unread count
    $unreadCount = method_exists($record, 'unRead') ? $record->unRead()->count() : 0;

    // Card settings (from page)
    $settings = $this->cardSettings ?? [];
    $showCustomer = $settings['show_customer'] ?? true;
    $showDays = $settings['show_days'] ?? true;
    $showLinearFeet = $settings['show_linear_feet'] ?? true;
    $showMilestones = $settings['show_milestones'] ?? true;
    $compactMode = $settings['compact_mode'] ?? false;

    // Determine urgency color for top bar
    // Red = overdue, Orange = high priority/due soon, Stage color = normal
    $urgencyColor = $stageColor;
    if ($isOverdue) {
        $urgencyColor = '#ef4444'; // red-500
    } elseif ($hasBlockers) {
        $urgencyColor = '#9333ea'; // purple-600 for blocked
    } elseif ($priority === 'high') {
        $urgencyColor = '#f97316'; // orange-500
    }
@endphp

<div
    id="{{ $record->getKey() }}"
    class="group bg-white dark:bg-gray-800 rounded-lg shadow-sm hover:shadow-md
           transition-all duration-150 cursor-grab active:cursor-grabbing
           border border-gray-200 dark:border-gray-700 overflow-hidden"
    @if($record->timestamps && now()->diffInSeconds($record->{$record::UPDATED_AT}, true) < 3)
        x-data
        x-init="
            $el.classList.add('ring-2', 'ring-primary-400')
            setTimeout(() => $el.classList.remove('ring-2', 'ring-primary-400'), 2000)
        "
    @endif
>
    {{-- Urgency color bar at top - instant visual priority --}}
    <div class="h-1.5" style="background-color: {{ $urgencyColor }};"></div>

    <div class="{{ $compactMode ? 'p-2 space-y-1' : 'p-3 space-y-2' }}">
        {{-- Title + Actions --}}
        <div class="flex items-start justify-between gap-2">
            <h4
                class="flex-1 font-semibold text-gray-900 dark:text-white {{ $compactMode ? 'text-xs' : 'text-sm' }} leading-tight line-clamp-2 cursor-pointer hover:text-primary-600"
                wire:click="recordClicked('{{ $record->getKey() }}', { id: {{ $record->id }} })"
            >
                {{ $record->name }}
            </h4>

            {{-- Actions on hover only --}}
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

        {{-- Customer - plain text, no icon --}}
        @if($showCustomer && $record->partner)
            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                {{ $record->partner->name }}
            </p>
        @endif

        {{-- Key Metrics - Clean 2-column, NO icons --}}
        <div class="grid grid-cols-2 gap-x-4 gap-y-0.5 text-xs">
            {{-- Timeline --}}
            @if($showDays && $daysLeft !== null)
                <span class="{{ $isOverdue ? 'text-red-600 font-semibold' : 'text-gray-600' }}">
                    @if($isOverdue)
                        {{ abs($daysLeft) }}d late
                    @else
                        {{ $daysLeft }}d left
                    @endif
                </span>
            @else
                <span></span>
            @endif

            {{-- Scope --}}
            @if($showLinearFeet && $linearFeet)
                <span class="text-gray-600 text-right">{{ number_format($linearFeet, 1) }} LF</span>
            @else
                <span></span>
            @endif
        </div>

        {{-- Blocked badge - only if truly blocked --}}
        @if($hasBlockers)
            <span class="inline-flex text-[10px] font-medium text-purple-700 bg-purple-100 px-1.5 py-0.5 rounded" title="{{ implode(', ', $blockers) }}">
                Blocked
            </span>
        @endif

        {{-- Single Progress Bar - milestone-based overall progress --}}
        @if($showMilestones && $totalMilestones > 0 && !$compactMode)
            <div class="flex items-center gap-2 pt-1">
                <div class="flex-1 h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                    <div
                        class="h-full rounded-full transition-all duration-300"
                        style="width: {{ $milestoneProgress }}%; background-color: {{ $stageColor }};"
                    ></div>
                </div>
                <span class="text-xs font-semibold tabular-nums text-gray-600 w-8 text-right">{{ $milestoneProgress }}%</span>
            </div>
        @endif
    </div>
</div>
