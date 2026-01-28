<div class="gantt-chart-wrapper">
    {{-- Controls Bar - TCS Clean Design --}}
    <div class="flex flex-wrap items-center gap-4 mb-6 p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
        {{-- View Mode Buttons --}}
        <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-900 rounded-lg p-1">
            @foreach(['Day', 'Week', 'Month', 'Quarter', 'Year'] as $mode)
                <button
                    wire:click="setViewMode('{{ $mode }}')"
                    class="px-3 py-1.5 text-sm rounded-lg transition-all duration-200
                        {{ $viewMode === $mode
                            ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm font-medium'
                            : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-50 dark:hover:bg-gray-800'
                        }}"
                >
                    {{ $mode }}
                </button>
            @endforeach
        </div>

        {{-- Today Button --}}
        <button
            x-data
            x-on:click="$dispatch('gantt-scroll-to-today')"
            class="px-4 py-1.5 text-sm rounded-lg border border-gray-200 dark:border-gray-600 transition-all duration-200 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:border-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 flex items-center gap-2 group"
        >
            <span class="w-2 h-2 rounded-full bg-blue-500 group-hover:animate-pulse"></span>
            Today
            <span class="text-xs text-gray-400 dark:text-gray-500">(T)</span>
        </button>

        {{-- Export Button --}}
        <button
            x-data
            x-on:click="$dispatch('gantt-export')"
            class="px-4 py-1.5 text-sm rounded-lg border border-gray-200 dark:border-gray-600 transition-all duration-200 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:border-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 flex items-center gap-2 group"
        >
            <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
            Export
            <span class="text-xs text-gray-400 dark:text-gray-500">(E)</span>
        </button>

        {{-- Print Button --}}
        <button
            x-data
            x-on:click="window.print()"
            class="px-4 py-1.5 text-sm rounded-lg border border-gray-200 dark:border-gray-600 transition-all duration-200 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:border-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/20 flex items-center gap-2 group"
        >
            <x-heroicon-o-printer class="w-4 h-4" />
            Print
            <span class="text-xs text-gray-400 dark:text-gray-500">(P)</span>
        </button>

        {{-- Keyboard Shortcuts Help --}}
        <button
            x-data="{ showHelp: false }"
            x-on:click="showHelp = !showHelp"
            class="px-4 py-1.5 text-sm rounded-lg border border-gray-200 dark:border-gray-600 transition-all duration-200 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:border-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/20 flex items-center gap-2 group relative"
        >
            <x-heroicon-o-question-mark-circle class="w-4 h-4" />
            <span class="text-xs text-gray-400 dark:text-gray-500">(?)</span>

            {{-- Help Tooltip --}}
            <div
                x-show="showHelp"
                x-on:click.away="showHelp = false"
                x-transition
                class="absolute top-full right-0 mt-2 w-72 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-xl p-4 z-50"
            >
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Keyboard Shortcuts</h4>
                <div class="space-y-2 text-xs">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Jump to Today</span>
                        <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded">T</kbd>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Export View</span>
                        <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded">E</kbd>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Print View</span>
                        <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded">P</kbd>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Day View</span>
                        <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded">1</kbd>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Week View</span>
                        <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded">2</kbd>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Month View</span>
                        <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded">3</kbd>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Quarter View</span>
                        <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded">4</kbd>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Year View</span>
                        <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded">5</kbd>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Navigate Left</span>
                        <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded">←</kbd>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Navigate Right</span>
                        <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded">→</kbd>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Show Help</span>
                        <kbd class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded">?</kbd>
                    </div>
                </div>
            </div>
        </button>

        {{-- Stage Filter --}}
        <div class="flex items-center gap-2">
            <label for="stageFilter" class="text-sm font-medium text-gray-700 dark:text-gray-300">Stage</label>
            <select
                id="stageFilter"
                wire:model.live="stageFilter"
                class="border-gray-200 dark:border-gray-600 rounded-lg px-3 py-1.5 text-sm bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:border-amber-400 focus:ring-2 focus:ring-amber-400/20 transition-all duration-200"
            >
                <option value="">All Stages</option>
                @foreach($stages as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Date Range --}}
        <div class="flex items-center gap-2">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Range</label>
            <input
                type="date"
                wire:model.live.debounce.500ms="dateRangeStart"
                class="border-gray-200 dark:border-gray-600 rounded-lg px-3 py-1.5 text-sm bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:border-amber-400 focus:ring-2 focus:ring-amber-400/20 transition-all duration-200"
            >
            <span class="text-gray-400">→</span>
            <input
                type="date"
                wire:model.live.debounce.500ms="dateRangeEnd"
                class="border-gray-200 dark:border-gray-600 rounded-lg px-3 py-1.5 text-sm bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:border-amber-400 focus:ring-2 focus:ring-amber-400/20 transition-all duration-200"
            >
        </div>

        {{-- Project Count --}}
        <div class="ml-auto flex items-center gap-2">
            <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ count($projectsData) }}</span>
            <span class="text-sm text-gray-500 dark:text-gray-400">projects</span>
        </div>
    </div>

    {{-- Main Content: Sidebar + Gantt Chart --}}
    <div class="flex gap-0 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden shadow-sm">
        {{-- Left Sidebar - Project List --}}
        <div class="w-80 flex-shrink-0 border-r border-gray-200 dark:border-gray-700">
            {{-- Header --}}
            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
                <div class="grid grid-cols-12 gap-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    <div class="col-span-6">Project</div>
                    <div class="col-span-2 text-center">Value</div>
                    <div class="col-span-2 text-center">LF</div>
                    <div class="col-span-2 text-center">Days</div>
                </div>
            </div>

            {{-- Project Rows --}}
            <div class="divide-y divide-gray-100 dark:divide-gray-700 max-h-[600px] overflow-y-auto">
                @forelse($projectsData as $project)
                    <a
                        href="{{ route('filament.admin.resources.project.projects.view', ['record' => $project['id']]) }}"
                        class="block px-4 py-3 hover:bg-amber-50 dark:hover:bg-amber-900/10 transition-all duration-200 group"
                    >
                        <div class="grid grid-cols-12 gap-2 items-center">
                            {{-- Project Name & Customer --}}
                            <div class="col-span-6">
                                <div class="flex items-center gap-2 mb-1">
                                    <div class="w-2 h-2 rounded-full flex-shrink-0 transition-transform duration-200 group-hover:scale-125" style="background-color: {{ $project['stage_color'] }};"></div>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white truncate group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors duration-200">
                                        {{ Str::limit($project['name'], 25) }}
                                    </span>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 truncate pl-4">
                                    {{ $project['customer'] ?: '—' }}
                                </div>
                            </div>

                            {{-- Value --}}
                            <div class="col-span-2 text-center">
                                @if($project['value'])
                                    <span class="text-sm font-bold text-green-600 dark:text-green-400">
                                        ${{ number_format($project['value'] / 1000, 0) }}k
                                    </span>
                                @else
                                    <span class="text-sm text-gray-400">—</span>
                                @endif
                            </div>

                            {{-- Linear Feet --}}
                            <div class="col-span-2 text-center">
                                @if($project['linear_feet'])
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ number_format($project['linear_feet']) }}
                                    </span>
                                @else
                                    <span class="text-sm text-gray-400">—</span>
                                @endif
                            </div>

                            {{-- Days --}}
                            <div class="col-span-2 text-center">
                                @if($project['days_remaining'] !== null)
                                    <span class="text-sm font-bold {{ $project['is_overdue'] ? 'text-red-500' : ($project['days_remaining'] < 7 ? 'text-orange-500' : 'text-gray-700 dark:text-gray-300') }}">
                                        {{ $project['is_overdue'] ? '-' : '' }}{{ abs($project['days_remaining']) }}d
                                    </span>
                                @else
                                    <span class="text-sm text-gray-400">—</span>
                                @endif
                            </div>
                        </div>

                        {{-- Progress Bar --}}
                        @if($project['milestones_total'] > 0)
                            <div class="mt-2 pl-4">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                        <div
                                            class="h-full rounded-full transition-all duration-300 ease-out"
                                            style="width: {{ $project['progress'] }}%; background-color: {{ $project['stage_color'] }};"
                                        ></div>
                                    </div>
                                    <span class="text-[10px] text-gray-500 dark:text-gray-400 w-10 text-right">
                                        {{ $project['milestones_done'] }}/{{ $project['milestones_total'] }}
                                    </span>
                                </div>
                            </div>
                        @endif
                    </a>
                @empty
                    <div class="px-4 py-12 text-center text-gray-400 dark:text-gray-500">
                        <x-heroicon-o-calendar class="w-10 h-10 mx-auto mb-2 opacity-50" />
                        <p class="text-sm">No projects with dates</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Right: Gantt Chart --}}
        <div
            class="flex-1 min-w-0"
            x-data="ganttChart({
                tasks: @js($tasks),
                viewMode: @js($viewMode)
            })"
            x-on:gantt-tasks-updated.window="refreshTasks($event.detail.tasks)"
            x-on:gantt-view-mode-changed.window="changeViewMode($event.detail.mode)"
            wire:ignore
        >
            @if(count($tasks) > 0)
                <div x-ref="ganttContainer" class="gantt-container"></div>
            @else
                <div class="flex items-center justify-center h-[600px] text-gray-400 dark:text-gray-500">
                    <div class="text-center">
                        <x-heroicon-o-chart-bar-square class="w-16 h-16 mx-auto mb-4 opacity-50" />
                        <p class="text-lg font-medium">No projects to display</p>
                        <p class="text-sm mt-2">Add start and completion dates to see the timeline</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Stage Legend --}}
    <div class="flex flex-wrap items-center gap-4 mt-4 p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Stages:</span>
        @foreach($stageData as $stage)
            <div class="flex items-center gap-2 group">
                <div class="w-4 h-2 rounded transition-transform duration-200 group-hover:scale-110" style="background-color: {{ $stage['color'] }};"></div>
                <span class="text-xs text-gray-600 dark:text-gray-400">{{ $stage['name'] }}</span>
            </div>
        @endforeach
        <div class="flex items-center gap-2 ml-4 pl-4 border-l border-gray-200 dark:border-gray-700">
            <div class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></div>
            <span class="text-xs text-gray-600 dark:text-gray-400">Today</span>
        </div>
    </div>
