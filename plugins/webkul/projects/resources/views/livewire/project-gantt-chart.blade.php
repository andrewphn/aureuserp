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
