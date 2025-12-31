@props(['status'])

@php
    $color = $status['color'] ?? '#6b7280';
    $hasProjects = count($status['records'] ?? []) > 0;
    $isCollapsed = $status['is_collapsed'] ?? false;
    $currentViewMode = $viewMode ?? 'projects';
    $statusId = $status['id'];
@endphp

<div
    x-data="{
        collapsed: {{ $isCollapsed ? 'true' : 'false' }},
        sortBy: 'default',
        sortDir: 'asc',
        sortMenuOpen: false,

        sortOptions: [
            { key: 'default', label: 'Default', icon: '≡' },
            { key: 'name', label: 'Name', icon: 'Aa' },
            { key: 'due_date', label: 'Due Date', icon: '📅' },
            { key: 'linear_feet', label: 'Linear Feet', icon: '📏' },
            { key: 'days_left', label: 'Urgency', icon: '⚡' },
        ],

        get currentSortLabel() {
            const opt = this.sortOptions.find(o => o.key === this.sortBy);
            return opt ? opt.icon : '≡';
        },

        setSort(key) {
            if (this.sortBy === key) {
                this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortBy = key;
                this.sortDir = key === 'days_left' ? 'asc' : 'asc';
            }
            this.sortMenuOpen = false;
            this.applySortToColumn();
        },

        applySortToColumn() {
            const container = this.$el.querySelector('[data-status-id]');
            if (!container) return;

            const cards = Array.from(container.querySelectorAll('[data-card-id]'));
            if (cards.length < 2) return;

            cards.sort((a, b) => {
                let valA, valB;

                switch(this.sortBy) {
                    case 'name':
                        valA = a.querySelector('h4')?.textContent?.trim().toLowerCase() || '';
                        valB = b.querySelector('h4')?.textContent?.trim().toLowerCase() || '';
                        break;
                    case 'due_date':
                        valA = a.dataset.dueDate || '9999-12-31';
                        valB = b.dataset.dueDate || '9999-12-31';
                        break;
                    case 'linear_feet':
                        valA = parseFloat(a.dataset.linearFeet || 0);
                        valB = parseFloat(b.dataset.linearFeet || 0);
                        break;
                    case 'days_left':
                        valA = parseInt(a.dataset.daysLeft ?? 9999);
                        valB = parseInt(b.dataset.daysLeft ?? 9999);
                        break;
                    default:
                        valA = parseInt(a.dataset.sortOrder || 0);
                        valB = parseInt(b.dataset.sortOrder || 0);
                }

                let result = 0;
                if (typeof valA === 'string') {
                    result = valA.localeCompare(valB);
                } else {
                    result = valA - valB;
                }

                return this.sortDir === 'desc' ? -result : result;
            });

            cards.forEach(card => container.appendChild(card));
        }
    }"
    class="flex-shrink-0 flex-grow-0 flex flex-col h-full min-h-0"
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
        class="flex-1 flex flex-col gap-2 p-2 overflow-y-auto min-h-0 bg-gray-100/70 dark:bg-gray-800/50 rounded-b-lg border border-t-0 border-gray-200 dark:border-gray-700"
        style="scrollbar-width: thin; scrollbar-color: transparent transparent;"
        onmouseenter="this.style.scrollbarColor = 'rgba(156,163,175,0.3) transparent'"
        onmouseleave="this.style.scrollbarColor = 'transparent transparent'"
    >
        @forelse($status['records'] as $record)
            @if($currentViewMode === 'tasks')
                @include('webkul-project::kanban.kanban-task-record')
            @else
                @include(static::$recordView)
            @endif
        @empty
            {{-- Empty State - Minimal --}}
            <div class="flex-1 flex flex-col items-center justify-center text-gray-400 dark:text-gray-500 py-8">
                <x-heroicon-o-inbox class="w-8 h-8 mb-2 opacity-40" />
                <p class="text-xs">No {{ $currentViewMode === 'tasks' ? 'tasks' : 'projects' }}</p>
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
