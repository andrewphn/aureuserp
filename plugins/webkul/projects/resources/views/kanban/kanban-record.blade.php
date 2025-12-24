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

    // Border color for card - instant visual status
    // Red = overdue, Purple = blocked, Orange = urgent, Gray = normal
    $borderClass = 'border-gray-200 dark:border-gray-700';
    if ($isOverdue) {
        $borderClass = 'border-l-4 border-l-red-500 border-t-0 border-r-0 border-b-0';
    } elseif ($hasBlockers) {
        $borderClass = 'border-l-4 border-l-purple-500 border-t-0 border-r-0 border-b-0';
    } elseif ($priority === 'high') {
        $borderClass = 'border-l-4 border-l-orange-500 border-t-0 border-r-0 border-b-0';
    }
@endphp

<div
    id="{{ $record->getKey() }}"
    class="group bg-white dark:bg-gray-800 rounded-lg shadow-sm hover:shadow-lg
           transition-all duration-150 cursor-grab active:cursor-grabbing
           border {{ $borderClass }} overflow-hidden"
    @if($record->timestamps && now()->diffInSeconds($record->{$record::UPDATED_AT}, true) < 3)
        x-data
        x-init="
            $el.classList.add('ring-2', 'ring-primary-400')
            setTimeout(() => $el.classList.remove('ring-2', 'ring-primary-400'), 2000)
        "
    @endif
>
    <div class="p-3">
        {{-- Title + Actions --}}
        <div class="flex items-start justify-between gap-2">
            <h4
                class="flex-1 font-bold text-gray-900 dark:text-white text-[13px] leading-snug line-clamp-2 cursor-pointer hover:text-primary-600"
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

        {{-- Customer --}}
        @if($showCustomer && $record->partner)
            <p class="text-[11px] text-gray-500 dark:text-gray-400 truncate mt-1">
                {{ $record->partner->name }}
            </p>
        @endif

        {{-- Divider --}}
        <div class="border-t border-gray-100 dark:border-gray-700 my-2.5"></div>

        {{-- Key Metrics Row --}}
        <div class="flex items-center justify-between text-xs">
            {{-- Timeline with visual emphasis --}}
            @if($showDays && $daysLeft !== null)
                @if($isOverdue)
                    <span class="font-bold px-2 py-0.5 rounded" style="color: #dc2626; background-color: #fef2f2;">
                        {{ abs($daysLeft) }}d late
                    </span>
                @else
                    <span class="font-medium text-gray-600">
                        {{ $daysLeft }}d left
                    </span>
                @endif
            @else
                <span></span>
            @endif

            {{-- Scope --}}
            @if($showLinearFeet && $linearFeet)
                <span class="text-gray-500 font-medium">{{ number_format($linearFeet, 1) }} LF</span>
            @endif
        </div>

        {{-- Blocked badge - prominent warning style --}}
        @if($hasBlockers)
            <div class="mt-2.5">
                <span class="inline-flex items-center gap-1.5 text-[10px] font-bold px-2 py-1 rounded" style="color: #92400e; background-color: #fef3c7;" title="{{ implode(', ', $blockers) }}">
                    <x-heroicon-m-exclamation-triangle class="w-3.5 h-3.5" />
                    Blocked
                </span>
            </div>
        @endif

        {{-- Progress Bar - hero element --}}
        @if($showMilestones && $totalMilestones > 0 && !$compactMode)
            <div class="mt-3 pt-3 border-t border-gray-200">
                <div class="flex items-center gap-3">
                    <div class="flex-1 h-3 rounded-full overflow-hidden shadow-inner" style="background-color: #9ca3af;">
                        <div
                            class="h-full rounded-full"
                            style="width: {{ max($milestoneProgress, 8) }}%; background-color: {{ $stageColor }};"
                        ></div>
                    </div>
                    <span class="text-sm font-bold tabular-nums shrink-0" style="color: {{ $stageColor }};">{{ $milestoneProgress }}%</span>
                </div>
            </div>
        @endif
    </div>
</div>