</div>

@assets
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/frappe-gantt@1.0.0/dist/frappe-gantt.css">
<script src="https://cdn.jsdelivr.net/npm/frappe-gantt@1.0.0/dist/frappe-gantt.umd.js"></script>
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<style>
    /* TCS Gantt Chart Styling - "Don't Make Me Think" UX */
    .gantt-container {
        height: 600px;
        overflow: auto;
    }

    /* Dynamic stage colors - generated from database */
    @foreach($stageData as $stage)
    .gantt .bar-wrapper .bar.stage-{{ $stage['key'] }} .bar-progress { fill: {{ $stage['color'] }}; }
    .gantt .bar-wrapper .bar.stage-{{ $stage['key'] }} .bar { fill: {{ $stage['color'] }}20; stroke: {{ $stage['color'] }}; stroke-width: 1.5; }
    @endforeach

    /* Default stage */
    .gantt .bar-wrapper .bar.stage-default .bar-progress { fill: #6b7280; }
    .gantt .bar-wrapper .bar.stage-default .bar { fill: #6b728020; stroke: #6b7280; }

    /* Milestone styling */
    .gantt .bar-wrapper .bar.milestone .bar { fill: #ef4444; }
    .gantt .bar-wrapper .bar.milestone.critical .bar { fill: #dc2626; }

    /* Clean grid styling */
    .gantt .grid-background { fill: #ffffff; }
    .gantt .grid-header { fill: #f9fafb; stroke: #e5e7eb; }
    .gantt .grid-row { fill: #ffffff; }
    .gantt .grid-row:nth-child(even) { fill: #fafafa; }
    .gantt .row-line { stroke: #f3f4f6; }
    .gantt .tick { stroke: #e5e7eb; }
    .gantt .lower-text, .gantt .upper-text { fill: #6b7280; font-size: 11px; }

    /* Dark mode support */
    .dark .gantt .grid-background { fill: #1f2937; }
    .dark .gantt .grid-header { fill: #111827; stroke: #374151; }
    .dark .gantt .grid-row { fill: #1f2937; }
    .dark .gantt .grid-row:nth-child(even) { fill: #111827; }
    .dark .gantt .row-line { stroke: #374151; }
    .dark .gantt .tick { stroke: #4b5563; }
    .dark .gantt .lower-text, .dark .gantt .upper-text { fill: #9ca3af; }

    /* ============================================
       DRAG & HOVER FEEDBACK - "Don't Make Me Think"
       ============================================ */

    /* Grab cursor on bars - indicates draggable */
    .gantt .bar-wrapper .bar {
        cursor: grab;
        transition: all 0.2s ease-out;
    }

    /* Hover state - visual feedback before interaction */
    .gantt .bar-wrapper:hover .bar {
        filter: brightness(1.1);
        transform: scaleY(1.15);
    }

    .gantt .bar-wrapper:hover .bar-progress {
        filter: brightness(1.05);
    }

    /* Active/Dragging state */
    .gantt .bar-wrapper .bar:active,
    .gantt .bar-wrapper.active .bar {
        cursor: grabbing;
        opacity: 0.8;
        transform: scale(1.02);
        filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.15));
    }

    /* Progress handle - resize cursor */
    .gantt .handle {
        cursor: ew-resize;
        transition: all 0.2s ease-out;
    }

    .gantt .handle:hover,
    .gantt .handle.active {
        fill: #f59e0b !important;
        transform: scale(1.3);
    }

    /* Bar label on hover */
    .gantt .bar-wrapper:hover .bar-label {
        font-weight: 600;
    }

    /* ============================================
       TODAY MARKER - Animated & Eye-catching
       ============================================ */

    .gantt .today-highlight {
        fill: rgba(59, 130, 246, 0.08);
    }

    .gantt .today-line {
        stroke: #3b82f6;
        stroke-width: 2;
        stroke-dasharray: none;
        animation: pulse-today 2s ease-in-out infinite;
    }

    @keyframes pulse-today {
        0%, 100% {
            opacity: 1;
            stroke-width: 2;
        }
        50% {
            opacity: 0.6;
            stroke-width: 3;
        }
    }

    /* Today line glow effect */
    .gantt .today-line-glow {
        stroke: #3b82f6;
        stroke-width: 6;
        stroke-opacity: 0.2;
        filter: blur(2px);
    }

    /* ============================================
       BAR STYLING - Consistent with Kanban
       ============================================ */

    .gantt .bar {
        rx: 6;
        ry: 6;
    }

    .gantt .bar-progress {
        rx: 6;
        ry: 6;
    }

    .gantt .bar-label {
        font-size: 11px;
        font-weight: 500;
        transition: font-weight 0.2s ease-out;
    }

    /* Arrow/dependency lines */
    .gantt .arrow {
        stroke: #9ca3af;
        stroke-width: 1.5;
        transition: stroke 0.2s ease-out;
    }

    .gantt .bar-wrapper:hover ~ .arrow,
    .gantt .arrow:hover {
        stroke: #6b7280;
        stroke-width: 2;
    }

    /* ============================================
       POPUP STYLING - Clean & Informative
       ============================================ */

    .gantt-popup {
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15),
                    0 0 0 1px rgba(0, 0, 0, 0.05);
        padding: 14px 18px;
        min-width: 240px;
        border: none;
        animation: popup-appear 0.2s ease-out;
    }

    @keyframes popup-appear {
        from {
            opacity: 0;
            transform: translateY(-8px) scale(0.95);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .dark .gantt-popup {
        background: #1f2937;
        color: #f3f4f6;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4),
                    0 0 0 1px rgba(255, 255, 255, 0.1);
    }

    .gantt-popup h5 {
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 10px;
        color: #111827;
    }

    .dark .gantt-popup h5 {
        color: #f9fafb;
    }

    .gantt-popup p {
        font-size: 12px;
        margin: 6px 0;
        color: #6b7280;
    }

    .dark .gantt-popup p {
        color: #9ca3af;
    }

    .gantt-popup .popup-action {
        font-size: 11px;
        color: #d4a574;
        margin-top: 10px;
        cursor: pointer;
        transition: color 0.2s ease-out;
    }

    .gantt-popup .popup-action:hover {
        color: #b8935e;
    }

    /* ============================================
       LOADING STATE
       ============================================ */

    .gantt-loading {
        position: absolute;
        inset: 0;
        background: rgba(255, 255, 255, 0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 100;
    }

    .dark .gantt-loading {
        background: rgba(17, 24, 39, 0.8);
    }

    /* ============================================
       PRINT STYLES - Optimized for Landscape
       ============================================ */

    @media print {
        @page {
            size: landscape;
            margin: 0.5in;
        }

        /* Hide interactive elements */
        .no-print,
        button,
        .gantt-popup,
        nav,
        header,
        footer {
            display: none !important;
        }

        /* Full width for print */
        .gantt-chart-wrapper {
            max-width: 100%;
        }

        /* Remove shadows and borders for cleaner print */
        .gantt-chart-wrapper > div {
            border: none !important;
            box-shadow: none !important;
        }

        /* Ensure gantt fills page */
        .gantt-container {
            height: auto !important;
            overflow: visible !important;
            page-break-inside: avoid;
        }

        /* Print project list on first page */
        .w-80 {
            page-break-after: always;
        }

        /* Optimize colors for print */
        .gantt .bar-wrapper .bar {
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
        }

        /* Show stage legend */
        .flex.flex-wrap.items-center.gap-4.mt-4 {
            display: flex !important;
            page-break-before: avoid;
        }
    }
</style>
@endassets

@script
<script>
    Alpine.data('ganttChart', (config) => ({
        gantt: null,
        tasks: config.tasks || [],
        viewMode: config.viewMode || 'Month',

        init() {
            this.$nextTick(() => {
                this.initGantt();
                this.addTodayMarker();
            });

            // Listen for scroll to today event
            window.addEventListener('gantt-scroll-to-today', () => this.scrollToToday());

            // Listen for export event
            window.addEventListener('gantt-export', () => this.exportGantt());

            // Setup keyboard shortcuts
            this.setupKeyboardShortcuts();
        },

        setupKeyboardShortcuts() {
            document.addEventListener('keydown', (e) => {
                // Ignore if user is typing in input field
                if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') {
                    return;
                }

                switch(e.key.toLowerCase()) {
                    case 't':
                        e.preventDefault();
                        this.scrollToToday();
                        break;
                    case 'e':
                        e.preventDefault();
                        this.exportGantt();
                        break;
                    case 'p':
                        e.preventDefault();
                        window.print();
                        break;
                    case '1':
                        e.preventDefault();
                        this.$wire.dispatch('set-view-mode', { mode: 'Day' });
                        this.changeViewMode('Day');
                        break;
                    case '2':
                        e.preventDefault();
                        this.$wire.dispatch('set-view-mode', { mode: 'Week' });
                        this.changeViewMode('Week');
                        break;
                    case '3':
                        e.preventDefault();
                        this.$wire.dispatch('set-view-mode', { mode: 'Month' });
                        this.changeViewMode('Month');
                        break;
                    case '4':
                        e.preventDefault();
                        this.$wire.dispatch('set-view-mode', { mode: 'Quarter' });
                        this.changeViewMode('Quarter');
                        break;
                    case '5':
                        e.preventDefault();
                        this.$wire.dispatch('set-view-mode', { mode: 'Year' });
                        this.changeViewMode('Year');
                        break;
                    case 'arrowleft':
                        e.preventDefault();
                        this.scrollGantt(-200);
                        break;
                    case 'arrowright':
                        e.preventDefault();
                        this.scrollGantt(200);
                        break;
                    case '?':
                        e.preventDefault();
                        // Toggle help - handled by Alpine component
                        break;
                }
            });
        },

        scrollGantt(amount) {
            const container = this.$refs.ganttContainer?.querySelector('.gantt-container');
            if (container) {
                container.scrollLeft += amount;
            }
        },

        exportGantt() {
            if (!this.$refs.ganttContainer) return;

            // Get the gantt SVG element
            const svg = this.$refs.ganttContainer.querySelector('.gantt svg');
            if (!svg) {
                this.$wire.dispatch('notify', {
                    type: 'warning',
                    title: 'No chart to export',
                    body: 'Please ensure projects are displayed on the timeline.'
                });
                return;
            }

            // Show export menu
            const exportMenu = document.createElement('div');
            exportMenu.className = 'fixed inset-0 bg-black/50 flex items-center justify-center z-50';
            exportMenu.innerHTML = `
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-2xl max-w-md w-full mx-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Export Gantt Chart</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">Choose your preferred export format:</p>
                    <div class="space-y-3">
                        <button onclick="this.getRootNode().host.exportSVG()" class="w-full px-4 py-3 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors flex items-center justify-between group">
                            <span class="flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                                SVG (Vector)
                            </span>
                            <span class="text-xs opacity-75">Best quality</span>
                        </button>
                        <button onclick="this.getRootNode().host.exportPNG()" class="w-full px-4 py-3 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors flex items-center justify-between group">
                            <span class="flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                PNG (Image)
                            </span>
                            <span class="text-xs opacity-75">Most compatible</span>
                        </button>
                    </div>
                    <button onclick="this.getRootNode().host.remove()" class="w-full mt-4 px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                        Cancel
                    </button>
                </div>
            `;

            // Store references for export functions
            exportMenu.exportSVG = () => {
                this.exportGanttSVG();
                exportMenu.remove();
            };
            exportMenu.exportPNG = () => {
                this.exportGanttPNG();
                exportMenu.remove();
            };

            document.body.appendChild(exportMenu);

            // Close on outside click
            exportMenu.addEventListener('click', (e) => {
                if (e.target === exportMenu) {
                    exportMenu.remove();
                }
            });
        },

        exportGanttSVG() {
            const svg = this.$refs.ganttContainer.querySelector('.gantt svg');
            const svgClone = svg.cloneNode(true);

            // Get SVG dimensions
            const bbox = svg.getBBox();
            svgClone.setAttribute('width', bbox.width + 40);
            svgClone.setAttribute('height', bbox.height + 40);
            svgClone.setAttribute('viewBox', `${bbox.x - 20} ${bbox.y - 20} ${bbox.width + 40} ${bbox.height + 40}`);

            // Add background
            const background = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
            background.setAttribute('x', bbox.x - 20);
            background.setAttribute('y', bbox.y - 20);
            background.setAttribute('width', bbox.width + 40);
            background.setAttribute('height', bbox.height + 40);
            background.setAttribute('fill', '#ffffff');
            svgClone.insertBefore(background, svgClone.firstChild);

            // Convert SVG to string
            const serializer = new XMLSerializer();
            const svgString = serializer.serializeToString(svgClone);

            // Create blob and download
            const blob = new Blob([svgString], { type: 'image/svg+xml' });
            const url = URL.createObjectURL(blob);

            const link = document.createElement('a');
            link.href = url;
            link.download = `gantt-chart-${new Date().toISOString().slice(0, 10)}.svg`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);

            this.$wire.dispatch('notify', {
                type: 'success',
                title: 'Export successful',
                body: 'SVG file has been downloaded.'
            });
        },

        async exportGanttPNG() {
            // Show loading notification
            this.$wire.dispatch('notify', {
                type: 'info',
                title: 'Exporting...',
                body: 'Generating PNG image. This may take a moment.'
            });

            try {
                const ganttElement = this.$refs.ganttContainer;

                // Use html2canvas to capture the gantt chart
                const canvas = await html2canvas(ganttElement, {
                    backgroundColor: '#ffffff',
                    scale: 2,
                    logging: false,
                    useCORS: true,
                    allowTaint: true
                });

                // Convert to blob and download
                canvas.toBlob((blob) => {
                    const url = URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = `gantt-chart-${new Date().toISOString().slice(0, 10)}.png`;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    URL.revokeObjectURL(url);

                    this.$wire.dispatch('notify', {
                        type: 'success',
                        title: 'Export successful',
                        body: 'PNG file has been downloaded.'
                    });
                });
            } catch (error) {
                console.error('PNG export failed:', error);
                this.$wire.dispatch('notify', {
                    type: 'error',
                    title: 'Export failed',
                    body: 'Unable to export PNG. Please try SVG format instead.'
                });
            }
        },

        addTodayMarker() {
            // Add enhanced today marker after gantt renders
            setTimeout(() => {
                const todayHighlight = this.$refs.ganttContainer?.querySelector('.today-highlight');
                if (todayHighlight) {
                    const x = todayHighlight.getAttribute('x');
                    const width = todayHighlight.getAttribute('width');
                    const centerX = parseFloat(x) + parseFloat(width) / 2;

                    // Add glow line (background)
                    const glowLine = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                    glowLine.setAttribute('class', 'today-line-glow');
                    glowLine.setAttribute('x1', centerX);
                    glowLine.setAttribute('x2', centerX);
                    glowLine.setAttribute('y1', '0');
                    glowLine.setAttribute('y2', '100%');
                    todayHighlight.parentNode.insertBefore(glowLine, todayHighlight.nextSibling);

                    // Add main line (foreground)
                    const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                    line.setAttribute('class', 'today-line');
                    line.setAttribute('x1', centerX);
                    line.setAttribute('x2', centerX);
                    line.setAttribute('y1', '0');
                    line.setAttribute('y2', '100%');
                    glowLine.parentNode.insertBefore(line, glowLine.nextSibling);
                }
            }, 500);
        },

        scrollToToday() {
            if (!this.gantt) return;
            this.gantt.scroll_current();
        },

        initGantt() {
            if (this.tasks.length === 0 || !this.$refs.ganttContainer) return;

            this.gantt = new Gantt(this.$refs.ganttContainer, this.tasks, {
                view_mode: this.viewMode,
                date_format: 'YYYY-MM-DD',
                popup_trigger: 'click',
                custom_popup_html: (task) => this.createPopupHtml(task),
                on_click: (task) => this.handleClick(task),
                on_date_change: (task, start, end) => this.handleDateChange(task, start, end),
                on_progress_change: (task, progress) => this.handleProgressChange(task, progress),
                bar_height: 26,
                bar_corner_radius: 6,
                arrow_curve: 5,
                padding: 16,
                language: 'en',
            });
        },

        createPopupHtml(task) {
            // Don't show popup for milestones
            if (task.id.startsWith('milestone-')) {
                return `
                    <div class="gantt-popup">
                        <h5>${task.name}</h5>
                        <p style="color: ${task.progress === 100 ? '#10b981' : '#6b7280'};">
                            ${task.progress === 100 ? '✓ Completed' : '○ Pending'}
                        </p>
                    </div>
                `;
            }

            return `
                <div class="gantt-popup">
                    <h5>${task.name}</h5>
                    ${task.customer ? `<p>📍 ${task.customer}</p>` : ''}
                    ${task.stage ? `<p>📊 ${task.stage}</p>` : ''}
                    ${task.linear_feet ? `<p>📏 ${task.linear_feet} LF</p>` : ''}
                    <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(0,0,0,0.1);">
                        <p>📅 ${task.start} → ${task.end}</p>
                        <p>⚡ ${task.progress}% complete</p>
                    </div>
                    <p class="popup-action">Click to view project →</p>
                </div>
            `;
        },

        handleClick(task) {
            // Don't navigate for milestones
            if (task.id.startsWith('milestone-')) return;

            // Navigate to project view page
            window.location.href = `/admin/project/projects/${task.id}`;
        },

        handleDateChange(task, start, end) {
            // Don't update milestones
            if (task.id.startsWith('milestone-')) return;

            // Format dates
            const startDate = start.toISOString().split('T')[0];
            const endDate = end.toISOString().split('T')[0];

            // Dispatch Livewire event to update project dates
            this.$wire.dispatch('gantt-date-change', {
                projectId: parseInt(task.id),
                start: startDate,
                end: endDate
            });
        },

        handleProgressChange(task, progress) {
            // Don't update milestones
            if (task.id.startsWith('milestone-')) return;

            // Dispatch Livewire event
            this.$wire.dispatch('gantt-progress-change', {
                projectId: parseInt(task.id),
                progress: Math.round(progress)
            });
        },

        changeViewMode(mode) {
            this.viewMode = mode;
            if (this.gantt) {
                this.gantt.change_view_mode(mode);
            }
        },

        refreshTasks(tasks) {
            this.tasks = tasks;
            if (this.gantt) {
                this.gantt.refresh(this.tasks);
            } else {
                this.initGantt();
            }
        }
    }));
</script>
@endscript
