@props(['status'])

@php
    $color = $status['color'] ?? '#6b7280';
    $hasProjects = count($status['records'] ?? []) > 0;
    $isCollapsed = $status['is_collapsed'] ?? false;
@endphp

<div
    x-data="{ collapsed: {{ $isCollapsed ? 'true' : 'false' }} }"
    class="flex-shrink-0 flex-grow-0 flex flex-col h-full"
    x-bind:style="collapsed ? 'width: 40px; min-width: 40px; max-width: 40px;' : 'width: 280px; min-width: 280px; max-width: 280px;'"
>
    {{-- Solid Color Header (Monday.com style) --}}
    @include(static::$headerView)

    {{-- Card Container - Full height with subtle background --}}
    <div
        x-show="!collapsed"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        data-status-id="{{ $status['id'] }}"
        class="flex-1 flex flex-col gap-2 p-2 overflow-y-auto bg-gray-100/70 dark:bg-gray-800/50 rounded-b-lg border border-t-0 border-gray-200 dark:border-gray-700"
    >
        @forelse($status['records'] as $record)
            @include(static::$recordView)
        @empty
            {{-- Empty State - Minimal --}}
            <div class="flex-1 flex flex-col items-center justify-center text-gray-400 dark:text-gray-500 py-8">
                <x-heroicon-o-inbox class="w-8 h-8 mb-2 opacity-40" />
                <p class="text-xs">No projects</p>
            </div>
        @endforelse
    </div>

    {{-- Collapsed State --}}
    <div
        x-show="collapsed"
        x-transition
        class="flex-1 flex flex-col items-center py-4"
    >
        <span
            class="text-xs font-medium text-gray-500 dark:text-gray-400"
            style="writing-mode: vertical-rl; text-orientation: mixed;"
        >
            {{ $status['title'] }} ({{ count($status['records'] ?? []) }})
        </span>
    </div>
</div>
