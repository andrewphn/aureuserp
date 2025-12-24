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
    if ($hasBlockers) {
        $borderColor = '#7c3aed'; // purple - Blocked
    } elseif ($isUrgent) {
        $borderColor = '#ea580c'; // orange - Urgent
    }
@endphp

<div
    id="{{ $record->getKey() }}"
    wire:click="recordClicked('{{ $record->getKey() }}', { id: {{ $record->id }} })"
    class="group bg-white dark:bg-gray-800 rounded-lg cursor-pointer
           hover:shadow-md transition-shadow duration-150"
    style="border-left: 4px solid {{ $borderColor }}; border-top: 1px solid #e5e7eb; border-right: 1px solid #e5e7eb; border-bottom: 1px solid #e5e7eb;"
>
    <div class="px-3.5 py-3">
        {{-- Row 1: Title --}}
        <h4 class="font-semibold text-gray-900 dark:text-white text-sm leading-snug line-clamp-2">
            {{ $record->name }}
        </h4>

        {{-- Row 2: Customer (if exists) --}}
        @if($record->partner)
            <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-1.5">
                {{ $record->partner->name }}
            </p>
        @endif

        {{-- Row 3: Metadata line --}}
        <div class="flex items-center justify-between mt-3 text-xs">
            {{-- Left: Due date --}}
            <div class="text-gray-500 font-medium">
                @if($record->desired_completion_date)
                    {{ $record->desired_completion_date->format('M j') }}
                @endif
            </div>

            {{-- Right: Key metrics --}}
            <div class="flex items-center gap-2.5 text-gray-500">
                {{-- Linear Feet --}}
                @if($linearFeet)
                    <span class="font-medium">{{ number_format($linearFeet, 0) }} LF</span>
                @endif

                {{-- Days indicator --}}
                @if($daysLeft !== null)
                    @if($isOverdue)
                        <span class="font-bold" style="color: #dc2626;">{{ abs($daysLeft) }}d late</span>
                    @elseif($daysLeft <= 7)
                        <span class="font-bold" style="color: #ea580c;">{{ $daysLeft }}d</span>
                    @else
                        <span class="font-medium">{{ $daysLeft }}d</span>
                    @endif
                @endif

                {{-- Progress fraction --}}
                @if($totalMilestones > 0)
                    <span style="color: {{ $stageColor }};" class="font-bold">{{ $completedMilestones }}/{{ $totalMilestones }}</span>
                @endif
            </div>
        </div>

        {{-- Hover actions bar --}}
        <div class="flex items-center justify-end gap-1.5 mt-3 pt-2.5 border-t border-gray-100 dark:border-gray-700
                    opacity-0 group-hover:opacity-100 transition-opacity -mb-0.5">
            <button
                wire:click.stop="openChatter('{{ $record->getKey() }}')"
                class="relative p-1 text-gray-400 hover:text-primary-500 rounded"
                title="Chatter"
            >
                <x-heroicon-m-chat-bubble-left-right class="w-4 h-4" />
                @if($unreadCount > 0)
                    <span class="absolute -top-1 -right-1 bg-red-500 text-white text-[9px] font-bold rounded-full w-4 h-4 flex items-center justify-center">
                        {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                    </span>
                @endif
            </button>
            <a
                href="{{ route('filament.admin.resources.project.projects.view', $record) }}"
                wire:click.stop
                class="p-1 text-gray-400 hover:text-primary-500 rounded"
                title="View Details"
            >
                <x-heroicon-m-arrow-top-right-on-square class="w-4 h-4" />
            </a>
        </div>
    </div>
</div>
