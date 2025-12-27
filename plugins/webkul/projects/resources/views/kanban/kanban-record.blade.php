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

    // Calculate progress percentage (0-100)
    $progressPercent = $totalMilestones > 0
        ? round(($completedMilestones / $totalMilestones) * 100)
        : 0;

    // Chatter unread count
    $unreadCount = method_exists($record, 'unRead') ? $record->unRead()->count() : 0;

    // Status-based styling
    $isUrgent = $isOverdue || ($daysLeft !== null && $daysLeft <= 7 && $daysLeft >= 0) || $priority === 'high';

    // Progress bar color based on status (using Tailwind/Filament colors)
    $progressColorClass = 'bg-success-500'; // Green - on track
    $progressBgClass = 'bg-success-100 dark:bg-success-900/20';
    $statusLabel = null;

    if ($hasBlockers) {
        $progressColorClass = 'bg-purple-500';
        $progressBgClass = 'bg-purple-100 dark:bg-purple-900/20';
        $statusLabel = 'Blocked';
    } elseif ($isOverdue) {
        $progressColorClass = 'bg-danger-500';
        $progressBgClass = 'bg-danger-100 dark:bg-danger-900/20';
        $statusLabel = 'Overdue';
    } elseif ($daysLeft !== null && $daysLeft <= 7 && $daysLeft >= 0) {
        $progressColorClass = 'bg-warning-500';
        $progressBgClass = 'bg-warning-100 dark:bg-warning-900/20';
        $statusLabel = 'Due Soon';
    }
@endphp

<div
    id="{{ $record->getKey() }}"
    x-data="{ showMenu: false, menuX: 0, menuY: 0 }"
    @contextmenu.prevent.stop="showMenu = true; menuX = $event.clientX; menuY = $event.clientY"
    @click.away="showMenu = false"
    @keydown.escape.window="showMenu = false"
    wire:click="openQuickActions('{{ $record->getKey() }}')"
    class="group cursor-pointer relative"
>
    {{-- Right-Click Context Menu --}}
    <div
        x-show="showMenu"
        x-cloak
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        :style="`position: fixed; top: ${menuY}px; left: ${menuX}px; z-index: 9999;`"
        class="bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 py-1 min-w-[200px]"
    >
        {{-- Quick Actions --}}
        <button
            @click.stop="showMenu = false; $wire.openQuickActions('{{ $record->getKey() }}')"
            class="w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-3 text-sm text-gray-700 dark:text-gray-200"
        >
            <x-heroicon-m-bolt class="h-4 w-4 text-primary-500" />
            Quick Actions
        </button>

        {{-- Edit Project --}}
        <a
            href="{{ \Webkul\Project\Filament\Resources\ProjectResource::getUrl('edit', ['record' => $record]) }}"
            @click.stop="showMenu = false"
            class="w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-3 text-sm text-gray-700 dark:text-gray-200"
        >
            <x-heroicon-m-pencil-square class="h-4 w-4 text-primary-500" />
            Edit Project
        </a>

        {{-- Messages --}}
        <button
            @click.stop="showMenu = false; $wire.openChatter('{{ $record->getKey() }}')"
            class="w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-3 text-sm text-gray-700 dark:text-gray-200"
        >
            <x-heroicon-m-chat-bubble-left-right class="h-4 w-4 text-primary-500" />
            Messages
            @if($unreadCount > 0)
                <span class="ml-auto bg-primary-500 text-white text-xs rounded-full px-1.5 py-0.5">{{ $unreadCount }}</span>
            @endif
        </button>

        {{-- View Full Page --}}
        <a
            href="{{ route('filament.admin.resources.project.projects.view', $record) }}"
            @click.stop="showMenu = false"
            class="w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-3 text-sm text-gray-700 dark:text-gray-200"
        >
            <x-heroicon-m-arrow-top-right-on-square class="h-4 w-4 text-primary-500" />
            View Full Page
        </a>

        {{-- Divider --}}
        <hr class="my-1 border-gray-200 dark:border-gray-700" />

        {{-- Mark Blocked / Unblock --}}
        <button
            @click.stop="showMenu = false; $wire.toggleProjectBlocked('{{ $record->getKey() }}')"
            class="w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-3 text-sm {{ $hasBlockers ? 'text-success-600' : 'text-purple-600' }}"
        >
            @if($hasBlockers)
                <x-heroicon-m-check-circle class="h-4 w-4" />
                Unblock Project
            @else
                <x-heroicon-m-no-symbol class="h-4 w-4" />
                Mark as Blocked
            @endif
        </button>
    </div>

    <x-filament::section
        compact
        class="hover:ring-2 hover:ring-primary-500/50 transition-all overflow-hidden !p-0"
    >
        {{-- Card Content --}}
        <div class="px-3.5 py-3">
            {{-- Row 1: Title with optional status badge --}}
            <div class="flex items-start justify-between gap-2">
                <h4 class="font-semibold text-gray-900 dark:text-white text-sm leading-snug line-clamp-2 flex-1">
                    {{ $record->name }}
                </h4>
                @if($statusLabel)
                    <x-filament::badge
                        :color="$hasBlockers ? 'gray' : ($isOverdue ? 'danger' : 'warning')"
                        size="sm"
                        @class([
                            '!bg-purple-100 !text-purple-700 dark:!bg-purple-900/30 dark:!text-purple-300' => $hasBlockers,
                        ])
                    >
                        {{ $statusLabel }}
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
                        <span class="font-medium">{{ number_format($linearFeet, 0) }} LF</span>
                    @endif

                    {{-- Days indicator --}}
                    @if($daysLeft !== null && !$statusLabel)
                        <span class="font-medium">{{ $daysLeft }}d</span>
                    @elseif($isOverdue)
                        <span class="font-bold" style="color: #dc2626;">{{ abs($daysLeft) }}d late</span>
                    @endif
                </div>
            </div>

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

            {{-- Hover actions bar --}}
            <div class="flex items-center justify-end gap-1 mt-3 pt-2.5 border-t border-gray-100 dark:border-gray-700
                        opacity-0 group-hover:opacity-100 transition-opacity">
                <x-filament::icon-button
                    wire:click.stop="openChatter('{{ $record->getKey() }}')"
                    icon="heroicon-m-chat-bubble-left-right"
                    color="gray"
                    size="sm"
                    label="Messages"
                />

                <x-filament::icon-button
                    tag="a"
                    href="{{ \Webkul\Project\Filament\Resources\ProjectResource::getUrl('edit', ['record' => $record]) }}"
                    wire:click.stop
                    icon="heroicon-m-pencil-square"
                    color="gray"
                    size="sm"
                    label="Edit Project"
                />

                <x-filament::icon-button
                    tag="a"
                    href="{{ route('filament.admin.resources.project.projects.view', $record) }}"
                    wire:click.stop
                    icon="heroicon-m-arrow-top-right-on-square"
                    color="gray"
                    size="sm"
                    label="Full Page"
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
                    @if($totalMilestones > 0)
                        {{ $completedMilestones }}/{{ $totalMilestones }} milestones
                    @else
                        No milestones
                    @endif
                </span>
                <span class="text-[10px] font-bold text-gray-700 dark:text-gray-200 relative z-10">
                    {{ $progressPercent }}%
                </span>
            </div>
        </div>
    </x-filament::section>
</div>
